# SkillMatch agent instructions

## Sources and constraints

- Laravel 13 / PHP 8.3+, Blade, Tailwind 4, Alpine CSP, Vite 7, PHPUnit 12. Use `composer show --direct` and the lockfiles to confirm versions before relying on package APIs.
- `README.md` covers Windows/Herd setup; `docs/PENGUJIAN.md` covers MySQL tests. `docs/STATUS_IMPLEMENTASI.md` distinguishes implemented features from targets in `docs/PRD.md` and `docs/design.md`.
- Boost is already installed and configured in `.mcp.json`; the install instructions in `CLAUDE.md` are stale. Do not rerun `boost:install` as routine setup. When Boost tools are available, use `search-docs` for version-specific Laravel changes and `get-absolute-url` before sharing local URLs.
- Obtain approval before changing dependencies or adding base directories. Create documentation only when requested.

## Setup and runtime

- Use `composer install` and `npm ci`, not dependency updates. `.npmrc` intentionally disables npm lifecycle scripts.
- The application uses MySQL/InnoDB; SQLite is for tests. `.env.example` uses database-backed sessions, queues, and cache, so migrate before browsing.
- `scripts/setup.ps1` installs dependencies, generates a missing key, clears config, migrates, seeds master data, and builds assets. It does not create the MySQL database; if `.env` is absent, it copies the example and exits for configuration. Do not regenerate an existing application key or use `migrate:fresh` on working data.
- Serve with Herd or `php artisan serve --host=127.0.0.1 --port=8000 --no-reload`. Run `php artisan queue:work --tries=2 --timeout=110` and `php artisan schedule:work` separately. There is no `composer run dev` script.
- `routes/console.php` schedules assessment expiry every minute and pending-application screening every five minutes. Queue workers perform document scans; restart workers after configuration changes.
- `php artisan db:seed` creates master data, not demo accounts. `skillmatch:demo` requires `APP_ENV=local` and `SKILLMATCH_DEMO=true`; `skillmatch:admin` creates an Admin. Both commands prompt for credentials and are interactive exceptions to the usual `--no-interaction` workflow. See README for details.

## Frontend

- Use `npm run build` after frontend changes. The strict CSP in `app/Http/Middleware/SecurityHeaders.php` permits only same-origin scripts/connections; the baseline uses built assets rather than Vite's dev server.
- Keep Alpine's `@alpinejs/csp` build. Register behavior in `resources/js/app.js`; do not introduce inline JavaScript or eval-dependent Alpine expressions. Vite entrypoints are `resources/css/app.css` and `resources/js/app.js`; `public/build` is generated.

## Domain boundaries and invariants

- `bootstrap/app.php` wires web routes, the `role` middleware, security headers, and the Midtrans webhook CSRF exception. Authentication is implemented in `AuthController`; role-specific recruitment routes require verified email.
- Recruitment, matching, assessment, and payments live in `app/Services`. Submission stores a profile snapshot; selection in `RecruitmentService::decide` locks the volunteer, position, and application inside a transaction to protect quota and schedule checks. Preserve those concurrency guarantees.
- Documents use the private disk, not a public storage link. `ScanDocument` leaves disabled/failed scans as `scan_failed`; only `ready` documents are downloadable. `DocumentPolicy` grants no blanket Admin access and limits organizer access to related, submitted, non-withdrawn applications until 90 days after the event.
- `PaymentService` uses Midtrans sandbox endpoints. A checkout return does not prove payment: server-side reconciliation validates the order/amount and applies state idempotently. Keep the webhook's signature verification and API status check intact.

## Verification

- `composer test` clears cached configuration before running PHPUnit. Focused run: `composer test -- --compact tests/Feature/SkillMatchTest.php`; add `--filter=test_role_and_event_owner_are_enforced` for one method. When calling `php artisan test --compact` directly, clear cached config first.
- `phpunit.xml` defaults to SQLite `:memory:` (requires `pdo_sqlite`/`sqlite3`), synchronous queues, and array mail/cache/session drivers. `SkillMatchTest` uses `RefreshDatabase`, seeds master data, and disables Vite; a frontend build is not needed for that suite.
- Shell environment variables can override PHPUnit's database defaults. For MySQL tests use a dedicated `*_test` database and a separate terminal, following `docs/PENGUJIAN.md`; never point `RefreshDatabase` at working data.
- For quota/concurrency changes, also run the MySQL-only check: after setting the documented test environment, run `php artisan config:clear`, `php artisan migrate --force --no-interaction`, then `php tests/Support/concurrency.php`. The script requires `APP_ENV=testing` and a MySQL database ending in `_test`; it launches two PHP processes competing for one slot.
- After PHP edits, run `vendor/bin/pint --dirty --format agent` in a Git checkout. For an extracted copy without Git metadata, format only changed files with `php vendor/bin/pint path/to/changed.php --format agent`.
- Use PHPUnit tests for behavior changes; copy/style/layout-only edits do not need new tests. The only npm scripts are `build` and `dev`; no JS test, lint, or typecheck task is configured.
