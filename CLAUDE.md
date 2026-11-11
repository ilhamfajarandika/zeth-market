# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Indonesian game top-up storefront: a single Laravel 13 monolith (PHP 8.4) with a Filament 5 admin panel at `/admin`, Blade + Tailwind 4 storefront, MySQL, and PHPUnit. Two product types: `topup` (fulfilled via the Melo supplier API) and `account` (sold from internal stock in `game_accounts`). Payment goes through Tripay. There is no nickname/ID lookup by design (only regex format validation per game).

Project docs are in Indonesian. `docs/` holds the spec and rules; `docs/PROJECT_SPEC.md` is the source of truth, then `docs/AGENTS.md`, then other docs, then code. `docs/CURRENT_STATUS.md` lists what is built, known issues and open decisions. Read `docs/AGENTS.md` and `docs/CURRENT_STATUS.md` before non-trivial work. Claude Code does not load `docs/AGENTS.md` automatically. If sources conflict, report it instead of choosing. Features outside the spec (reseller pricing, nickname checks, MLBB calculator) need approval. Root `TECH_STACK.md` is older and partly outdated (4-week timeline, reseller pricing).

Deployment is a VPS (Coolify or Forge) behind Cloudflare, **not** Laravel Cloud, despite the Boost section below.

## Commands

```bash
composer run setup          # install, .env, key, migrate, npm build
composer run dev            # dev server (runs `php artisan dev`)
php artisan migrate --seed  # seeds admin@example.com / password, sample games, products, stock
npm run build               # Vite build (npm run dev for HMR)
php artisan schedule:work   # required locally/in production: runs accounts:release-expired every minute
php artisan test --compact                                   # full suite
php artisan test --compact tests/Feature/StorefrontTest.php  # one file
php artisan test --compact --filter=test_name                # one test
vendor/bin/pint --format agent                               # format after editing PHP
```

Tests need a running MySQL with two databases, `topup_game` (app) and `topup_game_test` (used by `phpunit.xml`, wiped per test). `vendor/` is not committed, so run `composer install` first. In tests, fake all Tripay/Melo HTTP with `Http::fake()`.

## Architecture

**Order and stock lifecycle.** `OrderStatus` runs `pending → paid → processing → success`, or ends in `failed`, `expired` or `refunded`. `OrderStatus::isFinal()` marks states that callbacks must never change, so callback handling has to be idempotent. Orders store a snapshot of the product name and price, and the price must never be re-read from `products` after the order exists. Totals are always computed server-side.

**Account stock** (`App\Services\AccountStockService`) is the only place `game_accounts` change state: `available → reserved → sold`, with `reserved → available` on release or expiry. Every mutation uses `DB::transaction` + `lockForUpdate()` so an account can't be sold twice. A reservation lasts 15 minutes (`RESERVATION_MINUTES`) and should match the payment invoice expiry. `accounts:release-expired` (scheduled in `routes/console.php`) calls `releaseExpired()`, which currently checks only time, not order status (known issue M2). Displayed stock is `Product::withStock()` (`available_accounts_count`). `game_accounts.credentials` uses the `encrypted` cast and is shown only on the invoice page after the order succeeds.

**External integrations** live in `App\Services\Payment\TripayService` and `App\Services\Supplier\MeloService`. They log requests to `api_logs` and are configured in `config/services.php` from `.env` (`TRIPAY_*`, `MELO_*`). Neither has been tested against the real APIs. Melo authenticates with `X-API-Key`/`X-Secret-Key` headers, uses the invoice as `buyer_trx_id` (retry-safe, no double debit), and replaced Digiflazz; `MELO_SANDBOX=true` simulates orders without debit.

**Webhooks** (`POST /webhook/tripay`, `/webhook/melo`, `app/Http/Controllers/Webhook/`) are excluded from CSRF in `bootstrap/app.php` (`preventRequestForgery(except: ['webhook/*'])`) and throttled. They verify the signature (Tripay: HMAC-SHA256 `X-Callback-Signature`; Melo: hex HMAC-SHA256 `X-H2H-Signature`), write an `ApiLog` row with `signature_valid`, and return 403 (Tripay) or 401 (Melo) on a bad signature. The step after that is still a TODO in the controllers: dispatching the payment and supplier callback jobs. Checkout (Livewire), the order-creation flow and those jobs are not built yet, so check `docs/CURRENT_STATUS.md` before assuming a feature exists.

**Admin.** Filament resources under `app/Filament/Resources/<Name>/` are split into `Schemas/`, `Tables/` and `Pages/`. `User::canAccessPanel()` allows only `UserRole::Admin`. Enums in `app/Enums` implement Filament's `HasLabel`/`HasColor`, so the same enum drives admin UI and domain logic.

**Storefront.** `StorefrontController` serves `home`, `game` and `invoice`. Orders are routed by `invoice` number (`Order::getRouteKeyName()`), and the games route is `/game/{game}`. The storefront views are in `resources/views/storefront`, with the layout at `resources/views/components/layouts/app.blade.php`.

## Project rules that differ from or add to Boost

- Schema changes go in new migrations only (never edit existing ones), and new tables or columns must be recorded in `docs/DATABASE_DESIGN.md`.
- Don't add Composer/NPM packages or services without approval.
- Don't run `git commit`/`git push` unless asked.
- Don't mark work done until tests pass. After a task, update `docs/CURRENT_STATUS.md`, and update `docs/DATABASE_DESIGN.md` or `docs/API_INTEGRATION.md` if the schema or an integration changed.
- Bugs found outside the current task go in `docs/CURRENT_STATUS.md` under "Masalah Ditemukan"; don't fix them unprompted.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
