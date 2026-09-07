---
name: verify
description: Run this project's full quality gate — Pint, Pest, and PHPStan level 6 — and report exactly what failed. Use before declaring any change done.
---

Run the three checks in this order, each as a separate command so failures are attributable:

```bash
vendor/bin/pint --test    # check-only; do NOT use `composer lint`, which rewrites files
composer test             # config:clear + php artisan test (Pest)
composer analyse          # phpstan level 6 over app/ database/ routes/
```

Notes for interpreting results:

- The project is bootstrapped: `vendor/`, `artisan`, `.env` and `database/database.sqlite` all exist. If one is missing, restore it (`composer install`, `cp .env.example .env && php artisan key:generate`, `touch database/database.sqlite && php artisan migrate --seed`) rather than reporting the change as broken.
- `composer analyse` has pre-existing level-6 findings unrelated to any new change — compare against a baseline run before blaming the diff.
- Pint failures are almost always a missing `declare(strict_types=1);` or unsorted imports. Fix by running `composer lint` (write mode), then re-run `vendor/bin/pint --test`.
- PHPStan runs with `checkModelProperties: true`, so a new or renamed model column needs its `@property` docblock updated.
- `composer test` clears the config cache first on purpose — don't skip it in favour of a bare `php artisan test`.

To narrow a single failing test: `vendor/bin/pest tests/Feature/SomeTest.php --filter="the test name"`.

Report a short per-check pass/fail summary plus the actual output of anything that failed. Do not claim success for a check you did not run.
