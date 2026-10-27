# Installation

This guide sets up Readora and its Inventory API on a fresh computer. Run commands in the folder shown. The PowerShell commands are for Windows; macOS/Linux equivalents are included where commands differ.

## 1. Install the required tools

Install:

- **Git** to clone the repository: [Git for Windows](https://git-scm.com/install/windows).
- **PHP 8.4 or newer** for the combined project. The root app requires PHP `^8.4`; the API alone requires `^8.3`. Laravel also needs Ctype, cURL, DOM, Fileinfo, Filter, Hash, Mbstring, OpenSSL, PCRE, PDO, Session, Tokenizer, and XML. Enable PDO SQLite (`pdo_sqlite`) for the databases. See [PHP downloads](https://www.php.net/downloads.php).
- **Composer 2.x** to install PHP packages: [Composer installation](https://getcomposer.org/download/). The project does not pin a Composer version.
- **Node.js and npm** to build Readora's frontend. The locked Vite version requires Node `^20.19` or `>=22.12`; npm comes with Node.js. See [Node.js downloads](https://nodejs.org/en/download).
- **A local PHP server environment.** Laravel Herd is an optional Windows setup that includes PHP and a web server: [Herd for Windows](https://herd.laravel.com/windows).

Check the tools in PowerShell:

```powershell
git --version
php --version
composer --version
node --version
npm --version
php -m | Select-String 'PDO|pdo_sqlite|sqlite3'
```

The final command should show PDO SQLite support. On macOS/Linux, use your distribution's PHP instructions to enable the same extension.

## 2. Clone the repository

Choose a folder for projects, then clone Readora:

```powershell
Set-Location "$env:USERPROFILE\Documents"
git clone https://github.com/kulsoom0704/Readora.git
Set-Location .\Readora
Test-Path .\inventory-api
```

The final command should return `True`. Run Readora commands from this repository root; run API commands from `inventory-api`.

On macOS/Linux, use `cd ~/Documents`, run the same `git clone` command, then `cd Readora`.

## 3. Install and configure Readora

Run these commands from the repository root:

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
composer install
npm ci
php artisan key:generate
if (-not (Test-Path database/database.sqlite)) {
    New-Item -ItemType File -Path database/database.sqlite | Out-Null
}
php artisan migrate
npm run build
```

Composer installs PHP packages in `vendor/`; `npm ci` installs the locked frontend packages in `node_modules/`; `npm run build` compiles the website's CSS and JavaScript. `key:generate` creates the local encryption key.

Readora defaults to SQLite at `database/database.sqlite`. Its example environment uses SQLite. If `DB_DATABASE` is blank or unset, Laravel uses that default path. The file check avoids overwriting an existing database. These database steps are for a fresh clone.

Open the root `.env` file and set:

- `APP_URL=http://127.0.0.1:8000`
- `DB_CONNECTION=sqlite`
- `INVENTORY_API_URL=http://127.0.0.1:8001`
- `INVENTORY_API_TOKEN` and `INVENTORY_IDENTITY_SECRET`, as described in step 4.

Do not put the service token or identity secret in browser JavaScript or commit either `.env` file.

## 4. Install and configure the Inventory API

From the repository root, enter the API folder and install its PHP packages:

```powershell
Set-Location .\inventory-api
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
composer install
php artisan key:generate
if (-not (Test-Path database/inventory.sqlite)) {
    New-Item -ItemType File -Path database/inventory.sqlite | Out-Null
}
php artisan migrate
```

The API uses its own database at `inventory-api/database/inventory.sqlite`; it never uses Readora's database. The API migrations create its tables but do not copy books, stock, or reservations.

In `inventory-api/.env`, check or set:

- `APP_URL=http://127.0.0.1:8001`
- `DB_CONNECTION=sqlite`
- `DB_DATABASE=database/inventory.sqlite`
- `INVENTORY_API_TOKEN`
- `INVENTORY_IDENTITY_SECRET`

Each app needs its own `APP_KEY`. The service token and identity secret must each have the same value in both applications' backend `.env` files. Generate two different random values. From either project folder, this PHP command generates a value:

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Run it twice, then copy each result privately into the matching variable in both `.env` files. Do not paste generated values into this guide, Git, or browser code. The [Inventory API guide](../inventory-api/README.md) explains the authentication headers and health endpoint.

A fresh API database has no book inventory. Once the apps are running, an administrator can initialize stock from Readora's **Manage Books** page. Unknown stock is unavailable.

## 5. Add sample books and create an administrator

Return to the Readora repository root, then seed sample categories and books once, in this order: `Set-Location ..` returns from `inventory-api` to the root.

```powershell
Set-Location ..
php artisan db:seed --class=CategorySeeder
php artisan db:seed --class=BookSeeder
```

These commands add six categories and seven sample books. The sample books have no ISBN, price, or API inventory because they predate those features. The seeders insert directly, so do not run them a second time on the same database.

For setup, avoid the default `php artisan db:seed` and `php artisan migrate --seed`: the combined seeder invokes a legacy admin seeder with a predefined credential, and that seeder does not reliably set the admin role. The old README's credential has been removed.

Instead, register your own account in Readora. Then, from the root, run:

```powershell
php artisan tinker
```

At the Tinker prompt, substitute the email you registered:

```php
$user = App\Models\User::where('email', 'you@example.test')->firstOrFail();
$user->is_admin = true;
$user->save();
exit
```

This promotes the selected local account. Do not use it on a production system unless you are authorized to administer that system.

> On macOS/Linux, replace `Copy-Item .env.example .env` with `cp .env.example .env`. To create a missing database file, use `mkdir -p database` followed by `touch database/database.sqlite`; in `inventory-api`, use `touch database/inventory.sqlite`. Composer, npm, and Artisan commands are unchanged.

## 6. Start both applications

Keep two terminal windows open.

In the first, from the Readora root:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

In the second, from `inventory-api`:

```powershell
php artisan serve --host=127.0.0.1 --port=8001
```

Open Readora at [http://127.0.0.1:8000](http://127.0.0.1:8000). The API listens on port 8001. If you choose another API port, update the root `INVENTORY_API_URL`.

## 7. Try the main features

1. Browse the catalogue as a visitor.
2. Sign in as the administrator you created; add or edit a book with accurate ISBN and price, then set its stock.
3. Register a normal user and reserve an available copy.
4. Check **My Reservations** for its status and pickup/payment instructions.
5. As admin, mark an active reservation as purchased.
6. As that user, submit a written 1–5 star review and confirm it appears on the book page.
7. Check that the normal user cannot access admin pages.

Reservation and stock actions require the API to be running. If it is unavailable, Readora reports an error instead of showing a successful action.

## Troubleshooting

- **A command is not recognized:** install the tool, reopen the terminal, and repeat the version check.
- **Dependencies are missing:** run `composer install` in the relevant app; run `npm ci` from the root for website assets.
- **The app key or `.env` is missing:** copy that app's `.env.example` and run `php artisan key:generate` from its folder.
- **Tables are missing:** confirm the working folder and database path before `php artisan migrate`. Do not use `migrate:fresh` to troubleshoot a database with data.
- **API authentication or connection fails:** confirm the API is running, the root URL is correct, and the backend credentials match. Never share their values in browser code or support messages.
- **Stock is unknown:** an API inventory record may not exist. Set stock through the administrator page.
- **A port is busy:** stop the service using it or select another port and update `INVENTORY_API_URL`.
- **Tests appear to target a file database:** follow [Testing](TESTING.md), which describes the isolated test configuration.
