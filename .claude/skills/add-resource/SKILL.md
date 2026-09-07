---
name: add-resource
description: Scaffold a new translatable catalog resource (model, migration, FormRequest, policy, controller, shared Blade form, en/ru lang files, Pest test) following this repo's exact conventions. Pass the singular StudlyCase resource name, e.g. /add-resource Standard.
disable-model-invocation: true
---

Scaffold a new translatable catalog resource named `$ARGUMENTS` (singular StudlyCase). Read the existing `Category` implementation first and mirror it — it is the reference for every piece below. Do not invent a different structure.

Write each of these, all with `declare(strict_types=1);` and alphabetically sorted imports:

1. **Migration** (`database/migrations/`) — JSON column for each translatable attribute, a `slug` unique column if the resource is slugged, `softDeletes()`. Match the FK deletion behaviour of neighbouring tables: cascade only where ownership is real, otherwise `restrictOnDelete()`.

2. **Model** (`app/Models/`) — `use HasFactory, SoftDeletes;` plus `use App\Traits\HasTranslations;` with `protected array $translatable = ['name'];`. Do NOT reach for spatie/laravel-translatable. Add accurate `@property` docblocks — PHPStan runs with `checkModelProperties: true`.

3. **FormRequest** (`app/Http/Requests/`) — require **both** locales (`name.en`, `name.ru` required). Unique rules use `Rule::unique(...)->ignore($id)->whereNull('deleted_at')` because everything soft-deletes. If slugged, generate it in `prepareForValidation()` via `Str::slug($this->input('name.en'))`.

4. **Policy** (`app/Policies/`) — extend `CatalogPolicy` unless the resource needs different rules (all read; admin+moderator write; admin-only delete). **Register it explicitly** with `Gate::policy(...)` in `AppServiceProvider::boot()` — this repo does not use auto-discovery.

5. **Controller** (`app/Http/Controllers/`) — return types on every action (`View`, `RedirectResponse`). Use `authorizeResource()` in the constructor, matching `CategoryController`. Business-rule failures (e.g. deleting a row that still has children) return `back()->withErrors([...])`, never a status code. Successes redirect `->with('status', __('...'))`.

6. **Views** (`resources/views/<plural>/`) — `index`, `create`, `edit`, `show`, and a single shared `_form.blade.php` that both `create` and `edit` render, so the two cannot drift. Inputs post `name[en]` and `name[ru]`. Use `@can` for button visibility (the controller still authorizes). Bootstrap 5 classes; no new JS beyond a `confirm()` on delete.

7. **Routes** (`routes/web.php`) — add inside the existing authenticated group; do not create a new middleware group.

8. **Lang files** — `lang/en/<plural>.php` AND `lang/ru/<plural>.php`, same keys in both. No hardcoded user-facing strings anywhere. Use `trans_choice` plural strings where counts appear, with Russian 1 / 2–4 / 5+ forms.

9. **Factory + Pest feature test** — `tests/Feature/`, with `uses(RefreshDatabase::class);` at the top of the file (there is no `tests/Pest.php` binding). Cover: authorized create, unauthorized create is forbidden, both-locale validation, and the delete guard.

Finish by running `/verify`. Report anything you deliberately left out.
