# BUILD PROMPT — AviNutra.com
## Public website + reusable light CRM (Laravel · Filament · MariaDB · Hetzner/HestiaCP)
**Version 5.0 · 28 September 2026 · Supersedes v4**
**Built by Claude Code in VS Code, in a local Git repository; deployed to the Hetzner VPS.**

> **How to use:** open the local repository in VS Code and give this file to Claude Code. `Prompt-Feed-Website-v2.md` is the fuller reference for the website's content. Where the two differ, **this file wins**.

---

# PART A — CONTEXT AND GROUND RULES

## A1. The business

AviNutra (AviNutra.com) is a poultry-feed nutrition, consulting and ingredient-supply company.

- **Legal entity:** a Singapore company is intended but **not yet finalised or incorporated**.
- **Initial market:** Pakistan, served through a Pakistani importing/trading partner.
- **Future markets:** South Asia, Southeast Asia and MENA.

The distinction that shapes all content:

> Not "a trading company that happens to know nutrition", but **"a feed-nutrition company that uses international sourcing and trading capability to deliver nutrition solutions."**
>
> The website must persuade manufacturers to engage AviNutra, and persuade feed mills that AviNutra understands their business.

## A2. What is being built

1. **Public website** at `https://avinutra.com`. A **full, live site from day one.** Shortcomings are addressed gradually; there is no preview or holding mode.
2. **CRM** at `https://avinutra.com/crm`. Private and invite-only, used by the AviNutra team (in different cities and countries) to coordinate commercial work. It also manages the website's content and settings.
3. **The CRM must be reusable.** It is built as a **self-contained Laravel/Filament package** that can be installed into other websites on the same stack with minimal effort (Part C). **This is the most important architectural requirement.**

## A3. Non-negotiable rules

1. **Never invent facts.** Do not fabricate people, qualifications, clients, testimonials, statistics, certificates, partnerships, addresses, phone numbers or product availability.
2. **Because the site is public from day one, never show placeholders publicly.** Where a fact is missing:
   - **omit** the element (e.g. hide the team section until profiles exist; hide the WhatsApp button until a number is set); or
   - use **neutral, truthful wording** (e.g. "Details available on request", "Our team profiles will be published shortly").
3. **Record every omission** in `CONTENT-GAPS.md` at the repository root (page, what is missing, who supplies it). Also seed each omission as a **Task** in the CRM, assigned to the first admin.
4. **No manufacturer names, trademarks or logos** on the public site (e.g. MetAMINO®, Rhodimet®, Sandimet®), unless written permission is recorded in the CRM against that manufacturer. Inside the CRM, real names are fine.
5. **No therapeutic claims** ("prevents", "cures", "treats") on the public site. Use nutritional language only.
6. **No importer names or transaction-level customs data** on the public site.
7. **Company-status wording** always comes from Settings (A5), never hard-coded.
8. **Own infrastructure only.** Email goes through AviNutra's own mail server. No third-party CDNs for scripts or fonts; self-host everything. No external analytics at launch.

## A4. Environments

