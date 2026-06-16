# Contributing

Thank you for considering a contribution to Argusz.

## Development Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

Run the local development stack with:

```bash
composer dev
```

## Checks

Before opening a pull request, run:

```bash
composer test
```

For the broader CI-style checks, run:

```bash
composer ci
```

## Pull Requests

- Keep changes focused.
- Include tests for behavioral changes.
- Avoid committing secrets, local `.env` files, generated logs, database dumps, or organization-specific data.
- Document new configuration keys in `.env.example` and `README.md`.

## Coding Style

PHP code is formatted with Laravel Pint. Blade templates are formatted with
Prettier and the Blade plugin.
