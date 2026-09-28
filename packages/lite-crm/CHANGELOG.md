# Changelog

All notable changes to `gotrade/lite-crm` are recorded here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the
package uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

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
