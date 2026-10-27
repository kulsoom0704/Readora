# Testing

Run each suite from its own application directory. Both projects use Pest/PHPUnit.

## Readora website

Open a fresh PowerShell window and go to the repository root. The example below assumes you cloned to Documents; change the path if needed.

```powershell
Set-Location "$env:USERPROFILE\Documents\Readora"
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = ':memory:'
php artisan test --compact
```

The root `phpunit.xml` also configures SQLite `:memory:`. Setting these variables in a fresh terminal prevents inherited database settings from directing tests to the local SQLite file. The settings apply only to that PowerShell window; close it when finished.

To run one feature test, add its path, for example:

```powershell
php artisan test --compact tests\Feature\ReservationTest.php
```

## Inventory API

From a separate PowerShell window:

```powershell
Set-Location "$env:USERPROFILE\Documents\Readora\inventory-api"
php artisan test --compact
```

The API's `phpunit.xml` forces SQLite `:memory:`, so its tests do not use `database/inventory.sqlite`.

To run one API feature test:

```powershell
php artisan test --compact tests\Feature\InventoryReservationApiTest.php
```

## Test coverage

The Readora feature suite includes catalogue, authentication, navigation, Inventory API client/read behavior, admin inventory management, reservations, admin reservation actions, reviews, profiles, and legacy export tests.

The API feature suite covers health authentication, inventory/reservation endpoints, and the legacy data importer. Automated integration tests use HTTP fakes where appropriate; they do not require a live API server.

Use the output from the command you actually ran when reporting test counts. This guide does not claim a current pass count.
