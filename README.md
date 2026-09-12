# icofex — Document Management (Laravel + Blade)

Laravel **13.x** · PHP **8.4+** · session auth · server-rendered Blade · MySQL 8 / PostgreSQL 15.

Server-rendered version of the app: no JSON API, no SPA. Every page is a Blade view, every mutation is a normal HTML `<form>` POST with CSRF and a redirect-with-flash afterwards.

---

## Install

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite          # local default; see .env for MySQL / PostgreSQL
php artisan storage:link
php artisan migrate --seed
php artisan serve
```

`.env`:

```env
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
FILESYSTEM_DISK=public
```

Add to `config/app.php` (used by `SetLocale` and the translation scopes):

```php
'supported_locales' => ['en', 'ru'],
```

Seeded logins, password `password` for all: **admin**, **moderator**, **client**.

---

## Pages

| Route | View | Who |
|---|---|---|
| `GET /login` · `GET /register` | `auth.login`, `auth.register` | guests |
| `GET /` | `dashboard.index` — every folder, grouped by standard | all |
| `GET /menus/{slug}` | `menus.show` — one standard's folders | all |
| `GET /folders/{folder}` | `folders.show` — upload form + documents table | all |
| `GET /profile` | `profile.edit` — account + editable company | all |
| `GET /admin/categories` | `categories.index` — cards with Create button | admin, moderator |
| `GET /admin/categories/create` · `/{id}/edit` | `categories.create`, `categories.edit` | admin, moderator |
| `GET /admin/folders` (+ create/edit) | `folders.*` | admin, moderator |
| `GET /admin/companies` (+ show) | `companies.*` | admin, moderator |

Document upload, download and delete post to `/folders/{folder}/documents` and `/documents/{document}`.

---

## Structure

```
app/Http/Controllers/
    Auth/AuthenticatedSessionController.php   login / logout
    Auth/RegisteredUserController.php         register + company in one transaction
    MenuController.php                        dashboard, standard pages, admin CRUD
    DocumentFolderController.php              folder page + admin CRUD
    CategoryController.php                    admin CRUD + reorder
    DocumentController.php                    upload / download / delete
    ProfileController.php                     company details
    CompanyController.php                     admin read-only list

resources/views/
    layouts/app.blade.php        sidebar shell
    layouts/sidebar.blade.php    nav, standards, admin <details> submenu
    layouts/guest.blade.php      centred card on the sidebar gradient
    components/                  x-alert, x-icon, x-page-header, x-doc-card, x-search-box
    auth/ dashboard/ menus/ folders/ categories/ profile/ companies/

public/css/styles.css            the frontend stylesheet, ported
lang/en, lang/ru                 app, auth, roles, statuses + per-resource messages
```

Create and edit share one `_form.blade.php` per resource, so the two pages can't drift apart. `old()` repopulates on validation failure and `@error` prints Bootstrap `is-invalid` feedback inline.

---

## Notes on the conversion

- **Auth** is now `Auth::login()` + session regeneration, not tokens. `LoginRequest::authenticate()` keeps the 5-try `username|ip` throttle and the blocked-account check; failures come back as `withErrors` on the form.
- **`EnsureUserIsActive`** logs the user out and redirects to `/login` mid-session if their status changes, instead of returning a 403 JSON body.
- **Policies are unchanged** — same `CatalogPolicy`, `CategoryPolicy`, `CompanyPolicy`, `DocumentPolicy`. Views call `@can` so buttons only render when the action is permitted, and controllers still authorize, so hiding a button is never the only guard.
- **Bilingual fields** post as `name[en]` / `name[ru]` and land in the same JSON column. `?lang=ru` or `Accept-Language` switches the whole UI, including validation messages and pluralisation (`trans_choice` with Russian's 1/2-4/5+ forms).
- **Deletion guards** surface as `withErrors` on the page rather than a 409 status: a category or folder with documents can't be removed, and `is_default` categories are blocked by the policy.
- **The sidebar's standards list** comes from a `View::composer`, not an inline query in the template.
- **No JavaScript** beyond two `confirm()` calls on delete buttons — the admin submenu uses `<details>`, matching the static prototype.

Run `composer test` (Pest: auth, categories, documents), `composer lint` (Pint), `composer analyse` (PHPStan level 6).
