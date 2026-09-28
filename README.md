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

## Local setup

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
touch database/database.sqlite     # or configure MariaDB in .env
php artisan migrate --seed
npm run dev                        # or: npm run build
php artisan serve
```

The site runs at `http://localhost:8000`, the CRM at `http://localhost:8000/crm`.
Composer's platform is pinned to PHP 8.3 so the lock file always matches production.

## Quality checks (run after every phase)

```bash
vendor/bin/pint                                        # code style
vendor/bin/phpstan analyse --memory-limit=1G           # Larastan level 5
php artisan test                                       # host tests (Pest)
composer test:package                                  # package tests (Testbench + Pest)
```
