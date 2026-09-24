# Contributing to OpenBooks SG

Thank you for your interest in contributing to OpenBooks SG! This guide will help you get set up and understand our development workflow.

## Development Environment Setup

### Prerequisites

- PHP 8.3+ with extensions: `pdo_mysql`, `gd`, `zip`, `bcmath`, `intl`, `mbstring`
- MySQL 8.0+ (or use Docker Compose for a zero-config database)
- Composer 2.x
- Node.js 20+ and npm

### Getting Started

```bash
# Fork and clone the repository
git clone https://github.com/consultanything-tech/openbooks-sg.git
cd openbooks-sg

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Set your database credentials in .env, then run migrations
php artisan migrate

# Build frontend assets
npm run dev

# Start the development server
composer dev
```

Alternatively, use Docker for the full stack:

```bash
docker compose up -d
```

## Code Style

We use **Laravel Pint** for PHP code formatting and **PHPStan (level 5)** for static analysis.

```bash
# Auto-fix code style
./vendor/bin/pint

# Check code style without fixing
./vendor/bin/pint --test

# Run static analysis
./vendor/bin/phpstan analyse
```

Pint enforces PSR-12 with Laravel conventions. Do not manually format code -- let Pint handle it. Any PR that fails Pint checks will not be merged.

## Branch Naming Convention

Use descriptive branch names prefixed by type:

| Prefix      | Usage                          | Example                        |
|-------------|--------------------------------|--------------------------------|
| `feature/`  | New features                   | `feature/paynow-sgqr-support`  |
| `fix/`      | Bug fixes                      | `fix/invoice-total-rounding`   |
| `refactor/` | Code restructuring             | `refactor/report-queries`      |
| `docs/`     | Documentation only             | `docs/api-examples`            |
| `test/`     | Adding or fixing tests         | `test/billing-edge-cases`      |

## Commit Message Format

Write clear, concise commit messages in the imperative mood:

```
Add GST F5 export to CSV
Fix rounding error in invoice totals
Refactor banking reconciliation query
Update README with Docker instructions
```

Guidelines:
- First line: short summary (50-72 characters), imperative mood, no period
- Blank line, then optional body for complex changes
- Reference issues with `#123` or `Fixes #123` when applicable

## Pull Request Process

1. **Create a branch** from `main` using the naming convention above.
2. **Write tests** for any new functionality or bug fix.
3. **Ensure all tests pass** (`php artisan test`).
4. **Run Pint** (`./vendor/bin/pint`) to fix code style.
5. **Open a PR** against the `main` branch with:
   - A clear title summarizing the change
   - A description explaining **why** the change is needed
   - Screenshots for UI changes
   - Reference to any related issues
6. **Wait for review.** A maintainer will review your PR and may request changes.
7. **Do not force-push** after review has started. Add new commits instead so reviewers can see incremental changes.

## Running Tests

Tests use an SQLite in-memory database, so no external database is required.

```bash
# Run the full test suite
php artisan test

# Run with coverage
php artisan test --coverage

# Run a single test file
php artisan test tests/Feature/InvoiceTest.php

# Run tests matching a name
php artisan test --filter="test_invoice_can_be_created"
```

All new features must include tests. Bug fixes should include a regression test that fails before the fix and passes after.

## Architecture Overview

OpenBooks SG follows standard Laravel conventions:

```
app/
  Http/
    Controllers/       # Web controllers (one per feature area)
    Controllers/Api/   # REST API controllers (v1)
    Middleware/         # Auth, role-based access, API authentication
  Models/              # Eloquent models
  Services/            # Business logic (reports, AI, OCR, etc.)
database/
  migrations/          # Schema migrations
  seeders/             # Default data (chart of accounts, roles)
  factories/           # Test factories
resources/
  views/               # Blade templates
    layouts/           # App shell, sidebar, header
    partials/          # Reusable components (keyboard shortcuts, modals)
routes/
  web.php              # All web routes with role middleware groups
  api.php              # REST API v1 routes
tests/
  Feature/             # Integration tests
  Unit/                # Unit tests
```

### Key Patterns

- **Role-based routing:** Routes are grouped by middleware (`role:ADMIN,ACCOUNTANT` for write access, `role:ADMIN` for system settings).
- **API authentication:** The API uses a custom `ApiAuthenticate` middleware with Bearer tokens.
- **Report generation:** Reports are handled by dedicated controllers that query the database and render Blade views with optional CSV export.
- **AI integration:** The AI assistant and receipt OCR features connect to external NVIDIA NIM endpoints configured in Settings.

## Getting Help

- Open a GitHub issue for bugs or feature requests
- Join discussions in the GitHub Discussions tab
- Tag your issues with appropriate labels (`bug`, `feature`, `question`)
