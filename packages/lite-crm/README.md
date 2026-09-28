# Lite CRM (`gotrade/lite-crm`)

A light, industry-neutral CRM for Laravel, delivered as a Filament plugin.
Industry-specific fields, pipelines and lists are loaded from **presets** and
**custom fields**, so the same package can serve any website on the same stack.

> **Status: 0.4.0-dev.** Foundations (installer, users and invitations, MFA, roles and
> permissions, lists, pipelines, tags, custom fields, audit log), the record modules
> (organisations, contacts, activities, tasks, documents) and enquiries (form
> component, PHP and HTTP intake, inbox, Convert) are in place. Opportunities and the
> remaining modules follow in the next releases.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Filament 5 (a panel in the host application)
- MariaDB/MySQL or SQLite
- A working mail transport (invitations are e-mailed)

Dependencies installed with the package: `spatie/laravel-permission` (^8.3) and
`spatie/laravel-activitylog` (^4.12.3, the newest release that supports PHP 8.3).

## Install into a Laravel + Filament site

### 1. Require the package

From a monorepo, use a Composer path repository:

```json
"repositories": [
    { "type": "path", "url": "packages/lite-crm", "options": { "symlink": true } }
]
```

```bash
composer require gotrade/lite-crm:@dev
```

Once the package lives in its own Git repository, require it by version tag instead.
No code changes are needed.

### 2. Prepare the user model

