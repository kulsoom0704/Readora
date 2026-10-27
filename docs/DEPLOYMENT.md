# Deployment

Production deployment has not been completed or verified for this repository. This guide describes preparation steps only.

Readora and the Inventory API are separate Laravel applications and need separate deployment configuration. Readora must be able to reach the API over HTTPS.

## Server requirements

- PHP 8.4 or newer for Readora and the PHP extensions required by Laravel.
- PDO SQLite support if you keep SQLite in production.
- Node.js compatible with the locked Vite version to build Readora's frontend.
- A web server or managed Laravel host configured to serve each app from its own `public/` directory.

See [Laravel's deployment guide](https://laravel.com/docs/13.x/deployment) for the full framework checklist.

## Deployment sequence

1. Back up the Readora and Inventory API databases before changing a deployment.
2. Deploy both applications as separate Laravel apps. Configure each web server's document root to that app's `public/` directory, not the repository root.
3. Configure a separate production `.env` for each app. Use unique `APP_KEY` values. Set the production `APP_URL` values, SQLite paths, and the Readora `INVENTORY_API_URL` to an API address reachable from the Readora server.
4. Set the same service token and identity-signing secret in both backend environments. Keep them out of Git and browser code. Use HTTPS and restrict access to the API where possible.
5. Install production PHP dependencies from each application's directory:

```sh
composer install --no-dev --optimize-autoloader
```

6. Build the Readora frontend from the repository root:

```sh
npm ci
npm run build
```

7. After reviewing the target database and taking backups, run migrations separately from the Readora root and from `inventory-api`:

```sh
php artisan migrate --force
```

Do not run `migrate:fresh` or automatically seed production data. Keep both SQLite files on persistent storage and ensure Laravel can write to the database directory, `storage/`, and `bootstrap/cache/`.

Set `APP_ENV=production` and `APP_DEBUG=false`. Keep server clocks synchronized because the Inventory API verifies signed identity requests. Confirm the API URL, credentials, HTTPS, database backups, and stock/reservation flow in a staging environment before production use.
