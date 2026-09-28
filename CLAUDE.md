# CLAUDE.md — AviNutra (avinutra.com)

Claude Code reads this file at the start of every session. Keep it current: when a convention changes, update this file in the same commit.

## What this project is
- **Public website** for AviNutra, a poultry-feed nutrition, consulting and ingredient-supply company, at `https://avinutra.com`.
- **Private CRM** at `/crm`, built as a **reusable Laravel/Filament package** in `packages/lite-crm`.

## Specifications (read before starting any phase)
- `docs/Prompt-AviNutra-Laravel-Build-v5.md` — technical build spec. It **wins on technical points**.
- `docs/AviNutra-Website-Content-v3.md` — website content and page spec.
- `CONTENT-GAPS.md` — missing facts, and where the site omits or uses neutral wording.

## Stack
- PHP 8.3 · latest stable Laravel supporting PHP 8.3 · Filament v4+ · Livewire · Alpine.js · Tailwind (Vite) · MariaDB 11.4 (`DB_CONNECTION=mariadb`) · Pest · Orchestra Testbench · Pint · Larastan level 5.
- Installed (Phase 1): Laravel 13, Filament 5, Livewire 4 (bundles Alpine — do not import Alpine separately), Tailwind 4, Pest 4 (Pest 5 needs PHP 8.4), Testbench 11, Larastan 3. `composer.json` pins `config.platform.php` to 8.3 so the lock file matches production.
- Fonts: `@fontsource-variable/inter` and `@fontsource-variable/source-serif-4`, imported in `resources/css/app.css`. Brand colours are Tailwind theme tokens there (`primary`, `primary-dark`, `accent`, `ink`, `muted`, `surface`, `line`).
- Production is a Hetzner VPS with HestiaCP: nginx, PHP-FPM 8.3, Node 20.20.2, own Exim mail server. No Supervisor: queues run from the scheduler via cron.

## Commands
```bash
composer install && npm ci
php artisan migrate --seed
npm run dev          # local assets
npm run build        # production assets
php artisan test     # Pest (host: tests/Feature, tests/Unit)
composer test:package   # Pest (package, via Testbench) = vendor/bin/pest --configuration packages/lite-crm/phpunit.xml
vendor/bin/pint      # code style (Laravel preset, PSR-12 compatible, + declare_strict_types)
vendor/bin/phpstan analyse --memory-limit=1G   # Larastan level 5 (phpstan.neon)
php artisan lite-crm:install | lite-crm:preset feed-additives | lite-crm:create-admin {email} | lite-crm:doctor
```

## Repository layout and THE BOUNDARY
- `packages/lite-crm/` — **generic, industry-neutral CRM package**:
  - namespace `LiteCrm\`, Composer name `gotrade/lite-crm`;
  - loaded through a Composer path repository;
  - must install into any Laravel + Filament site with `composer require` + `lite-crm:install` + panel registration.
- `app/`, `resources/`, `routes/` (host) — the AviNutra website, calculators, Website admin resources, and AviNutra-only code.
- `packages/lite-crm/presets/feed-additives.php` — the **only** place inside the package where feed or poultry terms may appear.
- `database/seeders/AviNutraSeeder.php` — AviNutra site settings and data.
- `app/Providers/Filament/CrmPanelProvider.php` — the host's `/crm` panel; registers `LiteCrmPlugin`. Host-only Filament resources go in `app/Filament/Resources` (Website group).
- Package internals: provider `LiteCrm\LiteCrmServiceProvider`, plugin `LiteCrm\LiteCrmPlugin`, translations `resources/lang/en/*.php` (namespace `lite-crm::`), tests in `packages/lite-crm/tests` (namespace `LiteCrm\Tests`; each Feature file calls `uses(TestCase::class)` so it runs from the host root and stand-alone). The boundary test is `packages/lite-crm/tests/Architecture/BoundaryTest.php`; add new forbidden terms there. The word "feed" is forbidden except in "feedback", so call the dashboard's activity feed an "activity stream".

## Rules for the package (enforced by an architecture test)
1. Never write "AviNutra", "poultry", "feed", "methionine" or other industry terms in the package's `src/`, `config/`, `database/`, `resources/` or `routes/`. Industry specifics belong in presets, custom-field definitions or the host app.
2. Industry fields are **custom fields**, not columns. Lookups (organisation types, pipelines/stages, document types and so on) are **database tables**.
3. Tables are prefixed `crm_`. Use standard SQL only; JSON functions must work on MariaDB/MySQL **and** SQLite. Once released, migrations are additive only.
4. All user-facing strings go in `resources/lang/en/*.php` (package).
5. Models are resolved through `config('lite-crm.models')` so hosts can extend them. The user model comes from config.
6. Each module can be toggled in `config('lite-crm.modules')`; code must not break when a module is off.
7. Every package feature gets Testbench + Pest tests that run without the host app.
8. Record user-visible changes in `packages/lite-crm/CHANGELOG.md`, and follow semantic versioning.

## Rules for website content (the site is live and public from day one)
- **Never invent facts:** no fabricated people, qualifications, clients, testimonials, statistics, certificates, partnerships, addresses, phone numbers or product availability.
- **Never show placeholders publicly.** Omit the element, or use neutral truthful wording. Then add the gap to `CONTENT-GAPS.md` **and** seed it as a CRM task.
- No manufacturer names, trademarks or logos publicly unless permission is recorded in the CRM. No importer names or transaction-level customs data publicly.
- No therapeutic claims ("prevents", "cures", "treats").
- Company-status wording comes from Settings, never hard-coded. "Pte. Ltd." never appears until `sg_incorporated = true`.
- Every published market figure shows its source and date.
- British English.

## Infrastructure rules
- No external CDNs, fonts, analytics or email APIs. Self-host assets; send mail through the own SMTP server.
- Private files live on the `local` (private) disk and are served only through signed, policy-checked routes.
- `/crm` is always `noindex` and disallowed in robots.txt.

## Calculators (host)
- Logic lives in PHP service classes under `app/Services/Calculators`; the UI is a Livewire component.
- Defaults and tax rates come from the database (CRM › Website › Calculator defaults), each with its source, date and approved-by.
- Reference tests must always pass:
  - Methionine value: 2.36/1.00 = 2.360; 1.76/0.65 = 2.708; 1.76/0.88 = 2.000; 3.79/0.909 = 4.169.
  - Landed cost: CFR 2.80 with the defaults → gross ≈ 3.454, net ≈ 2.882 USD/kg.

## Working method
- Build in the phases listed in v5 §G1. After each phase:
  1. run Pint, Larastan and Pest (host + package);
  2. commit with a clear message;
  3. give a short summary: **what was built, tests status, what is left, and any decision needed from the owner.**
- Ask before adding a new Composer or npm dependency that is not listed in v5.
- Never commit `.env`, credentials or uploaded files.
- Code style: PSR-12 via Pint; strict types in new PHP files; form requests or Filament validation for all input.

## Deployment
- `deploy.sh` runs on the server as the Hestia site user. It does: git pull, composer install (no dev), `npm ci && npm run build`, migrate, then caches and optimize.
- Document root is `public_html/public`. Cron runs `php8.3 artisan schedule:run` every minute. See v5 Part F.
