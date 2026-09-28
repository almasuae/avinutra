# AviNutra — website and CRM

The public website for AviNutra (`https://avinutra.com`) and the private CRM at `/crm`.
The CRM is the reusable package in [`packages/lite-crm`](packages/lite-crm/README.md).

- Technical spec: [`docs/Prompt-AviNutra-Laravel-Build-v5.md`](docs/Prompt-AviNutra-Laravel-Build-v5.md)
- Content spec: [`docs/AviNutra-Website-Content-v3.md`](docs/AviNutra-Website-Content-v3.md)
- Missing facts: [`CONTENT-GAPS.md`](CONTENT-GAPS.md)
- Conventions for contributors and Claude Code: [`CLAUDE.md`](CLAUDE.md)

> This README is completed in Phase 9 (deployment, users, backups, mail DNS).

## Stack

PHP 8.3 · Laravel 13 · Filament 5 · Livewire 4 · Tailwind CSS 4 (Vite) · MariaDB 11.4
(SQLite for quick local work) · Pest 4 · Orchestra Testbench · Pint · Larastan level 5.
Node.js 20.19 or newer (`engines` in `package.json`); production runs 20.20.2.

## Dependency versions and why

Composer's platform is pinned to PHP 8.3 (`config.platform.php`), so the lock file
always resolves to versions that run on the production server.

| Package | Version | Where | Why this version |
|---|---|---|---|
| `laravel/framework` | 13.x | host | Latest stable Laravel supporting PHP 8.3 (v5 §B). |
| `filament/filament` | 5.x | host + package | Latest stable Filament (v5 asks for v4+); requires Livewire 4. |
| `spatie/laravel-permission` | ^8.3 (8.3.0) | package | Latest release; supports PHP 8.3 and Laravel 12/13. Roles and permissions (v5 §D1). |
| `spatie/laravel-activitylog` | ^4.12.3 (4.12.3) | package | 5.x requires PHP 8.4, so 4.12.3 is the newest release that supports PHP 8.3 and Laravel 13. Audit trail. Revisit when production moves to PHP 8.4. |
| `spatie/laravel-settings` | ^3.9 (3.9.0) | host | Latest release; supports PHP 8.3 and Laravel 13. Site settings (v5 §A5). |
| `pestphp/pest` | 4.x | dev | Pest 5 requires PHP 8.4. |
| `orchestra/testbench` | 11.x | dev | Matches Laravel 13, for package tests without the host. |
| `larastan/larastan` | 3.x | dev | Static analysis at level 5. |
| `@fontsource-variable/inter`, `@fontsource-variable/source-serif-4` | 5.x | npm | Self-hosted fonts (no font CDN). |

The permission and audit-log packages are dependencies of the CRM package because the
CRM uses them; they store their data in `crm_`-prefixed tables (`crm_roles`,
`crm_activity_log` ...). Settings is a host dependency because Site settings are
AviNutra-only.

## Local setup

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
touch database/database.sqlite     # or configure MariaDB in .env
php artisan migrate --seed         # seeds roles and neutral lists, never users
php artisan lite-crm:create-admin you@example.com
npm run dev                        # or: npm run build
php artisan serve
```

The site runs at `http://localhost:8000`, the CRM at `http://localhost:8000/crm`.

There are no seeded users and no default passwords. The first admin is created only
with `php artisan lite-crm:create-admin {email}` (it asks for a password of at least
12 characters, or sends an invitation with `--invite`). Admins must set up
authenticator-app MFA at their first sign-in; further users are invited from
CRM › CRM settings › Users.

## Quality checks (run after every phase)

```bash
vendor/bin/pint                                        # code style
vendor/bin/phpstan analyse --memory-limit=1G           # Larastan level 5
php artisan test                                       # host tests on SQLite
composer test:package                                  # package tests on SQLite (Testbench + Pest)
composer test:mariadb                                  # both suites on MariaDB 11.4
```

`composer test:mariadb` runs against a MariaDB 11.4 on `127.0.0.1:3307`. If nothing is
listening there, it starts the portable server in `~/.local/mariadb114` (override with
`MARIADB_HOME`) for the run and stops it afterwards. It drops and recreates the
`avinutra_test` and `lite_crm_test` databases, so never point it at a server with real
data. See [`scripts/test-mariadb.php`](scripts/test-mariadb.php) for the options.
