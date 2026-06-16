# Argusz Security Dashboard

Argusz is an open-source security dashboard for CI/CD scan results. It collects
findings from security tools, groups them by service and repository, and turns
raw scanner output into operational views for developers, security teams, and
management.

De applicatie ondersteunt in de basis vier categorieën security scans:

- **SCA** (bijv. `Trivy`, `Snyk`) voor kwetsbaarheden in dependencies en containers.
- **SECRETS** (bijv. `Gitleaks`, `Trufflehog`) voor gelekte secrets.
- **SAST** (bijv. `Semgrep`, `Sobelow`) voor statische code-analyse.
- **IaC** (bijv. `Checkov`, `tfsec`, `terrascan`, `kics`) voor Infrastructure as Code misconfiguraties.

Dankzij de universele **SARIF v2.1.0** integratie kan Argusz in principe resultaten van elk type scanner die dit formaat exporteert verwerken.

---

## Features

- Ingest API for CI/CD pipelines.
- Configurable GitHub Actions OIDC validation for trusted scan submissions.
- Service, repository, pipeline-run, and finding views.
- Finding lifecycle tracking with open, resolved, returning, and snoozed states.
- Trend analysis with MTTR, SLA aging, risk velocity, and repository comparison.
- Role-based access control with Spatie Permission.
- Optional issue tracker integration, with Jira as the current provider.
- Optional critical-finding notifications, with Microsoft Teams as the current provider.
- Audit logs for administrative actions.

## Tech Stack

- PHP 8.4
- Laravel 12
- Livewire 4
- Blade
- Tailwind CSS
- Vite
- Chart.js
- MySQL or another relational database for users, roles, sessions, queues, and audit logs
- MongoDB for security scan data

## Requirements

- PHP 8.4 or newer
- Composer
- Node.js 24 or newer
- npm 11 or newer
- A relational database supported by Laravel
- MongoDB

## Local Installation

Argusz needs both a relational database and a running MongoDB instance before
migrations and the app will work. Set `DB_*` and `DOCUMENTDB_*` in your `.env`
(see [Configuration](#configuration)) first.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run build
```

`db:seed` creates the `management` and `developer` roles and their permissions.
Create your first administrator with local password login:

```bash
php artisan argusz:create-admin
```

To explore the dashboard with example data, seed demo services and findings
(requires a running MongoDB; disabled in production):

```bash
php artisan db:seed --class=DemoDataSeeder
```

For local development:

```bash
composer dev
```

This starts the Laravel server, queue listener, log tail, Vite dev server, and
Nightwatch agent.

Laravel Telescope is available for debugging but disabled by default. Enable it
in local development with:

```dotenv
TELESCOPE_ENABLED=true
```

## Configuration

Set the application name and optional logo:

```dotenv
APP_NAME=Argusz
APP_LOGO=
APP_LOGO_ALT="${APP_NAME}"
AUTH_PROVIDER=local
SCM_PROVIDER=github
```

Configure your databases. Argusz uses a relational database for users, roles,
sessions, queues, and audit logs, and MongoDB for security scan data. Both must
be reachable before the app will start.

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=argusz
DB_USERNAME=root
DB_PASSWORD=

DOCUMENTDB_DSN=mongodb://127.0.0.1:27017
DOCUMENTDB_DATABASE=argusz
DOCUMENTDB_TLS=false
```

Configure the source control integration. GitHub is the current provider used
for source links, code snippets, and default-branch lookup:

```dotenv
SCM_PROVIDER=github
GITHUB_API_URL=https://api.github.com
GITHUB_TOKEN=
```

Configure GitHub Actions OIDC validation for ingest. `OIDC_ALLOWED_REPOSITORIES`
is required before the ingest API accepts tokens:

```dotenv
OIDC_ISSUER=https://token.actions.githubusercontent.com
OIDC_JWKS_URL=https://token.actions.githubusercontent.com/.well-known/jwks
OIDC_AUDIENCE=security-dashboard
OIDC_ALLOWED_REPOSITORIES=your-org/your-repo,your-org/another-repo
```

Optionally restrict admin-created users to one or more email domains:

```dotenv
USER_ALLOWED_EMAIL_DOMAINS=example.com,example.org
```

Configure authentication. Two providers are available:

`local` (default) uses email/password accounts. Create the first administrator
with `php artisan argusz:create-admin`; additional users are managed in-app.

```dotenv
AUTH_PROVIDER=local
```

`azure` uses Microsoft Entra ID (Azure AD) single sign-on. Users must already
exist with a role assigned before they can sign in:

```dotenv
AUTH_PROVIDER=azure
AZURE_CLIENT_ID=
AZURE_CLIENT_SECRET=
AZURE_REDIRECT_URI=
AZURE_TENANT_ID=
```

Optional issue tracker integration. Jira is the current provider. Leave
`ISSUE_TRACKER_ENABLED=false` to run without issue tracker actions:

```dotenv
ISSUE_TRACKER_PROVIDER=jira
ISSUE_TRACKER_ENABLED=false
ISSUE_TRACKER_SYNC_STATUSES=false
JIRA_URL=
JIRA_USERNAME=
JIRA_API_TOKEN=
JIRA_PROJECT_KEY=
JIRA_STATUS_DONE=Done
JIRA_STATUS_REOPEN="To Do"
```

Optional critical-finding notifications. Microsoft Teams is the current
provider:

```dotenv
NOTIFICATION_PROVIDER=teams
CRITICAL_FINDING_NOTIFICATIONS_ENABLED=false
TEAMS_WEBHOOK_URL=
```

## Ingest API

CI/CD workflows submit scan results to:

```http
POST /api/ingest
```

The request must include a valid Bearer token from GitHub Actions OIDC. The
payload includes repository metadata, branch, commit, actor, environment, and
findings per supported tool.

## CI/CD Scanning Workflows

The `.github/workflows/` directory contains reusable workflows that run the
supported scanners (Trivy, Gitleaks, Semgrep, Checkov), normalize their SARIF
output with `ci-pusher.php`, and push results to a running Argusz instance.

To use them in your own repositories, configure the following in the scanning
repository's GitHub settings:

- **Secret `DASHBOARD_URL`** — base URL of your Argusz instance.
- **Secret `CROSS_READ_REPO_TOKEN`** — token used to trigger and read scan runs.
- **Variable `OIDC_AUDIENCE`** — must match `OIDC_AUDIENCE` in your Argusz `.env`
  (defaults to `security-dashboard` if unset).

The repository submitting scans must also be listed in `OIDC_ALLOWED_REPOSITORIES`
on the Argusz instance, otherwise ingest is rejected.

## Quality Checks

```bash
composer test
composer ci
```

The project includes PHPUnit tests, Laravel Pint, Prettier for Blade templates,
PHPStan, Rector, and PHP Insights checks.

## Open Source Readiness

Before publishing your own fork or deployment-specific distribution, verify:

- no real `.env` files, API tokens, tenant IDs, webhook URLs, or database dumps are committed;
- no internal company names, domains, users, repositories, pipeline URLs, or customer data remain;
- branding assets are either generic or explicitly licensed for public use;
- old git history has been checked for secrets before making an existing repository public.

If the current private repository has ever contained secrets or internal-only
data, publish a fresh public repository with clean history instead of making the
private repository public.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

See [SECURITY.md](SECURITY.md).

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE).
