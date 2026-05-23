# Budget Buddy

Monorepo with `app/` (Laravel 11 + Vue 3 + Inertia) and `budget_master.js` (legacy Google Apps Script).

## Quick start

Everything runs inside `app/`:

```sh
cd app
cp .env.example .env
php artisan key:generate
composer install && npm install
npm run build
# needs MySQL on localhost:3306
php artisan migrate && php artisan serve
```

## Test commands

```sh
cd app
php artisan test                     # all PHP backend tests (PHPUnit 11)
php artisan test --group=transactions # run by PHP 8 #[Group] attributes
npm run test                         # vitest run --dom (frontend)
npm run test -- --mode production    # CI frontend, needs LARAVEL_BYPASS_ENV_CHECK=1
```

Both are run in CI (`.github/workflows/php.yml`). Order there: `composer validate → npm install → npm run build → php artisan migrate → php artisan test → npm run test`.

## Architecture

- **Routes**: all inertia pages in `routes/web.php` (auth:sanctum + verified middleware). API routes are minimal (just `/api/user`).
- **Controllers** in `app/Http/Controllers/`: `TransactionController`, `CategoryController`, `CategoryTypeController`, `DashboardController`, `SettingsController`
- **Pages** (Vue) in `resources/js/Pages/`, auto-resolved by Inertia from `routes/web.php` names.
- **Models** in `app/Models/`: `Account`, `AccountType`, `Transaction`, `TransactionImage`, `Category`, `CategoryType`, `User`
- **Pivot**: `category_transaction` stores `percentage` as raw integer (e.g., `10000` = 100%). `transactions.amount` is also integer (e.g., `42000` = $420.00).

## Testing conventions

- Feature tests use `RefreshDatabase` + seed via `TestHarnessSeeder` (hardcoded IDs in 100000+ range).
- Tests grouped with PHP 8 `#[Group('transactions')]`, `#[Group('categories')]` attributes.
- `Tests\Util` helper provides `deleteMockTransactions()` / `deleteMockCategories()`.

## Dev tooling

```sh
npm run dev       # vite dev server
npm run watch     # vite build --watch
./vendor/bin/phpstan  # PHP static analysis
./vendor/bin/pint     # Laravel PHP linter / formatter
```

## Docker (Laravel Sail)

```sh
cd app && docker-compose up -d  # MySQL 8, Redis, Meilisearch, Mailpit, Selenium
```
