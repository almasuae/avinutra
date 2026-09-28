# Changelog

All notable changes to `gotrade/lite-crm` are recorded here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the
package uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- `lite-crm:install` (publish config, migrate, seed roles, permissions and neutral lists;
  idempotent) and `lite-crm:create-admin {email}` (interactive password or `--invite`).
  No users are ever seeded.
- Users: invite-only accounts with signed, expiring invitation links (resending
  invalidates older links); CRM profile in `crm_user_profiles` (job title, city,
  country, time zone, phone, WhatsApp, territory, digest opt-out, active flag);
  users are deactivated, never deleted.
- Security: authenticator-app MFA with recovery codes, required for Admins and
  optionally for everyone; 8-hour session limit; 12-character minimum password;
  dates shown in each user's time zone.
- Roles (Admin, Manager, Commercial, Specialist, Partner, Viewer) and a
  `{module}.{ability}` permission catalogue via spatie/laravel-permission; Admin-only
  soft deletion; editable role labels.
- Lists (`crm_lookups`), pipelines with stages, and tags, with neutral defaults.
- Custom-field engine: definitions, validation and casting on save, dynamic Filament
  form fields, table columns, filters and infolist entries; `HasCustomFields` and
  `HasTags` model traits.
- Audit log in `crm_activity_log` via spatie/laravel-activitylog, with a read-only
  screen; logins, invitations and role changes are recorded; MFA secrets are
  encrypted and never logged.
- `LiteCrm\Contracts\CrmUser` and the `InteractsWithCrm` trait for host user models.
- `LiteCrm::model()` resolves package models through `lite-crm.models`, keyed by the
  package class.
- Package skeleton: `LiteCrmServiceProvider`, `config/lite-crm.php`, English
  translations, `LiteCrmPlugin` with per-panel module overrides, `NoIndex` middleware,
  Testbench + Pest harness, and the industry-neutrality architecture test.
