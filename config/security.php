<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ingest OIDC
    |--------------------------------------------------------------------------
    |
    | The ingest API accepts GitHub Actions OIDC tokens. Configure the expected
    | audience and the repositories that may submit scan results. Ingest is
    | rejected until at least one repository is configured.
    |
    */

    'oidc' => [
        'issuer' => env('OIDC_ISSUER', 'https://token.actions.githubusercontent.com'),
        'jwks_url' => env('OIDC_JWKS_URL', 'https://token.actions.githubusercontent.com/.well-known/jwks'),
        'audience' => env('OIDC_AUDIENCE', 'security-dashboard'),
        'allowed_repositories' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('OIDC_ALLOWED_REPOSITORIES', ''))
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    |
    | Leave USER_ALLOWED_EMAIL_DOMAINS empty to allow any valid email address.
    | Use a comma-separated list such as "example.com,example.org" to restrict
    | accounts to specific domains.
    |
    */

    'users' => [
        'allowed_email_domains' => array_filter(array_map(
            fn (string $domain): string => ltrim(trim($domain), '@'),
            explode(',', (string) env('USER_ALLOWED_EMAIL_DOMAINS', ''))
        )),
    ],

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    */

    'branding' => [
        'logo' => env('APP_LOGO'),
        'logo_alt' => env('APP_LOGO_ALT', env('APP_NAME', 'Argusz')),
    ],
];
