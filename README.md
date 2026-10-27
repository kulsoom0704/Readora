# Readora

Readora is a bookstore website for browsing books, reserving copies, and reviewing books after purchase. It combines a Laravel and Blade website with a separate Inventory API that manages stock and reservations.

Readora began as my own project last year. I reused that foundation and extended it for this assignment.

## Features

- Browse books and categories, view book details, and use the FAQ and contact pages.
- Register, sign in, and manage a profile.
- Manage books and stock as an administrator.
- Reserve and cancel copies; administrators can mark reservations as purchased.
- Review a book after purchase, with one review per user and book.

## How the applications fit together

```text
Browser -> Readora website (Blade + Laravel backend) --HTTP/JSON--> Inventory API
                    |                                                   |
                    v                                                   v
          Readora SQLite database                         Inventory API SQLite database
```

The website and its backend are one Laravel application. The Inventory API is a separate Laravel backend. Readora calls it from server-side PHP; the apps use separate databases and do not share API secrets with the browser.

## Technologies

- PHP 8.4+, Laravel 13, Blade
- Tailwind CSS 3, Alpine.js, Vite
- SQLite
- A separate Laravel 13 Inventory API

## Project structure

- `app/`, `routes/`, `resources/`, and `database/`: Readora's backend, website, and database migrations.
- `tests/`: Readora's automated tests.
- `inventory-api/`: the standalone Inventory API, its own database, and its tests.
- `docs/`: installation, testing, deployment, project plan, and references.

## Getting started

See [Installation](docs/INSTALLATION.md) for prerequisites and step-by-step instructions to configure and run both applications.

## Testing

Both applications have separate test suites. See [Testing](docs/TESTING.md) for the commands and safe in-memory database setup.

## More documentation

- [Deployment](docs/DEPLOYMENT.md)
- [Project plan](docs/PROJECT_PLAN.md)
- [References and AI assistance](docs/REFERENCES.md)
- [Inventory API guide](inventory-api/README.md)

The project has not been deployed to production; the deployment guide describes steps to prepare a deployment.
