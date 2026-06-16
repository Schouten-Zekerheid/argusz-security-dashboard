# Build steps
# 1. Initial stage where the env file is created and composer dependencies installed
# 2. Front-end build
# 3. Build final project image

ARG ACR_URL
ARG APP_ENV

ARG PROJECT_IMAGE=${ACR_URL}/image-${APP_ENV}-base-php-8.4:latest

FROM ${PROJECT_IMAGE} as project_builder

# Application Configuration
ARG APP_ENV
ARG APP_KEY
ARG APP_URL
ARG APP_LOGO
ARG APP_LOGO_ALT
ARG AUTH_PROVIDER
ARG SCM_PROVIDER
ARG DB_HOST

# Database Configuration
ARG DB_DATABASE
ARG DB_USERNAME
ARG DB_PASSWORD
ARG MYSQL_ATTR_SSL_CA_PATH
ARG MONGODB_URI
ARG MONGODB_DATABASE
ARG AZURE_CLIENT_ID
ARG AZURE_CLIENT_SECRET
ARG AZURE_REDIRECT_URI
ARG AZURE_TENANT_ID
# Logging Configuration
ARG NIGHTWATCH_SESSION_TOKEN
ARG LOG_CHANNEL
ARG LOG_LEVEL
# GitHub
ARG GITHUB_API_URL
ARG GITHUB_TOKEN
# Integrations
ARG ISSUE_TRACKER_PROVIDER
ARG ISSUE_TRACKER_ENABLED
ARG ISSUE_TRACKER_SYNC_STATUSES
ARG NOTIFICATION_PROVIDER
ARG CRITICAL_FINDING_NOTIFICATIONS_ENABLED
# Jira
ARG JIRA_URL
ARG JIRA_USERNAME
ARG JIRA_API_TOKEN
ARG JIRA_PROJECT_KEY

# Ingest OIDC
ARG OIDC_ISSUER
ARG OIDC_JWKS_URL
ARG OIDC_AUDIENCE
ARG OIDC_ALLOWED_REPOSITORIES

# User management
ARG USER_ALLOWED_EMAIL_DOMAINS

# Teams Configuration
ARG TEAMS_WEBHOOK_URL

# Use the Composer binary without depending on the base image packaging.
COPY --from=composer/composer:latest-bin /composer /usr/bin/composer
COPY . /var/www/html

RUN envsubst < /var/www/html/.env.docker > /var/www/html/.env

RUN composer install --no-dev --ignore-platform-reqs

FROM node:24 as node_build

WORKDIR /var/www/html

COPY --from=project_builder /var/www/html /var/www/html

RUN npm install \
    && npm run build \
    && rm -rf node_modules

FROM ${PROJECT_IMAGE}

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

COPY --from=node_build /var/www/html /var/www/html

COPY docker/php-custom-docker.ini /usr/local/etc/php/conf.d/php-custom-docker.ini
COPY docker/workers/* /etc/supervisor/conf.d/

RUN install-php-extensions mongodb \
    && echo 'root:Docker!' | chpasswd \
    && chmod -R 755 /var/www/html/public \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/public \
    && chown -R www-data:www-data /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache \
    && php artisan migrate --force \
    # cleanup
    && rm -rf /var/lib/apt/lists/* /root/.npm/* /root/.cache/*

# nosemgrep: dockerfile.security.missing-user-entrypoint.missing-user-entrypoint
ENTRYPOINT ["/docker/entrypoint.sh"]

EXPOSE 80 2222