| Item | Local development | Production (Hetzner VPS) |
|---|---|---|
| Editor / agent | VS Code + Claude Code | — |
| Control panel | — | **HestiaCP** (nginx + PHP-FPM, Exim/Dovecot, Let's Encrypt, cron, backups) |
| PHP | 8.3 (e.g. Laravel Herd or a native install) | **8.3** (CLI 8.3.31) |
| Database | MariaDB 11.x locally (preferred) or SQLite for quick work | **MariaDB 11.4** — Laravel `mariadb` driver, utf8mb4_unicode_ci |
| Node.js | 20.19+ | **v20.20.2** (installed) — assets are built on the server during deploy |
| Mail | Log driver or Mailpit | Own mail server (Hestia Exim) via SMTP; low volume |
| Process manager | — | None assumed; queues run from the scheduler via cron |
| Git | Local repo → a private remote (GitHub/GitLab, or a bare repo on the server) | Server pulls from the remote |

## A5. Site settings (database; editable by Admins in the CRM)

| Setting | Initial value |
|---|---|
| `brand` | AviNutra |
| `tagline` | Where Feed Science Meets Reliable Supply. |
| `positioning` | Poultry Feed Experts · Nutrition Consultants · Feed Ingredient Suppliers |
| `emails` | info@, nutrition@, sales@, partners@, noreply@ — all @avinutra.com |
| `whatsapp_sales`, `whatsapp_nutrition` | null (buttons hidden until set) |
| `sg_incorporated` | false |
| `legal_name`, `uen`, `registered_office` | null |
| `pk_partner_name`, `pk_partner_city`, `pk_partner_role` | null, null, "importer of record and local sales partner" |

**Status statement** (shown in the footer, on About › Company, on Contact and on quotations):

- **When not incorporated and the partner name is set:** "Our international trading company is being established in Singapore. Until incorporation is complete, commercial activity in Pakistan is conducted through our partner, {pk_partner_name}, {pk_partner_city}."
- **When not incorporated and no partner name is set:** "Our international trading company is being established in Singapore, with commercial activity initially focused on Pakistan."
- **When incorporated:** "{legal_name} (UEN {uen}) is incorporated in Singapore." Add "In Pakistan, products are imported and supplied through {pk_partner_name}." when a partner name is set.
- "Pte. Ltd." never appears until `sg_incorporated = true`.

---

# PART B — TECHNOLOGY STACK

| Area | Choice |
|---|---|
| Framework | **Laravel**, latest stable release supporting PHP 8.3 (12.x; 13.x if stable and all packages below support it) |
| CRM UI | **Filament** (latest stable, v4+), delivered as a **Filament plugin** from the CRM package |
| Public front end | Blade + **Tailwind CSS** (via Vite) + **Alpine.js**; **Livewire** for calculators and forms |
| Roles / permissions | `spatie/laravel-permission` |
| Audit trail | `spatie/laravel-activitylog` |
| Settings | `spatie/laravel-settings` |
| PDF (Phase 2) | `barryvdh/laravel-dompdf` |
| Backups | Hestia backups, plus `spatie/laravel-backup` (database + private files, sent off-server if configured) |
| SEO | `spatie/laravel-sitemap`; meta and Open Graph tags; JSON-LD (Organization, Article, Product, FAQPage) |
| Fonts | Self-hosted Inter and Source Serif 4 (Fontsource via npm) |
| Quality | **Pest** (unit + feature), **Orchestra Testbench** for package tests, Laravel Pint, Larastan level 5 |

---

# PART C — ARCHITECTURE FOR REPLICATION (most important)

## C1. Principle

The CRM is a **generic, industry-neutral package**. Everything specific to AviNutra lives in a **preset** and in the **host application**, never inside the package code:
- industry fields, pipeline stages, organisation types and branding;
- the website, the calculators, and any feed or poultry terms.

Installing the CRM on another website means three steps: install the package, run its installer, and optionally load a preset.

## C2. Repository layout (monorepo)

```text
avinutra/                          ← host Laravel app (the AviNutra website)
├── app/                           ← website controllers, Livewire calculators, AviNutra-only code
├── packages/
│   └── lite-crm/                  ← THE REUSABLE CRM PACKAGE
│       ├── composer.json          ← name: "gotrade/lite-crm" (vendor name changeable), PSR-4 LiteCrm\
│       ├── config/lite-crm.php
│       ├── database/migrations/   ← all tables prefixed crm_
│       ├── database/seeders/      ← roles, permissions, neutral default lookups
│       ├── presets/               ← JSON/PHP presets, e.g. feed-additives.php
│       ├── resources/views/  lang/en/
│       ├── routes/                ← enquiry endpoint, signed document downloads
│       ├── src/
│       │   ├── LiteCrmServiceProvider.php
│       │   ├── LiteCrmPlugin.php  ← Filament plugin
│       │   ├── Models/  Filament/  Livewire/  Actions/  Events/  Notifications/  Policies/  Support/
│       │   └── Console/           ← install, preset, create-admin, doctor
│       ├── tests/                 ← Testbench + Pest; run without the host app
│       ├── README.md  CHANGELOG.md  UPGRADE.md
├── database/seeders/AviNutraSeeder.php   ← AviNutra-specific data (applies the preset + site settings)
├── CLAUDE.md                      ← conventions for Claude Code (C8)
├── CONTENT-GAPS.md
```

Load the package through a Composer **path repository** (`"url": "packages/lite-crm", "options": {"symlink": true}`). Later it can be moved to its own private Git repository and required by version tag, with no code changes.

## C3. How a host application uses the package

```php
// app/Providers/Filament/CrmPanelProvider.php (host)
return $panel
    ->id('crm')->path(config('lite-crm.path', 'crm'))
    ->login()->brandName(app(SiteSettings::class)->brand)
    ->colors(['primary' => '#1F4E5F'])
    ->plugin(LiteCrmPlugin::make()
        ->modules(config('lite-crm.modules'))         // enable/disable per site
        ->navigationGroup('CRM'))
    ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources'); // host-only resources, e.g. Website
```

The host's own Filament resources (Website pages, Articles, Calculator defaults, Site settings) appear in the same panel under a **Website** navigation group. They are not part of the package.

## C4. Package configuration (`config/lite-crm.php`)

- `path` (default `crm`), `table_prefix` (`crm_`), `user_model` (host `User`)
- `models` — class map, so a host can extend any model
- `modules` — toggles, all on by default:
  `organisations, contacts, enquiries, opportunities, activities, tasks, products, samples, trials, quotations, price_log, documents, announcements, decisions, dashboard, import_export`
- `currencies` (default `['USD']`; AviNutra: USD, PKR, SGD, EUR, CNY), `base_currency`, `territories`
- `enquiry_api` — enable/disable the HTTP intake endpoint, plus a token
- `notifications` — digest hour (default 08:00 local), stale-opportunity days (21), expiry warning days (60)

## C5. Configurable data instead of hard-coded logic

1. **Lookups are database tables, editable in the CRM** (CRM › Settings), and seeded neutrally by the package: organisation types, statuses, pipelines and their stages (with default probability and won/lost flags), activity types, document types, lost reasons, territories, tags.
2. **Custom fields** (the key to reuse):
   - A `crm_custom_fields` table defines extra fields per entity (organisation, contact, opportunity, product, trial, sample): `key`, `label`, `type` (text, textarea, number, decimal, currency, date, boolean, select, multiselect, percentage, url), `options`, `section`, `required`, `visible_for_types` (e.g. only for "Feed mill" organisations), `show_in_table`, `filterable`, `sort`.
   - Values are stored in a `custom` JSON column on each entity and validated against the definitions.
   - Filament forms, tables, filters, infolists and CSV import/export render these fields **dynamically**.
   - Only MariaDB/MySQL- and SQLite-compatible JSON functions may be used.
3. **Presets** bundle lookups and custom fields for an industry. `php artisan lite-crm:preset feed-additives` loads AviNutra's (C7). A preset is idempotent: running it twice creates no duplicates.

## C6. Integration points (so any website can feed the CRM)

- **In PHP:** `LiteCrm::captureEnquiry(array $data, string $type, ?string $sourceUrl)`. It returns the Enquiry and fires the `EnquiryCaptured` event.
- **Blade/Livewire component:** `<livewire:lite-crm.enquiry-form type="ask_nutritionist" :fields="[...]" />`. It includes built-in anti-spam (honeypot, minimum fill time, rate limit) and uploads.
- **HTTP endpoint** (optional, token-protected): `POST /crm-api/enquiries`. Non-Laravel sites (e.g. WordPress, static sites) can post enquiries to a CRM installed elsewhere.
- **Events** for host listeners: `EnquiryCaptured`, `OpportunityStageChanged`, `OpportunityWon`, `OpportunityLost`, `TaskAssigned`, `DocumentExpiring`.

## C7. Keeping the package clean

- **No reference to "AviNutra", poultry, feed or methionine inside `packages/lite-crm/`.** A Pest architecture test fails the build if these words appear in the package's `src/`, `config/`, `database/` or `resources/`. The only permitted exception is `presets/feed-additives.php`.
- All user-facing strings go through `lang/en/*.php`, so another language can be added later.
- **Standard SQL only;** migrations are additive once released; semantic versioning with a CHANGELOG and upgrade notes.
- Package tests run on SQLite, and on MariaDB in CI when available.

## C8. Installer and diagnostics

- `php artisan lite-crm:install` publishes the config, runs the migrations, seeds roles, permissions and neutral lookups, and prints the next steps.
- `php artisan lite-crm:create-admin {email}` creates an admin user.
- `php artisan lite-crm:preset {name}` loads an industry preset.
- `php artisan lite-crm:doctor` checks the queue/cron, mail settings, private-disk permissions and scheduler heartbeat.
- `packages/lite-crm/README.md` gives a complete **"Install into another Laravel + Filament site"** guide: requirements, Composer setup, panel registration, cron, mail and the enquiry form. Test the guide by installing the package into a fresh Laravel app inside the package tests (Testbench workbench).
- **`CLAUDE.md`** records the repository conventions for future Claude Code sessions: package vs host boundary, naming, test commands, where to add custom fields, and "never put industry terms in the package".

---

# PART D — CRM SPECIFICATION

## D1. Users, roles and access (package)

- **Invite-only**, with no public registration. Admins invite users by email; the user sets a password via a signed link.
- **Multi-factor authentication** (Filament's built-in TOTP) is mandatory for Admins. An Admin can make it mandatory for all users.
- **Login security:** throttling (5 attempts per minute), an 8-hour session limit, and a minimum password length of 12 characters.
- **User profile:** name, job title, city, country, **time zone**, phone, WhatsApp, territory. All dates are stored in UTC and displayed in each user's own time zone.
- **Roles** (permissions seeded by the installer; Admins can adjust them):

| Role | Access |
|---|---|
| Admin | Everything, including users, settings, lookups, custom fields and deletions |
| Manager | All records, website content and reports; no user/settings management |
| Commercial | Organisations, contacts, enquiries, opportunities, activities, tasks, samples, quotations, price log |
| Specialist | Everything Commercial has, plus trials and technical content; approves technical content. AviNutra label: "Nutrition" |
| Partner | Only records assigned to them, or in their territory; no exports, price log or supplier pipeline |
| Viewer | Read-only; no exports |

- Deletion is Admin-only and soft (restorable). Exports are logged.

## D2. Entities (package)

Every entity has: `owner_id`, `created_by`, `updated_by`, timestamps, soft deletes, activity logging, a `custom` JSON column where applicable, and tags.

- **Organisations:** `name`, `type` (lookup), `status` (lookup), `country`, `region/province`, `city`, `address`, `website`, `phone`, `email`, `territory`, `source`, `notes`, `permission_to_name_publicly` (bool + date + evidence document), plus custom fields.
- **Contacts:** `organisation_id`, names, `job_title`, `department`, `email`, `phone`, `whatsapp`, `preferred_channel`, `languages`, `time_zone`, `consent_basis`, `consent_date`, `notes`, plus custom fields.
- **Enquiries** (inbox):
  - fields: `type` (lookup), `name`, `company`, `email`, `phone`, `country`, `message`, `payload` (JSON), `attachments`, `source_url`, `status` (New · Assigned · In progress · Converted · Closed · Spam), `assignee_id`, `first_response_at`;
  - **Convert** action: creates or links an Organisation and Contact, and optionally an Opportunity, in one step.
- **Products:** `name`, `slug`, `category` (lookup), `description`, `specification` (repeater: parameter / value / unit), `packaging`, `storage`, `shelf_life`, `availability` (available · on request · information), `publish_on_website` (bool), `supplier_links` (many-to-many with organisations, with notes), plus custom fields.
- **Opportunities:**
  - fields: `pipeline`, `stage`, `organisation_id`, `contact_id`, `product_id`, `volume`, `unit`, `value`, `currency`, `probability`, `expected_close_date`, `next_step`, `next_step_date`, `lost_reason`, `notes`, plus custom fields;
  - a **Kanban board per pipeline** with drag-and-drop; each stage change is logged as an activity.
- **Activities** (polymorphic): `type`, `occurred_at`, `duration`, `participants` (users + contacts), `summary`, `outcome`, `next_step`. A "Log activity" action is available on every record.
- **Tasks** (polymorphic): `title`, `description`, `assignee`, `due_at`, `priority`, `status`. Views: My tasks · Team · Overdue.
- **Samples:** `organisation`, `product`, `supplier`, `lot_number`, `quantity`, `sent_on`, `courier`, `tracking`, `received_on`, `feedback`.
- **Trials:** `organisation`, `product`, `protocol` (text/file), `start/end`, `status`, `result_summary`, `consent_to_publish`, plus custom fields for KPIs.
- **Quotations** (record in the MVP; PDF in Phase 2):
  - fields: auto `number` (prefix configurable, e.g. `AVN-Q-2026-0001`), `organisation`, `contact`, `product`, `quantity`, `price`, `currency`, `incoterm`, `port`, `payment_terms`, `validity`, `status`, `contracting_entity`;
  - the contracting entity defaults from a host-provided resolver; for AviNutra, from the Settings in A5.
- **Price log:** `date`, `product`, `source organisation`, `type` (supplier offer · customs-derived · published assessment · market report), `basis` (EXW/FOB/CFR/CIF/landed), `location`, `price`, `currency`, `unit`, `validity`, `reference`, `confidence` (verified · reported · unconfirmed).
- **Documents** (polymorphic):
  - fields: `file` (private disk), `title`, `doc_type` (lookup), `issuer`, `certificate_number`, `issued_on`, `expires_on`, `verified` (+ by whom + how), `confidential`;
  - downloads only through policy-checked, signed, short-lived routes.
- **Announcements:** `title`, Markdown body, `pinned`, audience by role.
- **Decisions:** `decided_on`, `title`, `decision`, `rationale`, `decided_by`, `related_record`. Append-only for non-Admins.

## D3. Dashboard (package widgets; each can be enabled per site)

1. **My day** — my tasks due or overdue, and my next steps this week.
2. **Enquiries** — new and unassigned count, average first-response time, and the latest five.
3. **Pipeline by stage** — count and value, with a weighted total in the base currency (Admin-maintained exchange rates).
4. **Second pipeline summary.**
5. **Won and lost this quarter**, with lost reasons.
6. **Activity by user** over the last 30 days.
7. **Documents and certificates expiring** within the warning window.
8. **Price watch** — 12-month line chart for a selectable product, by basis.
9. **Samples and trials** in progress.
10. **Team clock** — every user's local time and city.
11. **Pinned announcements** and the latest decisions.
12. **Recent activity feed** (permission-aware).

Managers and Admins can filter by user, territory and date range.

## D4. Notifications (in-app + email via the host's SMTP)

- New enquiry: to Admins and Managers, plus the mailbox configured for that enquiry type.
- Assignment of an enquiry, task or opportunity: to the assignee.
- Daily digest at the configured local hour. The scheduler runs hourly and sends to users whose local time matches. Users can opt out.
- Weekly: expiring documents and stale opportunities, sent to their owners.

## D5. Import and export

- CSV/XLSX import for organisations, contacts, products and the price log, including custom fields, with column mapping, a validation report and duplicate detection (email; organisation name + city).
- Exports follow role rules and are logged.
- The first planned import is the Pakistani importer list from the customs analysis (supplied separately).

## D6. AviNutra preset (`presets/feed-additives.php`) — the only industry-specific CRM content

- **Organisation types:** Feed mill · Integrator / poultry producer · Manufacturer / supplier · Trader / distributor · Service provider · Bank / finance · Other.
- **Custom fields — organisation (Feed mill):** feed production (MT/month), species mix (broiler/layer/breeder %), methionine use (t/yr), current suppliers, import history notes.
- **Custom fields — organisation (Manufacturer/supplier):** products supplied, plant locations, capacity (kt/yr), Pakistan presence (A direct / B via trader / C none), current Pakistan distributor, FAMI-QS / GMP+ / Halal status.
- **Custom fields — trial:** species, bird count, control vs test, FCR, body-weight gain, mortality, cost per kg live weight.
- **Pipelines:**
  - *Sales (feed mills):* New → Qualified → Sample sent → Trial → Quotation → Negotiation → Won / Lost.
  - *Supply partnership (manufacturers):* Identified → Contacted → Documents received → Due diligence → Commercial terms → Agreement signed → Active / Dropped.
- **Document types:** TDS · SDS · COA · Specification · FAMI-QS · GMP+ · ISO · Halal · Certificate of origin · Registration · Agreement · NDA · Company profile.
- **Enquiry types:** general · ask a nutritionist · sourcing request · quotation · sample · document · supplier application.
- **Territories:** PK-Punjab, PK-Sindh, PK-KP, PK-Balochistan, PK-ICT, SG, CN, Other.
- **Currencies:** USD (base), PKR, SGD, EUR, CNY. **Quotation prefix:** AVN-Q.
- **Role label:** Specialist → "Nutrition".

---

# PART E — PUBLIC WEBSITE (host app; live from day one)

## E1. Brand and design

- **Wordmark:** "AviNutra" in Inter semibold, with "Avi" in the primary colour, plus a simple abstract SVG mark (a feather, or a leaf with a droplet).
- **No** cartoon poultry and **no** stock farm photos.
- **Palette:** Primary `#1F4E5F` · Primary-dark `#163A47` · Accent `#C8A24A` (sparingly) · Ink `#1C2226` · Muted `#5B6770` · Surface `#F6F8F8` · Line `#DDE3E5`.
- **Language:** British English.
- **Quality targets:** mobile-first; Lighthouse mobile ≥ 90; WCAG 2.1 AA.
- **Search engines:** the public site is indexable (robots.txt allows it, and there is a sitemap). `/crm` is always `noindex` and disallowed in robots.txt.

## E2. Pages (drafted copy; apply A3.2 wherever facts are missing)

**Navigation:** Nutrition Services · Ingredients · Tools · Knowledge · Quality · For Suppliers · About · Contact, plus a highlighted button **Ask a Nutritionist**. On mobile, a sticky WhatsApp button appears once a number is set.

| Route | Content |
|---|---|
| `/` | **Hero:** "Poultry Nutrition Expertise. Global Feed Ingredient Supply." Supporting line: "We combine practical poultry nutrition expertise with international sourcing of quality feed ingredients, to help feed manufacturers make better nutritional and commercial decisions." Buttons: Talk to a Nutrition Expert · Explore Ingredients · Use Feed Calculators. **Audience split:** feed mills / ingredient manufacturers. **What we do:** 4 cards (Nutrition Consulting, Feed Ingredients, Technical Evaluation, Strategic Sourcing). **"Feed Mills Need More Than a Supplier."** with the flow Nutrition → Product → Economics → Supply → Performance. **Why AviNutra:** Technical · Practical · Objective (commercial relationships disclosed) · Global · Transparent · Responsive. **Feature:** Precision Amino Acid Nutrition. **CTA band.** |
| `/nutrition-services` + 6 service pages | Formulation support · Ingredient evaluation · Product substitution · Feed economics · Supplier qualification · Technical trials. Also `/nutrition-services/feed-mills` (customer journey) and `/nutrition-services/request-sourcing` (form) |
| `/ingredients` | Categories and products taken from CRM Products where `publish_on_website = true`. Filters for category, form and function. The call to action depends on availability ("Request quotation" / "Request sourcing" / "Ask a nutritionist") |
| `/ingredients/amino-acids/methionine` | Flagship page: role of methionine; the sources (DL-Met 99%, L-Met 99%/90%, MHA-FA 88%, MHA-Ca); why specification matters; DL- vs L-Met; the MHA efficacy debate presented neutrally with the range of positions; cost per kg of effective methionine with a worked example; documentation checklist; embedded Tool 1 |
| `/ingredients/{category}/{product}` | Product template. Certifications are shown **only** if a verified document is attached in the CRM |
| `/tools` | Tools index: Tools 1 and 2 live (E3), others listed as "in development" |
| `/knowledge`, `/knowledge/{slug}`, `/knowledge/glossary` | Articles managed in the host's Website section (published only), each with author, reviewer, last-reviewed date and sources. Launch with the Glossary (20 terms) and, if needed, one short article written by the builder that needs no personal attribution: "How to Compare Methionine Sources: Cost per kg of Effective Methionine" |
| `/quality` | "Quality Is Part of the Product." Present the quality process as AviNutra's working method and principles, not as claims of audits already carried out |
| `/suppliers`, `/suppliers/apply` | Partner With Us: proposition, what we offer manufacturers, onboarding steps, supplier application with uploads. A Pakistan market overview is added once the owner supplies sourced aggregate figures |
| `/about`, `/about/company`, `/about/editorial-policy`, `/about/team` | The Team page appears only when at least one profile is published **with consent on file**. The Company page shows the status statement and a commercial-relationship disclosure |
| `/contact`, `/ask-a-nutritionist` | Enquiry routes and forms |
| `/legal/privacy`, `/legal/terms`, `/legal/cookies`, `/legal/technical-disclaimer` | Sensible drafts (Singapore PDPA, applicable Pakistani law, GDPR-aware). No tracking cookies at launch |

**Forms** use the package's enquiry component (C6). Each submission creates a CRM Enquiry, sends a notification through the own SMTP, and sends the visitor an acknowledgement from noreply@avinutra.com.
- **Uploads:** at most 10 MB each and 5 per form; PDF/DOCX/XLSX/JPG/PNG only; private disk; MIME type checked.

**Host-only admin resources** (in the CRM panel, under the "Website" group):
- Site settings (A5)
- Pages' SEO fields
- Articles
- Team profiles
- Glossary and FAQs
- Calculator defaults and Pakistan tax rates, each with `source`, `date`, `approved_by`

## E3. Calculators (host app; Livewire; logic in PHP services with Pest tests)

Common features:
- **"Show working"** toggle, **USD/PKR** switch, shareable query parameters and a print layout.
- While a default value's `approved_by` is empty, it is labelled "Indicative default".
- Every calculator ends with the **Technical Notice:**
  > These tools are provided for education and preliminary commercial evaluation. Results depend on product specifications, formulation assumptions, genetics, production conditions and other variables. Final formulation and feeding decisions should be made by qualified animal-nutrition professionals using validated product data. Tax and duty figures are indicative and are not tax advice.

**Tool 1 — Methionine Value Calculator** (`/tools/methionine-value`)
- Compares 2 to 4 products. **Value factor** = kg of DL-Met 99% replaced by 1 kg of the product.
- Presets:
  - DL-Met 99% = 1.00
  - L-Met 99% = 1.00
  - L-Met 90% = 0.909
  - MHA-FA 88%: the user must choose **0.65 / 0.75 / 0.88** (each with its source shown)
  - Custom
- Formulas:
  - `costPerKgEffective = price / factor`
  - `equivInclusion = refInclusion × refFactor / factor`
  - `costPerMtFeed = equivInclusion × price`
  - deltas per MT of feed, per month and per year, and in %
- **Required tests:** 2.36/1.00 = 2.360; 1.76/0.65 = **2.708**; 1.76/0.88 = **2.000**; 3.79/0.909 = **4.169**.

**Tool 2 — Landed Cost Calculator, Pakistan** (`/tools/landed-cost-pakistan`)
- Defaults:
  - FOB or CFR price, with freight
  - insurance 0.35%
  - PKR/USD 277.20 (SBP, 23 Sep 2026)
  - customs duty 0%; additional customs duty 0% (to be verified); regulatory duty 0%
  - sales tax 18%
  - withholding income tax 2% filer / 4% non-filer (note that commercial importers may face higher rates)
  - LC and bank charges %
  - clearing PKR 250,000 (estimate)
  - inland freight PKR 150,000 (estimate)
  - financing rate × days
  - 20,000 kg per container
- Formulas:
  - `CIF = CFR × (1 + ins)`
  - `duties = CIF × (cd + acd + rd)`
  - `ST = (CIF + duties) × st`
  - `IT = (CIF + duties + ST) × wht`
  - `fixed = (clearing + inland) / fx / kg`
  - `bank = CIF × lc`
  - `fin = CIF × rate × days / 365`
  - `gross = CIF + duties + ST + IT + fixed + bank + fin`
  - `net = gross − ST − IT`
- Output per kg and per MT, in USD and PKR, with a breakdown and "Rates last verified: {date}".
- **Required test:** CFR 2.80 with the defaults, no LC charge and no financing → gross ≈ **3.454**, net ≈ **2.882** USD/kg.

---

# PART F — DEPLOYMENT (Hetzner + HestiaCP)

## F1. One-time server setup

1. **DNS:** A records for `avinutra.com`, `www` and `mail.avinutra.com` → the server IP.
2. **Web domain:** `avinutra.com` (alias `www`) under a dedicated Hestia user (e.g. `avinutra`).
   - Backend template **PHP-FPM 8.3**.
   - Let's Encrypt with forced HTTPS.
3. **Document root:** set the **Custom document root** to `public_html/public`. The project lives in `public_html`, so `.env`, `vendor` and `storage` sit outside the served folder. Verify that `https://avinutra.com/.env` returns 404.
4. **Database:** create a MariaDB database and user (e.g. `avinutra_crm`) in Hestia.
5. **Shell:**
   - Give the user bash and an SSH key.
   - Make Composer available (`v-add-user-composer avinutra`).
   - Confirm `php8.3 -v`, and `node -v` → v20.20.2.
6. **Mail:**
   - Add the mail domain `avinutra.com` with DKIM.
   - Create `info@`, `nutrition@`, `sales@`, `partners@` and `noreply@`.
   - DNS records: SPF `v=spf1 a mx ip4:<server-ip> -all`, DKIM, DMARC `v=DMARC1; p=quarantine; rua=mailto:info@avinutra.com`.
   - Let's Encrypt for `mail.avinutra.com`.
   - **Hetzner:** set reverse DNS (PTR) to `mail.avinutra.com`. Test outbound port 25 (`nc -vz gmail-smtp-in.l.google.com 25`); if it is blocked, ask Hetzner support to unblock it.
7. **Cron** (as the site user), every minute:
   `cd /home/avinutra/web/avinutra.com/public_html && /usr/bin/php8.3 artisan schedule:run >> /dev/null 2>&1`
8. **Backups:** Hestia daily backups (keep ≥ 7), with an off-server destination (e.g. a Hetzner Storage Box via SFTP). `spatie/laravel-backup` runs nightly as a second copy.

## F2. `.env` essentials

```
APP_NAME=AviNutra
APP_ENV=production
APP_DEBUG=false
APP_URL=https://avinutra.com
APP_TIMEZONE=UTC
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=avinutra_crm
DB_USERNAME=avinutra_crm
DB_PASSWORD=********
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
MAIL_MAILER=smtp
MAIL_HOST=mail.avinutra.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=noreply@avinutra.com
MAIL_PASSWORD=********
MAIL_FROM_ADDRESS=noreply@avinutra.com
MAIL_FROM_NAME="AviNutra"
LITE_CRM_PATH=crm
```

## F3. Scheduler (no Supervisor)

Scheduled in `routes/console.php` and in the package's service provider:
- every minute: `queue:work --stop-when-empty --max-time=50`, with `withoutOverlapping()`;
- hourly: the digest dispatcher;
- weekly: the expiry and stale-opportunity reports;
- nightly: backup; weekly: `backup:clean`;
- activity-log pruning at 24 months;
- a scheduler heartbeat checked by `lite-crm:doctor`.

## F4. `deploy.sh` (run on the server as the site user)

```bash
set -e
cd /home/avinutra/web/avinutra.com/public_html
php8.3 artisan down --render="errors::503" || true
git pull --ff-only
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php8.3 artisan migrate --force
php8.3 artisan optimize:clear
php8.3 artisan config:cache && php8.3 artisan route:cache && php8.3 artisan view:cache && php8.3 artisan event:cache
php8.3 artisan filament:optimize
php8.3 artisan queue:restart
php8.3 artisan up
```

**First install:**
1. Empty `public_html` (remove Hestia's default index) and clone the remote into it.
2. Create `.env`, then run `php8.3 artisan key:generate`.
3. Run `lite-crm:install`, then `lite-crm:preset feed-additives`, then `db:seed --class=AviNutraSeeder`, then `lite-crm:create-admin {email}`.
4. Run `./deploy.sh`.

## F5. Security checklist

- `APP_DEBUG=false`, and `.env` is unreachable.
- Security headers: HSTS, nosniff, Referrer-Policy, Permissions-Policy, and a strict Content-Security-Policy (everything is self-hosted).
- MFA for Admins; login throttling.
- Private files are served only through signed, policy-checked routes.
- `composer audit` and `npm audit` are clean.
- A test restore of the backup is done before relying on it.

---

# PART G — BUILD ORDER AND ACCEPTANCE

## G1. Build order

Work in phases. After each phase: run Pint, Larastan and Pest; commit; and **summarise what was built and what is left.**

1. **Skeleton:** Laravel app + the `packages/lite-crm` path package + Filament panel via `LiteCrmPlugin`. Also Tailwind/Vite, Pest/Testbench, `CLAUDE.md`, and the architecture test from C7.
2. **Package foundations:** installer, config, users (invites, MFA, time zones), roles and policies, lookups, custom-field engine (definitions, validation, dynamic Filament rendering), audit log.
3. **Package entities:** Organisations, Contacts, Activities, Tasks, Documents.
4. **Enquiries:** `captureEnquiry`, the Livewire form component, the HTTP endpoint, notifications and the Convert action.
5. **Remaining modules:** Products, Opportunities (pipelines + kanban), Samples, Trials, Quotations (record), Price log, Announcements, Decisions.
6. **Dashboard, digests, import/export; the `feed-additives` preset; `lite-crm:doctor`; the package README.**
7. **Host website:** layout and brand, all E2 pages, host admin resources, `CONTENT-GAPS.md` + seeded CRM tasks.
8. **Calculators (E3)** with their tests.
9. **SEO, sitemap, security headers, backups, `deploy.sh`, and the host README.**

## G2. Acceptance checklist

- [ ] The public site is complete and navigable. **No placeholder text is visible anywhere public.** Every omission is listed in `CONTENT-GAPS.md` and exists as a CRM task.
- [ ] Every website form creates a CRM Enquiry, and emails are delivered through the own SMTP (notification + acknowledgement).
- [ ] `/crm` requires login, and MFA for Admins. A Partner user sees only assigned or territory records, no price log and no supplier pipeline, and cannot export.
- [ ] Convert-enquiry works. Kanban drag changes the stage and logs an activity. Dashboard widgets are correct and respect permissions.
- [ ] Dates show in each user's time zone, and the digest arrives at the configured local hour.
- [ ] Custom fields defined in the CRM appear in forms, tables, filters and imports without any code change.
- [ ] **Replication test:** in the Testbench workbench (or a fresh Laravel app), the package installs with `composer require` + `lite-crm:install` + panel registration only. A neutral CRM works with no preset. After `lite-crm:preset feed-additives`, the AviNutra fields appear.
- [ ] The architecture test confirms no AviNutra or feed terms in the package outside `presets/`.
- [ ] Private documents cannot be downloaded without authorisation.
- [ ] Calculator tests match the reference values. Pest, Larastan and Pint are all clean.
- [ ] `.env` is unreachable; security headers are present; Lighthouse mobile ≥ 90 on the home page.
- [ ] The backup restores successfully to a test database.
- [ ] Both READMEs are complete: the host (install, deploy, users, backups, mail DNS) and the package (install into another site).

---

# PART H — OWNER INPUTS (the site works without them; each fills a content gap)

- Team list for the CRM (name, email, role, city, country, time zone).
- Pakistani partner's legal name and city; Singapore incorporation details when available.
- WhatsApp Business numbers.
- Website team profiles, with signed consent.
- Products to mark available, once supply is secured.
- Written permissions from any manufacturer to be named publicly.
- Nutrition panel approval of calculator defaults.
- Sourced aggregate figures for a Pakistan market overview page.
- CSV of the Pakistani importers from the customs analysis, for the first CRM import.