The CRM keeps its own data about users (profile, time zone, MFA secrets) in
`crm_user_profiles`; it never changes the host's `users` table. The user model needs
the `InteractsWithCrm` trait (which includes spatie's `HasRoles`), `Notifiable`, and
four interfaces:

```php
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Notifications\Notifiable;
use LiteCrm\Concerns\InteractsWithCrm;
use LiteCrm\Contracts\CrmUser;

class User extends Authenticatable implements CrmUser, FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    use InteractsWithCrm, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'crm' && $this->canAccessCrm();
    }
}
```

`canAccessCrm()` admits only users with an active CRM profile, an accepted invitation
and at least one role. If the user model is not `App\Models\User`, set
`LITE_CRM_USER_MODEL` or `lite-crm.user_model`.

### 3. Register the plugin on a panel

```php
use LiteCrm\LiteCrmPlugin;

return $panel
    ->id('crm')
    ->path(config('lite-crm.path', 'crm'))
    ->login()                                   // no ->registration(): the CRM is invite-only
    ->pages([Dashboard::class])
    ->plugin(
        LiteCrmPlugin::make()
            ->modules(config('lite-crm.modules')) // optional per-panel override
            ->navigationGroup('CRM'),
    )
    // ... the usual Filament middleware and authMiddleware
    ;
```

The plugin adds, on that panel:

- the settings screens (group "CRM settings"): Users, Roles, Lists, Pipelines, Tags,
  Custom fields, Audit log and CRM settings;
- a profile page (job title, city, country, time zone, phone, WhatsApp, digest opt-out,
  MFA set-up);
- authenticator-app MFA with recovery codes, **required for Admins**, and for everyone
  when an Admin switches it on in CRM settings;
- an 8-hour session limit, a 12-character minimum password (login is throttled to five
  attempts per minute by Filament);
- dates shown in each user's own time zone (stored in UTC);
- the signed invitation route `/{path}/invitation/{user}`;
- `X-Robots-Tag: noindex, nofollow` on every panel response. Also disallow the panel
  path in `robots.txt`.

### 4. Install

```bash
php artisan lite-crm:install
```

This publishes `config/lite-crm.php` (unless it exists; `--force` overwrites), runs the
migrations, and seeds roles, permissions and neutral lists. It never creates users.
It is safe to run again: nothing is duplicated and Admin edits are kept.

### 5. Create the first admin

```bash
php artisan lite-crm:create-admin owner@example.com --name="Full Name"
# or, to e-mail an invitation link instead of typing a password:
php artisan lite-crm:create-admin owner@example.com --name="Full Name" --invite
```

This is the only way to create the first admin: there are no seeded accounts or
default passwords. Further users are invited from CRM settings › Users.

### 6. Cron and mail

Run the scheduler every minute (`php artisan schedule:run`), and configure SMTP so
invitations can be delivered. Invitation e-mails are queued.

## Roles and permissions

Six roles are seeded (v5 §D1): **Admin** (everything, and the only role that can
delete), **Manager**, **Commercial**, **Specialist**, **Partner** (own and territory
records only; no exports, no price log) and **Viewer** (read-only, no exports).
Permissions are named `{module}.{ability}` (for example `contacts.view_all` or
`users.manage`). Admins adjust them in CRM settings › Roles, and rename the roles
users see in CRM settings. Re-running the installer adds new permissions to the roles
that should have them, but never re-adds a permission an Admin removed.

Deletion is soft and restorable, and only Admins can delete. Nothing can be
hard-deleted from the panel.

## Records

Each module appears in the panel's CRM navigation group when it is enabled in
`lite-crm.modules`, and its policy denies everything when it is off.

| Module | What it holds |
|---|---|
| Organisations | Companies with type, status, territory, location, tags, custom fields, and a dated permission (with evidence) before naming them publicly |
| Contacts | People, optionally linked to an organisation, with channels, languages, time zone and the legal basis for holding their data |
| Activities | Calls, meetings, e-mails ... on an organisation or contact, with team and contact participants ("Log activity" on every record page) |
| Tasks | To-dos with assignee, due date, priority and status; My tasks · Team · Overdue · All; the assignee is e-mailed |
| Documents | Files on the private disk with expiry, verification and a confidential flag |

Every record has an owner (defaulting to its creator), `created_by` and `updated_by`,
soft deletes and an audit trail.

**Who sees what.** Users with `{module}.view_all` see every record. Others (the
Partner role) see only records they own, records assigned to them, records in their
territory (organisations, and the contacts, activities, tasks and documents that
belong to them), and activities they took part in. Confidential documents are visible
only to their owner and to users with `documents.view_confidential`. Lists, pages,
relation managers and download links all apply the same rules.

**Documents** are stored on `lite-crm.documents.disk` (default `local`, which is
private). Accepted types are PDF, DOCX, XLSX, JPG and PNG, up to 10 MB, and the MIME
type is checked. A download link is signed and valid for 5 minutes; the route also
requires login and re-checks access, and each download is audit-logged.

**Adding a record type** (for a later module or a host): use `HasAuthors`,
`HasVisibility` (implement `crmModule()` and `restrictToUser()`), `HasRelatedRecords`,
`HasTags`, `HasCustomFields`, `SoftDeletes` and `LogsCrmActivity`. Because spatie's
trait also defines `activities()`, resolve the clash with
`LogsCrmActivity { HasRelatedRecords::activities insteadof LogsCrmActivity; }`; the
audit trail is then `auditLog()`. Register a policy extending `RecordPolicy`.

## Enquiries

Enquiries arrive from three places and land in the CRM inbox (CRM › Enquiries):

1. **The Livewire form component**, on any page of the host site:

   ```blade
   <livewire:lite-crm.enquiry-form
       type="general"
       :fields="['name' => ['required' => true], 'email' => ['required' => true], 'company', 'message' => ['required' => true],
                 'topic' => ['label' => 'Topic', 'type' => 'select', 'options' => ['a' => 'A', 'b' => 'B']]]"
       :uploads="3"
       privacy-url="/legal/privacy"
       :values="['topic' => 'a']"
   />
   ```

   Standard fields (`name`, `company`, `email`, `phone`, `country`, `city`, `message`)
   have their own columns; extra questions are stored in the enquiry's payload. A
   consent checkbox is always shown (linked to `privacy-url` when given). The
   component uses Tailwind classes, so add
   `@source '.../packages/lite-crm/resources/views/**/*.blade.php'` to the host's CSS,
   or publish and restyle the view.

2. **PHP**: `LiteCrm::captureEnquiry(array $data, string $type, ?string $sourceUrl)`
   returns the `Enquiry` and fires `EnquiryCaptured`.

3. **HTTP** (off by default): `POST /crm-api/enquiries` with
   `Authorization: Bearer <token>` and a JSON body such as
   `{"type": "general", "name": "…", "email": "…", "message": "…", "consent": true,
   "source_url": "…", "_honeypot": "", "_started_at": 1759140000}`. Switch it on with
   `LITE_CRM_ENQUIRY_API=true` and generate the token in CRM › Settings (only its
   SHA-256 hash is stored; "Replace token" revokes the old one). Responses: 202
   received, 401 bad token, 422 invalid, 429 too many, 404 switched off. The endpoint
   is also rate-limited per IP (`enquiry_api.requests_per_minute`).

**Spam**, without external services: a honeypot field, a minimum fill time
(`enquiries.min_fill_seconds`, default 3) and a per-IP limit (5 per 10 minutes).
Honeypot and timing failures are stored with the **Spam** status (tab "Spam", with
the reason) and trigger no e-mails; files sent with spam are discarded. Submissions
over the per-IP limit are refused and not stored, so a flood cannot fill the
database. The sender always sees the same thanks, so bots learn nothing.

**Uploads** follow the document rules: PDF, DOCX, XLSX, JPG or PNG, at most 10 MB each
and 5 per enquiry (MIME type checked), stored as documents on the private disk and
downloaded only through signed links.

**E-mails** (queued; they go out when the queue runs):
- new enquiry → users with the roles in `enquiries.notify_roles` (Admin, Manager) and
  the mailbox set on the enquiry type (CRM › Settings › Lists, "Mailbox");
- acknowledgement → the sender, from `MAIL_FROM_ADDRESS`; it mentions a response time
  only if `enquiries.response_time` is set;
- assignment → the assignee.

In development set `MAIL_MAILER=log` and the e-mails are written to
`storage/logs/laravel.log`; tests use `Notification::fake()`.

**The inbox**: tabs New · Mine · Open · All · Spam; actions Assign, Start work, Close,
Reopen, Mark as spam / Not spam, Log activity, and **Convert**. The first response
time is recorded when work starts, the enquiry is closed or converted, or an activity
is logged. Partners see only enquiries assigned to them.

**Convert** never creates duplicates. It lists existing organisations with the same
name (showing their city) and contacts with the same e-mail address, pre-selects the
best match, and creates nothing until the user confirms. Creating a new organisation
is refused when one with the same name **and** city exists, and a new contact when
one with the same e-mail exists (case and spaces ignored), even if that record is
hidden from the user. It then links the enquiry, logs a note on the contact or
organisation, and can add a follow-up task. (Creating an opportunity arrives with the
opportunities module.)

## Lists, pipelines and tags

Lookups are database rows, edited in CRM settings, never hard-coded:

- **Lists** (`crm_lookups`): organisation types and statuses, activity types, document
  types, lost reasons, territories, enquiry types, product categories. Each entry has a
  permanent `key` (used by presets and custom-field conditions) and an editable label.
- **Pipelines** (`crm_pipelines`, `crm_pipeline_stages`): stages with default
  probability and won/lost flags. A neutral "Sales" pipeline is seeded.
- **Tags** (`crm_tags`, `crm_taggables`), attached to records with the `HasTags` trait.

## Custom fields

Industry fields are custom fields, not columns. Admins define them in CRM settings ›
Custom fields, per entity (organisation, contact, opportunity, product, trial, sample):
key, label, type (text, long text, whole number, decimal, amount, date, yes/no, one
choice, several choices, percentage, web address), choices, section, help text,
required, "only for these types" (for example only for one organisation type), show in
tables, filterable, and order.

An entity model stores the values in a `custom` JSON column:

```php
use LiteCrm\Models\Concerns\HasCustomFields;

class Organisation extends Model
{
    use HasCustomFields;

    protected string $customFieldEntity = 'organisation';

    public function customFieldTypeKey(): ?string
    {
        return $this->type?->key; // drives "only for these types"
    }
}
```

Values are validated against the definitions whenever the model is saved (forms,
imports, API or code) and cast to their types. Values of fields that were switched
off are kept. Filament screens render the fields without code changes:

```php
use LiteCrm\CustomFields\CustomFieldComponents;

CustomFieldComponents::form('organisation', fn (Get $get) => /* current type key */);
CustomFieldComponents::tableColumns('organisation');
CustomFieldComponents::tableFilters('organisation');
CustomFieldComponents::infolist('organisation');
```

Filters use only JSON queries that work on MariaDB/MySQL and SQLite
(`where('custom->key', ...)`, `whereJsonContains`).

## Audit log

Every change to CRM data is recorded (who, when, before and after) in
`crm_activity_log`, together with logins, invitations and role changes. MFA secrets
are never logged, and they are stored encrypted. Admins and Managers can read the log
in CRM settings › Audit log.

## Configuration

| Key | Default | Purpose |
|---|---|---|
| `path` | `crm` (`LITE_CRM_PATH`) | Panel URL path |
| `table_prefix` | `crm_` | Prefix for every package table |
| `user_model` | `App\Models\User` | The host's user model (must implement `CrmUser`) |
| `models` | `[]` | Map a package model to a subclass, e.g. `Lookup::class => App\Models\Lookup::class` |
| `modules` | all `true` | Toggle each module per site |
| `currencies`, `base_currency`, `territories` | `['USD']`, `USD`, `[]` | Commercial settings |
| `enquiry_api` | disabled, `crm-api/enquiries`, 30/min | Token-protected HTTP intake endpoint (token managed in CRM › Settings) |
| `enquiries` | 3 s, 5 per 10 min, Admin + Manager, acknowledge, no response time, 5 uploads | Spam timing and per-IP limit, who is notified, acknowledgement, upload count |
| `notifications` | 08:00, 21 days, 60 days | Digest hour, stale-opportunity and expiry windows |
| `auth` | 12 chars, 480 min, 72 h, `admin`, `['admin']` | Password length, session limit, invitation expiry, super-admin role, roles that must use MFA |
| `documents` | `local`, `crm/documents`, 10 MB, five types, 5 min | Private disk, folder, size limit, accepted MIME types, download-link lifetime |
| `custom_field_entities` | six entities | Entities that accept custom fields |
| `custom_field_type_lookups` | organisation, product | The list that classifies each entity's records |
| `lookup_types` | eight lists | The lists shown in CRM settings › Lists |
| `prefix_third_party_tables` | `true` | Keep spatie's roles/permissions/audit tables `crm_`-prefixed; set `false` to share the host's own |

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
composer test:package      # SQLite
composer test:mariadb      # host and package suites on MariaDB
```

Stand-alone:

```bash
cd packages/lite-crm
composer install
vendor/bin/pest                             # SQLite
DB_CONNECTION=mariadb DB_PORT=3307 DB_DATABASE=lite_crm_test vendor/bin/pest
```
