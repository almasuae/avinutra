# CLAUDE.md — AviNutra (avinutra.com)

Claude Code reads this file at the start of every session. Keep it current: when a convention changes, update this file in the same commit.

## What this project is
- **Public website** for AviNutra, a poultry-feed nutrition, consulting and ingredient-supply company, at `https://avinutra.com`.
- **Private CRM** at `/crm`, built as a **reusable Laravel/Filament package** in `packages/lite-crm`.

## Specifications (read before starting any phase)
- `docs/Prompt-AviNutra-Laravel-Build-v5.md` — technical build spec. It **wins on technical points**.
- `docs/AviNutra-Website-Content-v3.md` — website content and page spec.
- `CONTENT-GAPS.md` — missing facts, and where the site omits or uses neutral wording.
- `docs/design/DESIGN-BRIEF.md` (v1.2) — brand and design. It **supersedes v5 §E1** and the visual-identity and imagery parts of Content Blueprint v3 (§4). `homepage-mockup.png` is the approved look (never reuse its pictures).
- **Logo master = `docs/design/logo-source.png`** (owner's decision, 29 Sep 2026; the SVG was only a PNG wrapper and was deleted — a vector is commissioned only for large-format print). `npm run brand:build` (`scripts/brand-build.mjs`, sharp) clears pixels with alpha < 40 and generates `public/brand/*` (PNG + WebP, 1x/2x), icons, `public/favicon.ico` and `config/brand.php` (sizes). Never hand-edit these; use `<x-brand.logo variant="compact|full|mark" />`. Variants are plain crops of the cleaned master — nothing inside a crop is ever removed (only the tagline crop for `logo-compact` and the outline cut for `logo-mark`); the build fails if any output differs from the master in its crop area or loses the i-dot.

## Stack
- PHP 8.3 · latest stable Laravel supporting PHP 8.3 · Filament v4+ · Livewire · Alpine.js · Tailwind (Vite) · MariaDB 11.4 (`DB_CONNECTION=mariadb`) · Pest · Orchestra Testbench · Pint · Larastan level 5.
- Installed (Phase 1): Laravel 13, Filament 5, Livewire 4 (bundles Alpine — do not import Alpine separately), Tailwind 4, Pest 4 (Pest 5 needs PHP 8.4), Testbench 11, Larastan 3. `composer.json` pins `config.platform.php` to 8.3 so the lock file matches production.
- Phase 2 added `spatie/laravel-permission` ^8.3 and `spatie/laravel-activitylog` ^4.12.3 (package; 5.x needs PHP 8.4) and `spatie/laravel-settings` ^3.9 (host). The README records versions and reasons.
- Phase 9 added `spatie/laravel-backup` ^10.3 (host; named in v5 §F1/F3): nightly database + `storage/app/private` + `storage/app/public` to the `backups` disk (`storage/app/backups`, server only; no external backup service).
- Node: `package.json` has `"engines": {"node": ">=20.19"}`; `npm run build` must keep working on Node 20.20.2 (production).
- Lighthouse (approved 30 Sep 2026): a **local dev tool only**, in its own `tools/lighthouse/package.json` (lighthouse 12.8.2, the newest that runs on Node 20) so the root `npm ci` in `deploy.sh` never installs it on the server. Run `cd tools/lighthouse && npm ci && node run.mjs http://127.0.0.1:8765/` against a local server with built assets, `APP_ENV=production`, `APP_DEBUG=false` and OPcache (`-d zend_extension=opcache -d opcache.enable_cli=1`). Reports go to `tools/lighthouse/reports/` (ignored). Keep the home page's mobile Performance at 90 or more.
- Browser test tooling (`composer test:browser`): **no Composer or npm package**. `scripts/test-browser.php` starts PHP's built-in server and runs `scripts/check-overflow.mjs` with Node's built-in WebSocket (`node --experimental-websocket`, Node ≥ 20.10), which drives an installed Edge or Chrome (`BROWSER_PATH` to override) over the DevTools protocol. Runs locally against the local database; not part of the server deploy.
- Fonts: `@fontsource-variable/inter` (body) and `@fontsource-variable/source-serif-4`, plus `@fontsource/lato` 900 for headings (`font-heading`, Lato Black — owner's choice), imported in `resources/css/app.css`. Brand colours are Tailwind theme tokens there (`green-900/800/700/500`, `orange-600/500/400`, `orange-cta` for white button text, `orange-text` for small orange text, `ink`, `muted`, `surface`, `line`); `AppSupportBrandPalette` mirrors them and a test keeps every text pair at WCAG AA. Utilities: `btn-primary`, `btn-secondary`, `btn-cta`, `eyebrow`, `accent-bar`, `card`.
- Public site (Phase 7): routes in `routes/web.php` (names used by `config/site.php`); data-driven pages use `PageController`, `IngredientController`, `KnowledgeController`; copy for service and category pages lives in `app/Content/*`. Link to a page only through `App\Support\SiteLinks::url()` (null until the route exists — no dead links). Forms: `<x-site.enquiry-form form="…">` with definitions in `App\Support\EnquiryForms` (v3 §8); the package form view is restyled in `resources/views/vendor/lite-crm/livewire/enquiry-form.blade.php`. Products come from CRM Products with `publish_on_website`; availability wording in `App\Support\ProductPresentation`. Website admin (CRM › Website): `SiteSettingsPage` and resources extending `App\Filament\Resources\WebsiteResource` (`website.manage`). Launch content: `KnowledgeSeeder`; gaps → CRM tasks: `ContentGapTaskSeeder` (parses the Open table of CONTENT-GAPS.md).
- Public layout: `<x-layouts.site>` with `<x-site.header>` / `<x-site.footer>` (dark green, logo on a white rounded panel — owner's choice); navigation, CTA, footer columns and legal links live in `config/site.php` and are linked only when their route exists. The company-status line comes from `SiteSettings::statusStatement()`. `/dev/design` (local only) is the design review page.
- Production is a Hetzner VPS with HestiaCP: nginx, PHP-FPM 8.3, Node 20.20.2, own Exim mail server. No Supervisor: queues run from the scheduler via cron.

## Commands
```bash
composer install && npm ci
php artisan migrate --seed
npm run dev          # local assets
npm run build        # production assets
php artisan test     # Pest (host: tests/Feature, tests/Unit), SQLite
composer test:package   # Pest (package, via Testbench), SQLite = vendor/bin/pest --configuration packages/lite-crm/phpunit.xml
composer test:mariadb   # host + package suites on MariaDB 11.4 (scripts/test-mariadb.php; port 3307, portable server in ~/.local/mariadb114)
composer test:browser   # headless Edge/Chrome, every public page at 390 px (OVERFLOW_WIDTH to change): no overflow, CSP violation, JS error, image missing after scrolling, or overlapping table cells (scripts/check-overflow.mjs; local DB)
composer test:backup    # real backup:run on MariaDB 11.4, restored into a second database, every table compared
vendor/bin/pint      # code style (Laravel preset, PSR-12 compatible, + declare_strict_types)
vendor/bin/phpstan analyse --memory-limit=1G   # Larastan level 5 (phpstan.neon)
php artisan lite-crm:install | lite-crm:preset feed-additives | lite-crm:create-admin {email} | lite-crm:doctor
```
- Local toolchain on the owner's Windows machine: portable PHP 8.3, Composer, Node 20.20.2 and MariaDB 11.4 live in `%USERPROFILE%\.local` (`php83`, `node20`, `mariadb114`); they are not on PATH, so prepend them in the shell.

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
- Users: the host `User` implements `LiteCrm\Contracts\CrmUser` (+ Filament `FilamentUser`, `HasAppAuthentication`, `HasAppAuthenticationRecovery`) and uses `LiteCrm\Concerns\InteractsWithCrm`. CRM user data lives in `crm_user_profiles`; the package never alters the host `users` table. In package code, type users as `Model&CrmUser` and check with `instanceof CrmUser`.
- Permissions are `{module}.{ability}` (see `LiteCrm\Support\Permissions`). Package Filament resources extend `LiteCrm\Filament\Resources\CrmResource` (view/manage permission; delete = Admin only, soft; never force-delete). spatie's roles/permissions/audit tables are `crm_`-prefixed (`lite-crm.prefix_third_party_tables`).
- Record models (Organisation, Contact, Activity, Task, Document; later Opportunity etc.) use `HasAuthors`, `HasVisibility` (`crmModule()` + `restrictToUser()`), `SoftDeletes`, `LogsCrmActivity`, and where relevant `HasRelatedRecords`, `HasTags`, `HasCustomFields`. Resolve spatie's `activities()` clash with `LogsCrmActivity { HasRelatedRecords::activities insteadof LogsCrmActivity; }` (audit trail = `auditLog()`). Add the model to `LiteCrm::recordModels()` / `morphMap()` and give it a policy extending `LiteCrm\Policies\RecordPolicy`. Filament resources extend `RecordResource` (queries are visibility-scoped). On untyped builders call scopes via `Visibility::apply($query, $user)` or `$query->scopes([...])` so Larastan stays clean.
- Enquiries: all intake goes through `LiteCrm\Enquiries\EnquiryIntake::capture()` (form component `lite-crm.enquiry-form`, `LiteCrm::captureEnquiry()`, `POST /crm-api/enquiries`). Spam (honeypot/timing) is stored with status Spam, never deleted; the per-IP limit refuses. The API is off by default; its token is only stored hashed (`ApiToken`). Convert = `EnquiryConverter` — it must never create duplicates (org: name + city; contact: e-mail). Local test page `/dev/enquiry-form` exists only when `APP_ENV=local`.
- Model events: never use `wasRecentlyCreated` inside a `saved` hook (it stays true for the instance's lifetime); use separate `created` and `updated` hooks with `wasChanged()`.
- Opportunities: stage logic lives in `Opportunity` model hooks (probability, closed_at, stage-change activity, events). Pipelines can be role-restricted (`visible_to_roles`, portable `whereJsonContains`). Quotation numbers come from `Support\Sequence` (locked counter); the host sets the contracting entity with `LiteCrm::resolveContractingEntityUsing()`.
- Shell pitfall on this machine: `printf`/`sed` in Git Bash eat backslashes in PHP namespaces (e.g. `\E` became an escape character). Write PHP files with the editor tools or Node, and check for 0x1B characters after generating code.
- Private documents: `lite-crm.documents` config; downloads only via `Document::temporaryDownloadUrl()` (signed, 5 min, authenticated route + policy).
- Custom fields: entity models use `HasCustomFields` (+ `$customFieldEntity`, optional `customFieldTypeKey()`); Filament screens use `LiteCrm\CustomFields\CustomFieldComponents`. Lists are rows in `crm_lookups` keyed by (`type`, `key`).
- Dashboard: `LiteCrm\Filament\Pages\CrmDashboard` (the plugin registers it; panels must not also register Filament's `Dashboard`). Widgets live in `src/Filament/Widgets`, use the `CrmWidget` concern (module check, visibility-scoped queries, page filters) and are listed in `CrmDashboard::WIDGETS` + `config('lite-crm.dashboard_widgets')`. Money across currencies goes through `LiteCrm\Support\Money` (rates in `crm_exchange_rates`); currencies/base currency/quotation prefix come from `LiteCrm::currencies()` etc., which read CRM settings first.
- Notifications that users should also see in the app use `Notifications\Concerns\InAppAndMail` (database + mail; mail only for on-demand routes). Scheduled work is registered in `LiteCrmServiceProvider::schedule()`; the host's `routes/console.php` drains the queue with `queue:work --stop-when-empty`.
- Import/export: Importer/Exporter classes in `src/ImportExport`, actions from `CrmExporters::importAction()/exportAction()`. Importers must never create duplicates (same rules as `EnquiryConverter`) and must check `Gate::denies('update', $existing)` before updating.
- Presets: PHP arrays in `packages/lite-crm/presets` (or `lite-crm.preset_paths`), applied idempotently by `LiteCrm\Presets\PresetLoader`. The host applies `feed-additives` and the enquiry mailboxes in `Database\Seeders\AviNutraSeeder` (called by `DatabaseSeeder`). The quotation contracting entity comes from SiteSettings via `AppServiceProvider::contractingEntity()`.

## CRM form layout (host and package; owner's decision, 30 Sep 2026)
- **Long text gets room.** A screen with long text (Textarea, MarkdownEditor, RichEditor) opens as a **full page** (List/Create/Edit pages, `$maxContentWidth = Width::Full`), or at least a **7xl modal**: wrap its create/edit/view actions in `LiteCrm\Filament\FormLayout::wide(...)`. Never a half-width slide-over (`->slideOver()` is not used).
- Full-page editors (e.g. Articles, Team profiles): two columns from 1024 px — main text about two thirds, details (status, dates, selects, byline, sources) one third — and one column below (`$schema->columns(['default' => 1, 'lg' => 3])`, groups with `columnSpan(['lg' => 2])` / `(['lg' => 1])`).
- **Textareas** start at 4 rows or more and grow (`FormLayout::configureDefaults()` sets `rows(4)->autosize()` for every textarea; never pass `->rows()` below 4). Longer notes may start higher (e.g. outline 8, bio 8).
- **Editors** are full width in their column and at least 400 px high (default); the main text of a full page uses `FormLayout::TALL_EDITOR_MIN_HEIGHT` (60% of the screen, min. 500 px). They grow with the content, and the toolbar stays visible while scrolling (`FormLayout::styles()`, added to the panel by `LiteCrmPlugin`).
- Repeaters with long values give each value a full-width row (e.g. a source: title, then URL and date below), never several tiny columns.
- Short fields (names, dates, selects) may stay in 2–4 columns.
- `FormLayout::violations()` checks these rules; `packages/lite-crm/tests/Architecture/FormLayoutTest.php` (package) and `tests/Feature/ArticleEditorTest.php` (host) run it.

## Rules for the package (enforced by an architecture test)
1. Never write "AviNutra", "poultry", "feed", "methionine" or other industry terms in the package's `src/`, `config/`, `database/`, `resources/` or `routes/`. Industry specifics belong in presets, custom-field definitions or the host app.
2. Industry fields are **custom fields**, not columns. Lookups (organisation types, pipelines/stages, document types and so on) are **database tables**.
3. Tables are prefixed `crm_`. Use standard SQL only; JSON functions must work on MariaDB/MySQL **and** SQLite. Once released, migrations are additive only.
4. All user-facing strings go in `resources/lang/en/*.php` (package).
5. Models are resolved through `LiteCrm::model(Lookup::class)`, which honours `config('lite-crm.models')` (keyed by package class, mapped to a subclass) so hosts can extend them. The user model comes from `LiteCrm::userModel()`.
6. Each module can be toggled in `config('lite-crm.modules')`; code must not break when a module is off.
7. Every package feature gets Testbench + Pest tests that run without the host app.
8. Record user-visible changes in `packages/lite-crm/CHANGELOG.md`, and follow semantic versioning.

## Rules for website content (the site is live and public from day one)
- **Never invent facts:** no fabricated people, qualifications, clients, testimonials, statistics, certificates, partnerships, addresses, phone numbers or product availability.
- **Never show placeholders publicly.** Omit the element, or use neutral truthful wording. Then add the gap to `CONTENT-GAPS.md` **and** seed it as a CRM task.
- No manufacturer names, trademarks or logos publicly unless permission is recorded in the CRM. No importer names or transaction-level customs data publicly.
- No therapeutic claims ("prevents", "cures", "treats").
- **No country references on the public website** (owner's decision, 29 Sep 2026): never Singapore, Pakistan/Pakistani, PKR, Karachi, Lahore, Punjab, Sindh, SBP, FBR or "Pte. Ltd." in public views, content, SEO fields, glossary, articles, legal pages or e-mails; no company-status statement, offices or partner on public pages. `tests/Feature/NoCountryReferencesTest.php` enforces it (only the ISO country picker's option list is exempt). The company-status Site settings (`sg_incorporated`, `legal_name`, `uen`, partner fields, `SiteSettings::statusStatement()`) are for CRM quotations only. The CRM itself is unchanged (territories, PKR, partner data).
- Every published market figure shows its source and date.
- British English.
- **Links:** every external link (another host) opens in a new tab with `target="_blank" rel="noopener noreferrer"`, a small "opens in new tab" icon and screen-reader text "(opens in new tab)". Internal links (relative, avinutra.com, www.avinutra.com, the APP_URL host; `config('site.internal_hosts')`) and `mailto:`/`tel:` links stay in the same tab, never with a `target`. `App\Support\ExternalLinks::process()` applies this to article bodies, and the `ProcessExternalLinks` middleware (web group, not `/crm`) to every public HTML page, so templates and content written in the CRM are covered. `tests/Feature/ExternalLinksTest.php` crawls every public page and fails on a violation.
- **Team profiles:** a person appears publicly (Team page, article bylines, JSON-LD) only when the profile is published with consent on file, its date and the signed consent document (`consent_document_path`, private `local` disk, `team-consents/`; downloaded only from CRM › Website › Team Profiles). `TeamProfile` refuses to save `is_published` without all three; the Articles editor offers only public profiles. Never seed sample people.
- Supplier intake wording: suppliers "introduce your company" / "share your company particulars"; the public page, thank-you text and acknowledgement never say "apply"/"application" (URL `/suppliers/apply` and the CRM type name stay).

## Infrastructure rules
- No external CDNs, fonts, analytics or email APIs. Self-host assets; send mail through the own SMTP server.
- Private files live on the `local` (private) disk and are served only through signed, policy-checked routes.
- `/crm` is always `noindex` and disallowed in robots.txt.

## Calculators (host)
- Logic lives in PHP service classes under `app/Services/Calculators`; the UI is a Livewire component.
- Defaults and tax rates come from the database (CRM › Website › Calculator defaults), each with its source, date and approved-by.
- Reference tests must always pass:
  - Methionine value: 2.36/1.00 = 2.360; 1.76/0.65 = 2.708; 1.76/0.70 = 2.514; 1.76/0.88 = 2.000; 3.79/0.909 = 4.169. MHA-FA presets are on a product basis: 0.65 ≈ 75%, 0.70 ≈ 80%, 0.88 = 100% equimolar.
  - Landed cost (universal inputs): CFR 2.80, insurance 0.35%, CIF valuation, no duties, sales tax 18% on CIF+duties (recoverable), other tax 2% on CIF+duties+sales tax (recoverable), charges 400,000 local units per container at 277.20 per USD, 20,000 kg → gross ≈ 3.454, net ≈ 2.882 USD/kg.
- Services: `App\Services\Calculators\MethionineValueCalculator`, `LandedCostCalculator`; Livewire: `App\Livewire\MethionineValue`, `LandedCost` (inputs in the URL; nothing stored). Tool 2 has no country and blank defaults; its "Example" values are illustrative only.

## Working method
- Build in the phases listed in v5 §G1. After each phase:
  1. run Pint, Larastan and Pest (host + package) on SQLite, **and `composer test:mariadb`**; report the MariaDB result in the summary;
  2. commit with a clear message, then push `main` to `origin` (GitHub, private);
  3. give a short summary: **what was built, tests status, what is left, and any decision needed from the owner.**
- Ask before adding a new Composer or npm dependency that is not listed in v5.
- Never commit `.env`, credentials or uploaded files.
- **Process safety:** never stop processes by name or image (no `taskkill /IM php.exe`, `Stop-Process -Name php`, `pkill php` or similar); the owner may be running their own server or queue worker. Record the process ID of anything you start (dev server, queue worker, headless browser) and stop only those IDs.
- Never seed users or default passwords. The first admin is created only with `lite-crm:create-admin`; `/crm` has no registration.
- Code style: PSR-12 via Pint; strict types in new PHP files; form requests or Filament validation for all input.

## Deployment
- `deploy.sh` runs on the server as the Hestia site user: `git pull --ff-only`, `composer install --no-dev`, `npm ci && npm run build`, `migrate --force`, caches, `filament:optimize`, `queue:restart`, `lite-crm:doctor`. It must never run destructive commands (no migrate:fresh/refresh/reset, db:wipe, seeders, reset --hard, git clean, push, or deleting storage/); `SeoAndSecurityTest` checks it. On failure it brings the site back up.
- Secrets never go in the repository or in messages: the README says where each secret is created and which `.env` key it goes in; the owner enters them.
- Security headers: `App\Http\Middleware\SecurityHeaders` (CSP: nonce-based scripts on the public site, `unsafe-inline` scripts in the CRM because of Filament; HSTS only on HTTPS in production). Static files served by nginx need the Hestia template additions in the README. No inline event handlers (`onclick`) in views: use `data-*` + `resources/js/app.js`. JSON-LD via `<x-seo.json-ld>` (it adds the nonce).
- SEO: `/robots.txt` and `/sitemap.xml` are generated by `SeoController` (no static robots.txt); `/crm` is disallowed, `noindex` and never in the sitemap. Maintenance and 500 pages are stand-alone (`errors/minimal-page`): no database, no Vite assets.
- The CRM uses `LiteCrm\Filament\LocalAvatarProvider` (initials drawn locally): never load avatars or anything else from external services.
- Server (live since 30 Sep 2026): HestiaCP with **nginx as a proxy in front of Apache** + PHP-FPM 8.3. The README install order is the one that worked: clone into an empty `public_html`, install, then set the custom document root; the `laravel-PHP-8_3` PHP-FPM template widens `open_basedir`; `disable_functions` is cleared in the CLI php.ini only (pcntl for the queue worker). README steps say who runs them ([panel] / [root] / [avinutra]). Never change anything on the server from here.
- `.env`: an empty value (`KEY=`) must fall back to the default — write `env('KEY') ?: default` (flags via the `$flag` helper in `config/security.php`); `tests/Unit/EnvFallbackTest.php` checks every key .env.example leaves empty or commented and every BACKUP_/SECURITY_/LITE_CRM_/DB_DUMP_ key. After any `.env` change the server needs `php artisan config:cache`.
- HSTS is sent by Laravel only (`SECURITY_HSTS`, default true); HSTS must not be enabled in HestiaCP.
- `lite-crm:doctor` also checks the queue worker's pcntl functions (CLI) and stuck scheduler locks (fix: `schedule:clear-cache`); the queue worker's overlap lock expires after 10 minutes.
- npm: every package in the root `package.json` must support Node 20.20.2 (`tests/Unit/NodeEnginesTest.php`); tools that need newer Node live in their own folder (`tools/…`).
- Print: the screen header/footer are hidden; the layout adds a print-only header (compact logo, site host, print date set by `app.js` on `beforeprint`) and, with `:print-notice="true"` (tools, articles), a one-sentence Technical Notice.
- Document root is `public_html/public`. Cron runs `php8.3 artisan schedule:run` every minute. See v5 Part F.
