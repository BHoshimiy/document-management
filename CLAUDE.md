# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

`icofex/document-manager` — a certification-document library. PHP 8.4 (Symfony 8 inside Laravel 13 requires >= 8.4.1), Laravel 13, Composer only (no npm/vite/mix). Server-rendered Blade + Bootstrap 5, session auth on `username` (not email). No API routes, no queues, no jobs, no events.

Domain hierarchy: `Menu` (a standard, e.g. Global GAP) → `DocumentFolder` (has a `code` like `RP-FER-01`) → `Document`, tagged with a `Category`. A `Category` is optionally scoped to a `Menu` (`categories.menu_id`, nullable = global) — `Menu` has many categories directly; a folder page offers the global categories plus the ones of its own menu (`Category::scopeForMenu`). A `DocumentFolder` in turn carries an optional `category_id`, which `DocumentFolderRequest` validates as global-or-same-menu. The UI calls a `Menu` a "menu" everywhere, but the lang keys are still named `app.standard*` — wording only moved, keys did not. Each client `User` has one `Company`; documents belong to a company.

**Bootstrapped.** The Laravel skeleton is in place: `artisan`, `public/index.php`, `bootstrap/providers.php`, `bootstrap/cache/`, `storage/`, `routes/console.php`, `phpunit.xml`, `tests/Pest.php`, `tests/TestCase.php`, `.env`(+`.env.example`) and a `config/app.php` that holds **only** `supported_locales` — Laravel merges the framework's own `config/app.php` underneath it, so add a key here only to override a framework default. Local dev and the test suite run on SQLite (`database/database.sqlite`, `:memory:` under Pest); MySQL 8 / PostgreSQL 15 stay the deployment targets, switched via `DB_CONNECTION` in `.env`.

## Commands

```bash
composer test      # php artisan config:clear --ansi && php artisan test
composer lint      # pint — WRITE mode; use `vendor/bin/pint --test` to check only
composer analyse   # phpstan analyse (level 6, paths: app database routes)
                   # --memory-limit=1G: Larastan boots the app, which busts the 128M CLI default
```

`composer test` clears the config cache first because `config('app.supported_locales')` is a hand-added key.

Single test: `vendor/bin/pest tests/Feature/CategoryTest.php --filter="auto-generates the slug"`.

Run `composer test` and `composer analyse` before declaring work done. The Pest suite is green; `composer analyse` still reports pre-existing level-6 findings (mostly missing `TModel` generics on the factories and model `@property` gaps) — don't read those as damage from your change, and don't fix them unless asked.

## Code style

- `declare(strict_types=1);` is mandatory in every PHP file (enforced by `@pint.json`). `bootstrap/app.php` is the sole exception.
- Imports alphabetically sorted, no unused imports.
- Type everything: return types on all controller actions (`View`, `RedirectResponse`, `StreamedResponse`), `private readonly` constructor promotion for injected services.
- PHPStan runs with `checkModelProperties: true` — model `@property` docblocks must be accurate.

## Translations

Bilingual (en/ru) is mandatory: every user-facing string lands in **both** `lang/en/` and `lang/ru/`. Never hardcode text in Blade or controllers. `lang/en/app.php` uses `trans_choice` strings; the Russian file needs 1 / 2–4 / 5+ plural forms.

Translatable model attributes use the hand-rolled `App\Traits\HasTranslations` — **do not add spatie/laravel-translatable**. Declare `protected array $translatable = ['name'];` (used by `Menu`, `DocumentFolder`, `Category`). Reading `$model->name` gives the current-locale string; `getTranslations('name')` gives the array; assigning an array replaces the whole JSON, assigning a string sets only the current locale. Forms post `name[en]` / `name[ru]` and requests require both.

**Known bug — keep both engines working:** `scopeOrderByTranslation` emits MySQL-only raw `JSON_UNQUOTE(JSON_EXTRACT(col, "$.locale"))`. MySQL 8 and PostgreSQL 15 are both supported targets, so avoid adding more engine-specific JSON SQL and prefer fixing this toward portability.

