# Budget Buddy

Monorepo with `app/` (Laravel 11 + Vue 3 + Inertia) and `budget_master.js` (legacy Google Apps Script).

## Quick start

Everything runs inside `app/`:

```sh
cd app
vendor/bin/sail artisan key:generate
vendor/bin/sail composer install && npm install
vendor/bin/sail npm run build
# needs MySQL on localhost:3306
vendor/bin/sail artisan migrate && vendor/bin/sail artisan serve
```

## Test commands

```sh
cd app
vendor/bin/sail artisan test                     # all PHP backend tests (PHPUnit 11)
vendor/bin/sail artisan test --group=transactions # run by PHP 8 #[Group] attributes
vendor/bin/sail npm run test                         # vitest run --dom (frontend)
vendor/bin/sail npm run test -- --mode production    # CI frontend, needs LARAVEL_BYPASS_ENV_CHECK=1
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
vendor/bin/sail npm run dev       # vite dev server
vendor/bin/sail npm run watch     # vite build --watch
./vendor/bin/phpstan  # PHP static analysis
./vendor/bin/pint     # Laravel PHP linter / formatter
```

## Docker (Laravel Sail)

```sh
cd app && vendor/bin/sail up -d  # MySQL 8, Redis, Meilisearch, Mailpit, Selenium
```
