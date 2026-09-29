# Changelog

All notable changes to `gotrade/lite-crm` are recorded here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the
package uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added (sidebar and dashboard)
- Sidebar groups set in `lite-crm.navigation.groups` (group key → label and member
  screens, in order). Defaults: Sales, Operations, Team (labels translatable; a group's
  `label` in config wins). Screens not listed use the plugin's default group.
- `feed-additives` preset: product custom fields for the website (species, form,
  function, nutritional function, country of origin, TDS date, reviewed applications).

### Changed (dashboard)
- "Activities by user" shows whole numbers only on its axis.
- Widgets re-ordered so neighbours have similar heights (won/lost next to expiring
  documents, the two charts together, the activity stream beside notices).

### Added (after the Phase 6 review)
- Stale exchange-rate warning on the pipeline widget and in the weekly report when a
  rate in use is older than `exchange_rates.stale_after_days` (default 30).
- `LiteCrm::resolveEnquiryResponseTimeUsing()` / `LiteCrm::enquiryResponseTime()`, so
  hosts can supply the acknowledgement's response-time promise from their settings.
- `LITE_CRM_DIGEST_HOUR` environment variable for `notifications.digest_hour`.

### Changed
- `enquiries.response_time` is now a whole phrase ("within one working day"); the
  acknowledgement line reads "We aim to reply :time.".

### Added (dashboard, notifications, import/export, presets)
- **Dashboard** (`CrmDashboard`) with eleven widgets: my day, enquiries, pipelines,
  won/lost, activity by user, expiring documents, price watch, samples & trials, team
  clock, notices and activity stream. Widgets can be switched off in
  `dashboard_widgets`. Owner/territory/period filters need the new `dashboard.filter`
  permission (Admins and Managers).
- **Exchange rates** (CRM settings) and `LiteCrm\Support\Money`: totals are converted
  to the base currency with the rate valid on the day. Currencies, base currency and
  quotation prefix are now CRM settings (config is the fallback).
- **In-app notifications** (bell) for task assigned, enquiry assigned and new enquiry,
  alongside the e-mails.
- **Daily digest** (`lite-crm:send-digests`, hourly, at each user's local digest hour,
  once a day, skipped when empty) and **weekly report** (`lite-crm:send-weekly-reports`,
  Mondays) with the `DocumentExpiring` event.
- **Import** (organisations, contacts, products, price log) and **export** (also
  opportunities), in CSV or XLSX. Imports never create duplicates and support "Update
  existing records". Exports are permission-checked, visibility-scoped, stored
  privately and audit-logged.
- **Presets:** `lite-crm:preset {name} [--list]`, `preset_paths`, and the
  `feed-additives` preset.
- `lite-crm:doctor` health check, the scheduler heartbeat, and daily audit-log
  clean-up (`audit_log_days`, default 730).
- New migrations: `crm_exchange_rates`, `crm_user_profiles.last_digest_on`, and
  Laravel's notifications/imports/exports tables when the host lacks them.

### Changed
- The plugin registers its own dashboard page. Hosts should remove Filament's
  `Dashboard` from the panel's `pages()`.

### Added (sales modules)
- **Products** with categories, specification table, packaging/storage/shelf life,
  suppliers with notes, availability and "publish on website" (guarded by the new
  `products.mark_available` and existing `website.manage` permissions, in the model).
- **Opportunities** in pipelines with stages, value, probability, expected close,
  next step and lost reason; stage changes logged as activities and firing
  `OpportunityStageChanged`, `OpportunityWon`, `OpportunityLost`; **kanban board** with
  drag-and-drop and a keyboard "Move to" menu; Mark won / Mark lost actions.
- Pipelines can be limited to roles (`visible_to_roles`); their opportunities are
  hidden from other roles (Admins excepted).
- **Samples**, **Trials** (dated consent before publication), **Quotations** (yearly
  gap-free numbering, contracting-entity resolver, Incoterms), **Price log**,
  **Announcements** (Markdown, pinned, role audience, sanitised) and **Decisions**
  (append-only except for Admins).
- Converting an enquiry can open an opportunity.
- New neutral activity type "Stage change".

### Fixed
- Model events on a freshly created record: the task-assignment e-mail could repeat
  after editing a new task, and assigning a just-captured enquiry sent no e-mail.

### Added (enquiries)
- **Enquiries** inbox (New · Mine · Open · All · Spam) with Assign, Start work, Close,
  Reopen, Mark as spam / Not spam, Log activity and **Convert**; first-response time;
  partners see only enquiries assigned to them; manual logging of phone enquiries.
- `<livewire:lite-crm.enquiry-form>`: configurable standard and extra fields, consent
  checkbox, optional uploads (stored as private documents), accessible markup.
- `LiteCrm::captureEnquiry()` and the `EnquiryCaptured` / `EnquiryAssigned` events.
- `POST /crm-api/enquiries`: off by default; bearer token generated, replaced and
  revoked in CRM › Settings (only its SHA-256 hash is stored); per-IP rate limit;
  same validation and spam checks as the form.
- Spam protection: honeypot, minimum fill time, per-IP limit; spam kept with the Spam
  status and the reason, without e-mails or files.
- E-mails: new enquiry to Admins/Managers and the enquiry type's mailbox (new
  "Mailbox" field on enquiry types), acknowledgement to the sender (response time
  only when configured), assignment to the assignee.
- Convert without duplicates: existing matches offered first (organisation by name,
  contact by e-mail); new records refused when an identical one exists (name + city /
  e-mail, case- and space-insensitive), even if hidden from the user.

### Fixed
- The task-assignment e-mail went to the previous assignee after a reassignment.

### Added (record modules)
- **Organisations** (type, status, territory, location, source, notes, tags, custom
  fields, and a dated "permission to name publicly" with an evidence document) and
  **Contacts** (organisation, job, channels, languages, time zone, legal basis and date
  for holding their data, tags, custom fields), each with list, create, view and edit
  pages and relation managers.
- **Activities** (polymorphic; type, when, duration, team and contact participants,
  summary, outcome, next step) with a "Log activity" action on every record page.
- **Tasks** (polymorphic; assignee, due date, priority, status, completion time) with
  My tasks · Team · Overdue · All tabs and a "Mark done" action; the `TaskAssigned`
  event and an e-mail to the assignee.
- **Documents** (polymorphic) on the private disk: type, issuer, certificate number,
  issue and expiry dates, verification (who, when, how), confidential flag; PDF/DOCX/
  XLSX/JPG/PNG up to 10 MB; downloads only through signed links valid for 5 minutes,
  behind login and a permission check, and written to the audit log.
- Every record has owner, created-by and updated-by, soft deletes and an audit trail.
- Record visibility: users without `{module}.view_all` (e.g. Partners) see only their
  own, assigned or territory records; confidential documents need
  `documents.view_confidential` (new permission, granted to Manager and Specialist).
- Model policies for the record models; nobody, not even an Admin, can hard-delete.
- Stable morph aliases (`crm_organisation`, `crm_contact` ...).
- `HasAuthors`, `HasVisibility` and `HasRelatedRecords` model traits;
  `LiteCrm\Support\Visibility::apply()`.

### Added (foundations)
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
