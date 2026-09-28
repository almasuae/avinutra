# Lite CRM (`gotrade/lite-crm`)

A light, industry-neutral CRM for Laravel, delivered as a Filament plugin.
Industry-specific fields, pipelines and lookups are loaded from **presets** and
**custom fields**, so the same package can serve any website on the same stack.

> **Status: 0.1.0-dev (skeleton).** The service provider, configuration, Filament
> plugin and test harness are in place. The modules listed below are built in the
> following phases, and this README grows into the full installation guide as
> they land.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Filament 5 (a panel in the host application)

## Install into a Laravel + Filament site

1. **Require the package.** From a monorepo, use a Composer path repository:

   ```json
   "repositories": [
       { "type": "path", "url": "packages/lite-crm", "options": { "symlink": true } }
   ]
   ```

   ```bash
   composer require gotrade/lite-crm:@dev
   ```

   Once the package lives in its own Git repository, require it by version tag
   instead. No code changes are needed.

2. **Register the plugin in a Filament panel:**

   ```php
   use LiteCrm\LiteCrmPlugin;

   return $panel
       ->id('crm')
       ->path(config('lite-crm.path', 'crm'))
       ->login()
       ->plugin(
           LiteCrmPlugin::make()
               ->modules(config('lite-crm.modules')) // optional per-panel override
               ->navigationGroup('CRM'),
       );
   ```

3. **Optionally publish the configuration and translations:**

   ```bash
   php artisan vendor:publish --tag=lite-crm-config
   php artisan vendor:publish --tag=lite-crm-lang
   ```

The `lite-crm:install`, `lite-crm:preset`, `lite-crm:create-admin` and
`lite-crm:doctor` commands arrive in later releases.

## What the plugin does today

- Merges `config/lite-crm.php` (path, table prefix, user model, model map,
  module toggles, currencies, enquiry API, notification timings).
- Adds an `X-Robots-Tag: noindex, nofollow` header to every panel response.
  Hosts should also disallow the panel path in `robots.txt`.

## Configuration

| Key | Default | Purpose |
|---|---|---|
| `path` | `crm` (`LITE_CRM_PATH`) | Panel URL path |
| `table_prefix` | `crm_` | Prefix for every package table |
| `user_model` | `App\Models\User` | The host's user model |
| `models` | `[]` | Class map, so a host can extend package models |
| `modules` | all `true` | Toggle each module per site |
| `currencies`, `base_currency`, `territories` | `['USD']`, `USD`, `[]` | Commercial settings |
| `enquiry_api` | disabled | Token-protected HTTP intake endpoint |
| `notifications` | 08:00, 21 days, 60 days | Digest hour, stale-opportunity and expiry windows |

## Rules for contributors

- The package is **industry-neutral**. An architecture test fails the build if
  industry or host terms appear in `src/`, `config/`, `database/`, `resources/` or
  `routes/`. Industry content belongs in `presets/` or the host app.
- All user-facing strings go in `resources/lang/en/*.php`.
- Tables are prefixed `crm_`; migrations are additive once released; only JSON
  functions that work on MariaDB/MySQL **and** SQLite may be used.
- Every feature has Testbench + Pest tests that run without a host app.
- Record user-visible changes in `CHANGELOG.md` (semantic versioning).

## Testing

Inside a monorepo host (uses the host's `vendor/`):

```bash
vendor/bin/pest --configuration packages/lite-crm/phpunit.xml
# or
vendor/bin/pest packages/lite-crm/tests
```

Stand-alone:

```bash
cd packages/lite-crm
composer install
vendor/bin/pest
```

Tests run against in-memory SQLite through Orchestra Testbench.
