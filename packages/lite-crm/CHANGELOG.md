# Changelog

All notable changes to `gotrade/lite-crm` are recorded here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the
package uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Package skeleton: `LiteCrmServiceProvider`, `config/lite-crm.php` (path, table prefix,
  user model, model map, module toggles, currencies, enquiry API, notification timings)
  and English translations.
- `LiteCrmPlugin` Filament plugin with per-panel module overrides and a configurable
  navigation group.
- `NoIndex` middleware: every panel response carries `X-Robots-Tag: noindex, nofollow`.
- Testbench + Pest test harness, and an architecture test that keeps industry terms
  out of the package.
