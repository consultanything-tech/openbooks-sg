# OpenBooks SG — Agent Guidelines

## Project Overview

OpenBooks SG is a self-hosted accounting platform for Singapore businesses, built with Laravel 13 and PHP 8.3+. It handles invoicing, bills, banking, reports (including GST F5 for IRAS), inventory, time tracking, and more.

## Tech Stack

- **Backend:** PHP 8.3+, Laravel 13, MySQL 8.0 (SQLite for tests)
- **Frontend:** Blade templates, Tailwind CSS 4, Vite 6, Lucide icons
- **Testing:** PHPUnit 12 (feature + unit), Playwright (e2e)
- **Quality:** Laravel Pint (code style), PHPStan level 5 (static analysis)
- **AI/OCR:** NVIDIA NIM API (configurable, optional)

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# Configure DB_* in .env
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

## Key Conventions

- **Code style:** Run `./vendor/bin/pint` before committing. Do not manually format.
- **Static analysis:** `./vendor/bin/phpstan analyse` must pass at level 5.
- **Tests:** `php artisan test` — uses SQLite in-memory, no external DB needed.
- **Routes:** Web routes in `routes/web.php` (465 lines), API routes in `routes/api.php`.
- **Roles:** ADMIN, ACCOUNTANT, VIEWER — enforced via `role` middleware on route groups.
- **Config:** All secrets must go through `config/services.php`, never call `env()` outside `config/`.
- **Models:** Use `$hidden` for sensitive fields. Use `encrypted` cast for stored secrets.

## Architecture

```
app/Http/Controllers/       # Web controllers (one per feature area)
app/Http/Controllers/Api/   # REST API v1 controllers
app/Models/                 # 34 Eloquent models
app/Services/               # Business logic (Accounting, AskOpenBooks, OCR, OFX/QBO parsers)
resources/views/            # 108 Blade templates
database/migrations/        # 22 migration files
tests/Feature/              # 33 integration tests
tests/Unit/                 # Unit tests
```

## Important Notes

- Never hardcode passwords, API keys, or auth bypasses.
- The installer (`InstallController`) writes `.env` — handle with care.
- Seed data uses fictitious company names only — never use real companies.
- `storage/installed` acts as the install lock file.
