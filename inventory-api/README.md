# Readora Inventory and Reservations API

This is a small, standalone Laravel API application for Readora's stock and reservation lifecycle. It runs separately from the Readora website and never connects to Readora's database. External Readora book and user IDs are stored as values, without cross-database foreign keys.

## Prerequisites

- PHP 8.3 or newer with `pdo_sqlite` enabled (checked here with PHP 8.4.21).
- Composer 2.9 or newer (checked here with Composer 2.9.5).
- PowerShell on Windows for the commands below.

Laravel 13 requires PHP 8.3 or newer. This application uses Laravel 13 and runs separately from the Readora website.

## Install and configure

From the `inventory-api` directory:

```powershell
composer install
if (-not (Test-Path .env)) {
    Copy-Item .env.example .env
}
if (-not (Select-String -Path .env -Pattern '^APP_KEY=.+$' -Quiet)) {
    php artisan key:generate
}
```

The `.env.example` configures SQLite to use `database/inventory.sqlite` inside this application. It must remain separate from Readora's `database/database.sqlite`.

Generate a random local service token:

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Copy the generated value into `INVENTORY_API_TOKEN` in this application's `.env`. Later, configure the same value in Readora's `.env` for backend-to-backend calls. Do not commit either `.env` file or put the token in Blade, JavaScript, or browser-visible configuration.

Generate a second random value for signed user and administrator identity assertions:

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Put it in `INVENTORY_IDENTITY_SECRET` in this application's `.env`. Readora's PHP backend will later use the same secret when signing identity claims. Keep both values in backend `.env` files only. The service token authenticates Readora as a service; the separate signature proves which user Readora authenticated and whether that user has its admin role.

## Create the API database

Still in `inventory-api`, create the empty SQLite file if it does not exist, then run this application's migrations:

```powershell
if (-not (Test-Path database/inventory.sqlite)) {
    New-Item -ItemType File -Path database/inventory.sqlite | Out-Null
}

php artisan migrate
```

These commands use `inventory-api/database/inventory.sqlite`. They do not use or change Readora's database. The migrations create the API's `inventories` and `reservations` tables as well as Laravel's standard tables. They do not migrate or copy any Readora records.

## Start the API

From `inventory-api`:

```powershell
php artisan serve --host=127.0.0.1 --port=8001
```

The service listens at `http://127.0.0.1:8001`. All endpoints require the `X-Service-Token` request header. The health endpoint is `GET /api/v1/health`.

## Test the API

Tests force SQLite `:memory:` in `phpunit.xml`, so they do not use either application's file database.

```powershell
php artisan test --compact
```

## Check the health endpoint

From a second PowerShell window, set `$token` to the local token you placed in `.env`, then send it in the request header:

```powershell
$token = Read-Host 'Paste the local Inventory API token'
Invoke-RestMethod -Uri 'http://127.0.0.1:8001/api/v1/health' -Headers @{ 'X-Service-Token' = $token }
```

A valid token returns:

```json
{"status":"ok"}
```

To confirm that authentication is required, call the same URL without a header; it should return HTTP `401`:

```powershell
curl.exe -i 'http://127.0.0.1:8001/api/v1/health'
```

## Phase 2 API endpoints

The API also verifies a signed Readora identity for user-specific actions. Readora's backend sends `X-Readora-User-Id`, `X-Readora-Role` (`user` or `admin`), `X-Readora-Timestamp`, and `X-Readora-Signature`. The signature is a lowercase hexadecimal HMAC-SHA256, using `INVENTORY_IDENTITY_SECRET` and this exact UTF-8 message:

```text
UPPERCASE_HTTP_METHOD
/api/v1/request/path
UNIX_TIMESTAMP
READORA_USER_ID
user_or_admin
IDEMPOTENCY_KEY_OR_EMPTY
SHA256_HEX_OF_RAW_REQUEST_BODY
```

Each value is on its own line. Use an empty line for the idempotency key when the request has none. The signature is valid for five minutes. This binds the asserted user and role to the method, URL, time, idempotency key, and body. Only Readora's PHP backend should have the secrets; never send these values from browser JavaScript. A deployment must use HTTPS and keep the clocks synchronized. Because this is a shared-secret design, a compromised Readora backend can create signed assertions; rotate the identity secret if exposed.

| Method and path | Purpose | Identity needed |
| --- | --- | --- |
| `GET /api/v1/health` | Check API status | Service token |
| `PUT /api/v1/inventory/books/{bookId}` | Create/update stock (`{"stock": 4}` or `{"stock": null}`) | Signed admin |
| `GET /api/v1/inventory/books/{bookId}` | Read stock and availability | Service token |
| `POST /api/v1/reservations` | Reserve one copy (`{"book_id": 12}`) and send `Idempotency-Key` header | Signed user |
| `GET /api/v1/reservations` | List only the asserted user's reservations | Signed user |
| `GET /api/v1/reservations/{id}` | Read one of the asserted user's reservations | Signed user |
| `DELETE /api/v1/reservations/{id}` | Cancel own active reservation and restore one copy | Signed user |
| `GET /api/v1/admin/reservations` | List reservations for admin work | Signed admin |
| `PATCH /api/v1/admin/reservations/{id}/purchase` | Mark an active reservation purchased without changing stock | Signed admin |

Successful responses use a `data` object (or a `data` list and `meta` pagination fields for lists). Validation errors return `422`, missing or invalid authentication returns `401`, a non-admin identity gets `403`, a missing record gets `404`, and unavailable stock or a reservation state conflict returns `409`. Each user's idempotency key can create only one reservation; retrying the same key and book returns the original reservation. Reusing that key for a different book returns `409`.

Stock is decremented only when its current value is a known positive integer, in the same transaction that creates the reservation. Setting stock to unknown (`null`) is rejected while active reservations exist, so cancellation can still restore a known quantity safely. Cancellation changes an active reservation and increments stock in one transaction; purchase changes only the status.