Locale resolution (`SetLocale`): `?lang=` query param → `Accept-Language` → fallback. It is appended to the `web` group globally *and* aliased `locale` and re-applied on the auth group.

## Authorization

Policies are registered explicitly via `Gate::policy(...)` in `AppServiceProvider::boot()`, **not** by Laravel's auto-discovery naming convention — new policies must be registered there.

- `Menu` + `DocumentFolder` share `CatalogPolicy`: all read; admin+moderator write; **admin only** delete.
- `CategoryPolicy extends CatalogPolicy` and blocks deleting `is_default` rows.
- `DocumentPolicy::viewAny`/`create` return `true` on purpose — scoping comes from `Document::scopeVisibleTo(User)` plus the explicit company-ownership check in `DocumentController::store`. Don't remove those.
- `CategoryController` declares the resource ability map through `HasMiddleware::middleware()` (`can:viewAny,…`, `can:view,category`, …) — Laravel no longer supports controller-instance middleware, so `authorizeResource()` in a constructor fatals. Its `reorder` action authorizes inline, as do all the other controllers.
- `@can` in Blade only hides buttons — the controller must still authorize.

Roles/statuses are backed enums cast on `User`: `App\Enums\UserRole` (`isAdmin()`, `canManageCatalog()`) and `App\Enums\UserStatus` (`canLogin()`).

## Conventions and gotchas

- **Errors**: business-rule failures return `back()->withErrors([...])` on the page, never an HTTP status code or JSON. Successes are `->with('status', __('...'))` + redirect. `EnsureUserIsActive` logs the user out and redirects to `/login` with `withErrors(['username' => ...])`.
- **Soft deletes on every model** — unique rules must use `->whereNull('deleted_at')`.
- `Model::shouldBeStrict(! app()->isProduction())` is on: lazy loading, missing attributes and discarded fillable attributes throw in dev/test.
- Sidebar menus come from a `View::composer` in `AppServiceProvider` injecting `$sidebarMenus` — never query menus inline in a template.
- `create` and `edit` views each render one shared `_form.blade.php` so the pages cannot drift.
- `DocumentService` stores to `companies/{companyId}/folders/{folderId}` on the `public` disk, wraps the DB insert in a transaction and deletes the orphaned file on failure. `delete()` soft-deletes and keeps the file unless force-deleting.
- `documents` FKs: `company_id` cascades; `category_id` and `document_folder_id` are `restrictOnDelete()`.
- Login throttling is doubled: a `throttle:auth` limiter (10/min per IP, defined in `AppServiceProvider`) plus `LoginRequest::ensureIsNotRateLimited()` (5 attempts keyed on `strtolower(username)|ip`).
- The `sessions` table is created inside the users migration — the session driver is expected to be `database`.

## Environment

```env
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
FILESYSTEM_DISK=public
SESSION_DRIVER=database   # the sessions table ships inside the users migration
CACHE_STORE=file          # there is no cache table, so never point this at database
QUEUE_CONNECTION=sync     # no jobs table either
DB_CONNECTION=sqlite      # local default; mysql/pgsql for the real targets
TRUSTED_PROXIES=          # "*" when serving through ngrok/a tunnel so X-Forwarded-Proto is honoured; empty otherwise
```

Plus `'supported_locales' => ['en', 'ru'],` in `config/app.php` (consumed by `SetLocale` and `HasTranslations::scopeWhereTranslationLike`, both defaulting to `['en','ru']` if absent).

Seeded logins, password `password`: `admin`, `moderator`, `client`.

## Git

Not yet a git repository. Once initialized, use conventional-commit messages (`feat:`, `fix:`, `refactor:`).

## Reference

- `@README.md` — setup and intended bootstrap
- `@DB_schema_document_managments.md` — schema reference
