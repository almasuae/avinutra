# AviNutra — website and CRM

The public website for AviNutra (`https://avinutra.com`) and the private CRM at `/crm`.
The CRM is the reusable package in [`packages/lite-crm`](packages/lite-crm/README.md).

- Technical spec: [`docs/Prompt-AviNutra-Laravel-Build-v5.md`](docs/Prompt-AviNutra-Laravel-Build-v5.md)
- Content spec: [`docs/AviNutra-Website-Content-v3.md`](docs/AviNutra-Website-Content-v3.md) · design: [`docs/design/DESIGN-BRIEF.md`](docs/design/DESIGN-BRIEF.md)
- Missing facts: [`CONTENT-GAPS.md`](CONTENT-GAPS.md)
- Conventions for contributors and Claude Code: [`CLAUDE.md`](CLAUDE.md)

Contents: [Stack](#stack) · [Dependencies](#dependency-versions-and-why) · [Local setup](#local-setup) ·
[Quality checks](#quality-checks-run-after-every-phase) · [First install on the server](#first-install-on-the-server-hestiacp) ·
[.env](#env-on-the-server) · [Deploying updates](#deploying-updates) · [Users](#users) · [Mail and DNS](#mail-and-dns) ·
[Backups](#backups) · [Security headers](#security-headers) · [SEO](#seo) · [The APP_KEY](#the-app_key)

## Stack

PHP 8.3 · Laravel 13 · Filament 5 · Livewire 4 · Tailwind CSS 4 (Vite) · MariaDB 11.4
(SQLite for quick local work) · Pest 4 · Orchestra Testbench · Pint · Larastan level 5.
Node.js 20.19 or newer (`engines` in `package.json`); production runs 20.20.2.
Production: a Hetzner VPS with HestiaCP (nginx + PHP-FPM 8.3, Exim), no Supervisor —
queues run from the scheduler.

## Dependency versions and why

Composer's platform is pinned to PHP 8.3 (`config.platform.php`), so the lock file
always resolves to versions that run on the production server.

| Package | Version | Where | Why this version |
|---|---|---|---|
| `laravel/framework` | 13.x | host | Latest stable Laravel supporting PHP 8.3 (v5 §B). |
| `filament/filament` | 5.x | host + package | Latest stable Filament (v5 asks for v4+); requires Livewire 4. |
| `spatie/laravel-permission` | ^8.3 (8.3.0) | package | Latest release; supports PHP 8.3 and Laravel 12/13. Roles and permissions (v5 §D1). |
| `spatie/laravel-activitylog` | ^4.12.3 (4.12.3) | package | 5.x requires PHP 8.4, so 4.12.3 is the newest release that supports PHP 8.3 and Laravel 13. Audit trail. Revisit when production moves to PHP 8.4. |
| `spatie/laravel-settings` | ^3.9 (3.9.0) | host | Latest release; supports PHP 8.3 and Laravel 13. Site settings (v5 §A5). |
| `spatie/laravel-backup` | ^10.3 (10.3.3) | host | Named in v5 §F1/F3. Nightly backups of the database and uploaded files to the server's own disk. |
| `pestphp/pest` | 4.x | dev | Pest 5 requires PHP 8.4. |
| `orchestra/testbench` | 11.x | dev | Matches Laravel 13, for package tests without the host. |
| `larastan/larastan` | 3.x | dev | Static analysis at level 5. |
| `@fontsource-variable/inter`, `@fontsource-variable/source-serif-4` | 5.x | npm | Self-hosted fonts (no font CDN). |
| `@fontsource/lato` (900 only) | ^5.3 | npm | Headings: Lato Black, the owner's choice (Design Brief §3). |
| `sharp` | ^0.35.5 | npm (dev) | Approved in Design Brief §2. `npm run brand:build` makes every logo variant, icon and preview from `docs/design/logo-source.png`. The generated files are committed, so the server never runs it. |

The browser checks (`composer test:browser`) use **no extra package**: PHP's built-in
server, Node's built-in WebSocket (`node --experimental-websocket`) and an installed Edge
or Chrome.

## Local setup

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
touch database/database.sqlite     # or configure MariaDB in .env
php artisan migrate --seed         # roles, lists, the feed-additives preset, mailboxes; never users
php artisan lite-crm:create-admin you@example.com
npm run dev                        # or: npm run build
php artisan serve
```

The site runs at `http://localhost:8000`, the CRM at `http://localhost:8000/crm`.

There are no seeded users and no default passwords. The first admin is created only
with `php artisan lite-crm:create-admin {email}` (it asks for a password of at least
12 characters, or sends an invitation with `--invite`). Admins must set up
authenticator-app MFA at their first sign-in; further users are invited from
CRM › CRM settings › Users.

Website content is edited in the CRM under **Website**: Site settings (mailboxes, WhatsApp
numbers, the enquiry response time, and the company-status values used on quotations),
Page SEO, Articles, Team profiles, Glossary, FAQs and Calculator defaults (users with
`website.manage`). `php artisan db:seed` also seeds the Knowledge Centre launch content,
the calculator presets and one CRM task per open row of `CONTENT-GAPS.md`; run
`php artisan db:seed --class=ContentGapTaskSeeder` after `lite-crm:create-admin` to
assign those tasks to the first admin.

Brand files: `npm run brand:build` regenerates `public/brand/*`, `public/favicon.ico`
and `config/brand.php` from the master logo `docs/design/logo-source.png`, and fails if
any variant differs from the master (see CLAUDE.md). The design review page
`/dev/design` exists only when `APP_ENV=local`.

Background work: the scheduler (`php artisan schedule:run`, every minute via cron on
the server; `php artisan schedule:work` locally) sends digests and weekly reports,
drains the queue (`queue:work --stop-when-empty`), runs the backups and cleans the audit
log. Run `php artisan lite-crm:doctor` to check an installation.

## Quality checks (run after every phase)

```bash
vendor/bin/pint                                        # code style
vendor/bin/phpstan analyse --memory-limit=1G           # Larastan level 5
php artisan test                                       # host tests on SQLite
composer test:package                                  # package tests on SQLite (Testbench + Pest)
composer test:mariadb                                  # both suites on MariaDB 11.4
composer test:backup                                   # a real backup, restored into a second database
composer test:browser                                  # every public page at 390 px: no overflow, no CSP violations, no JS errors
composer audit && npm audit                            # known vulnerabilities
```

`composer test:mariadb` and `composer test:backup` use a MariaDB 11.4 on
`127.0.0.1:3307`. If nothing is listening there, they start the portable server in
`~/.local/mariadb114` (override with `MARIADB_HOME`) and stop it afterwards with SQL
`SHUTDOWN`. They drop and recreate their own test databases, so never point them at a
server with real data. `composer test:browser` runs against your local database.

---

## First install on the server (HestiaCP)

Paths below assume the Hestia user `avinutra` and the domain `avinutra.com`.

1. **DNS:** A records for `avinutra.com`, `www` and `mail.avinutra.com` → the server's IP.
2. **Hestia user and web domain:** create the user `avinutra` (bash shell). Add the web
   domain `avinutra.com` (alias `www.avinutra.com`) with backend template **PHP-FPM 8.3**,
   Let's Encrypt and **Force SSL**. Set the **Custom document root** to
   `public_html/public`, so `.env`, `vendor` and `storage` sit outside the served folder.
3. **Database:** Hestia › DB › add a MariaDB database and user (e.g. `avinutra_crm`).
   Hestia generates the password; you will put it in `.env` (below).
4. **Shell tools:** as root, `v-add-user-composer avinutra`. Check as the user:
   `php8.3 -v` and `node -v` (v20.20.2).
5. **Read-only deploy key** (the server can pull, never push):
   ```bash
   # as the user avinutra
   ssh-keygen -t ed25519 -C "avinutra-server-deploy" -f ~/.ssh/id_ed25519   # no passphrase
   cat ~/.ssh/id_ed25519.pub
   ```
   On GitHub: repository `almasuae/avinutra` › Settings › Deploy keys › Add deploy key,
   paste the public key, and leave **Allow write access switched OFF**. The private key
   stays on the server; never copy it anywhere else.
6. **Clone:** `public_html` must be empty. Look with `ls -A public_html`, delete only
   Hestia's default files you see there (e.g. `index.html`, `robots.txt`), then clone:
   ```bash
   cd /home/avinutra/web/avinutra.com
   ls -A public_html                      # must print nothing before you clone
   git clone git@github.com:almasuae/avinutra.git public_html
   cd public_html
   ```
7. **`.env`:** `cp .env.example .env`, then fill it in as described in [.env on the server](#env-on-the-server).
8. **Application key — once only:** `php8.3 artisan key:generate`, then copy the
   `APP_KEY` line from `.env` into your password manager (see [The APP_KEY](#the-app_key)).
9. **Install:**
   ```bash
   php8.3 ~/.composer/composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   php8.3 artisan migrate --force --seed        # roles, lists, preset, mailboxes, content; never users
   php8.3 artisan lite-crm:create-admin you@avinutra.com --name="Your Name"
   php8.3 artisan db:seed --class=ContentGapTaskSeeder --force   # assigns the gap tasks to you
   ./deploy.sh
   ```
10. **Cron** (Hestia › Cron jobs, as the user, every minute):
    ```
    cd /home/avinutra/web/avinutra.com/public_html && /usr/bin/php8.3 artisan schedule:run >> /dev/null 2>&1
    ```
11. **Check:** `php8.3 artisan lite-crm:doctor` (all OK within two minutes of setting up
    cron), `https://avinutra.com/.env` returns 404, and
    `https://avinutra.com/robots.txt` shows the `Sitemap:` line (see [SEO](#seo)).
12. **Security headers for static files** and **HSTS**: see [Security headers](#security-headers).

## `.env` on the server

Start from `.env.example`. **No password, token or key is in this repository; you
create each secret yourself** and type it into the server's `.env` (never commit it).

| Key | Production value | Where the value comes from |
|---|---|---|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` | — |
| `APP_URL` | `https://avinutra.com` | — |
| `APP_KEY` | generated | `php8.3 artisan key:generate`, once; keep a copy off-server |
| `DB_CONNECTION` | `mariadb` | — |
| `DB_HOST` / `DB_PORT` | `127.0.0.1` / `3306` | — |
| `DB_DATABASE` / `DB_USERNAME` | e.g. `avinutra_crm` | Hestia › DB (step 3) |
| `DB_PASSWORD` | secret | Hestia › DB, when you create the database |
| `SESSION_DRIVER` / `SESSION_SECURE_COOKIE` | `database` / `true` | — |
| `CACHE_STORE` / `QUEUE_CONNECTION` | `database` / `database` | — |
| `MAIL_MAILER` | `smtp` | — |
| `MAIL_HOST` / `MAIL_PORT` / `MAIL_SCHEME` | `mail.avinutra.com` / `587` / `smtp` | — |
| `MAIL_USERNAME` | `noreply@avinutra.com` | Hestia › Mail (see [Mail and DNS](#mail-and-dns)) |
| `MAIL_PASSWORD` | secret | the password you set for the `noreply@` mailbox in Hestia |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | `noreply@avinutra.com` / `"AviNutra"` | — |
| `LITE_CRM_PATH` | `crm` | — |
| `BACKUP_ARCHIVE_PASSWORD` | secret (recommended) | create a long password in your password manager; it encrypts the backup zips |
| `BACKUP_NOTIFICATION_EMAIL` | `info@avinutra.com` | who is told if a backup fails or is missing |
| `DB_DUMP_BINARY_PATH` | empty | set only if `mariadb-dump` is not on the PATH (e.g. `/usr/bin`) |
| `SECURITY_CSP` | `true` | — |

The enquiry API token is not an `.env` value: it is generated in CRM › CRM settings and
only its hash is stored. The API stays switched off unless `LITE_CRM_ENQUIRY_API=true`.

## Deploying updates

```bash
ssh avinutra@<server>
cd /home/avinutra/web/avinutra.com/public_html && ./deploy.sh
```

[`deploy.sh`](deploy.sh) puts the site into maintenance mode, fast-forwards to
`origin/main` (`git pull --ff-only`), runs `composer install --no-dev`, `npm ci && npm run
build`, `migrate --force`, rebuilds the caches (`config`, `route`, `view`, `event`,
`filament:optimize`), restarts the queue workers, brings the site up and runs
`lite-crm:doctor`.

It **never** runs `migrate:fresh`, `migrate:refresh`, `db:wipe` or seeders, never
rewrites git history (no `reset --hard`, `clean` or push), and never deletes `storage/`.
It refuses to run if the server has local changes or is not on `main`. If a step fails,
it stops and brings the site back up; read the output, fix the cause and run it again.
A test checks the script for forbidden commands.

## Users

- **First admin:** `php8.3 artisan lite-crm:create-admin {email}` — the only way; there
  are no seeded users or default passwords. `/crm` has no registration.
- **Everyone else:** CRM › CRM settings › Users › Invite (the invitation link expires
  after 72 hours). Choose the role (Admin, Manager, Commercial, Specialist, Partner,
  Viewer) and the user's time zone.
- **MFA:** Admins must set up an authenticator app at first sign-in (and keep the
  recovery codes). CRM settings can require MFA for everyone.
- **Leavers:** deactivate the user in CRM settings › Users; their records stay.

## Mail and DNS

All mail goes through the server's own Exim (no external e-mail service).

1. Hestia › Mail › add the mail domain `avinutra.com` with **DKIM** and Let's Encrypt
   for `mail.avinutra.com`.
2. Create the mailboxes `info@`, `nutrition@`, `sales@`, `partners@` and `noreply@`.
   The site sends from `noreply@` (its password goes into `MAIL_PASSWORD`).
3. DNS records:

   | Type | Name | Value |
   |---|---|---|
   | MX | `avinutra.com` | `mail.avinutra.com` |
   | TXT (SPF) | `avinutra.com` | `v=spf1 a mx ip4:<server-ip> -all` |
   | TXT (DKIM) | `mail._domainkey.avinutra.com` | the key Hestia shows for the mail domain |
   | TXT (DMARC) | `_dmarc.avinutra.com` | `v=DMARC1; p=quarantine; rua=mailto:info@avinutra.com` |

4. **Hetzner:** set the server's reverse DNS (PTR) to `mail.avinutra.com`. Test outbound
   port 25 with `nc -vz gmail-smtp-in.l.google.com 25`; if it is blocked, ask Hetzner
   support to unblock it.
5. Test: send an enquiry from the Contact page. The mailbox for its type receives the
   notification, and you receive the acknowledgement from `noreply@`.

## Backups

**What:** the MariaDB database and the uploaded files (`storage/app/private` — CRM
documents and enquiry uploads — and `storage/app/public` — team photographs). The code is
in git; `.env` is **not** in the backups (keep `APP_KEY` and the secrets in your password
manager).

**Where:** on the server only, in
`/home/avinutra/web/avinutra.com/public_html/storage/app/backups/AviNutra/`, one zip per
night (outside the web root; not reachable over HTTP). No external backup service is used.

**When:** `backup:run` nightly at 02:00, `backup:clean` on Sundays at 03:00 (keeps every
backup for 7 days, one a day for 30 days, one a week for 8 weeks, one a month for 6
months; never more than 5 GB), and `backup:monitor` daily at 08:00, which e-mails
`BACKUP_NOTIFICATION_EMAIL` if the newest backup is missing or older than a day. Failures
are e-mailed too. Set `BACKUP_ARCHIVE_PASSWORD` to encrypt the zips.

**Copy backups off the server** (do this regularly, e.g. weekly):

```bash
# From your own computer (macOS/Linux, or PowerShell on Windows 10+):
scp "avinutra@<server>:/home/avinutra/web/avinutra.com/public_html/storage/app/backups/AviNutra/*.zip" ./avinutra-backups/
# or keep a local folder in sync:
rsync -av avinutra@<server>:/home/avinutra/web/avinutra.com/public_html/storage/app/backups/AviNutra/ ./avinutra-backups/
```

On Windows you can also use WinSCP with the same SSH login. HestiaCP's own user backups
(Hestia › Backups, downloadable from the panel) are a useful second copy.

**Restore** (into a test database first — never over the live one without a backup of it):

1. Unzip the backup (with `BACKUP_ARCHIVE_PASSWORD` if set). It contains
   one `.sql` file in `db-dumps/` and the `storage/app/...` folders.
2. Create an empty database in Hestia (e.g. `avinutra_restore`) and load the dump:
   `mariadb -u avinutra_restore -p avinutra_restore < db-dumps/<the .sql file>`
3. Point a copy of the site at it (or inspect it) — with the **same `APP_KEY`**, or MFA and
   encrypted fields cannot be read.
4. To restore files, copy `storage/app/private` and `storage/app/public` back into place.

`composer test:backup` proves the whole cycle automatically: it runs a real
`backup:run`, restores the dump into a second database and compares every table.

## Security headers

**Set by Laravel** (`App\Http\Middleware\SecurityHeaders`) on every page and CRM response:

| Header | Value |
|---|---|
| `Content-Security-Policy` | `default-src 'self'`; nothing is loaded from other sites. Public pages: scripts need a per-request nonce (plus `'unsafe-eval'` for Alpine). CRM: `'self' 'unsafe-inline' 'unsafe-eval'` scripts (Filament's inline scripts). `frame-ancestors 'self'`, `object-src 'none'`, `upgrade-insecure-requests` on HTTPS. |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` — only over HTTPS in production |
| `X-Content-Type-Options` | `nosniff` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `X-Frame-Options` | `SAMEORIGIN` |
| `Cross-Origin-Opener-Policy` | `same-origin` |
| `Permissions-Policy` | camera, microphone, geolocation, payment, USB off |
| `X-Robots-Tag` | `noindex, nofollow` on everything under `/crm` (the CRM package) |

nginx serves the static files itself (`/build/*`, `/brand/*`, `favicon.ico`,
`site.webmanifest`) without asking Laravel, so **add these in HestiaCP**:

1. **HSTS for everything:** Hestia › Web › `avinutra.com` › SSL: tick **Enable HSTS**
   (or `v-add-web-domain-ssl-hsts avinutra avinutra.com`).
2. **nosniff and caching for static files:** create a custom nginx template. As root,
   copy `/usr/local/hestia/data/templates/web/nginx/php-fpm/default.tpl` and
   `default.stpl` to `avinutra.tpl` and `avinutra.stpl`, and in both files add, inside the
   `server { ... }` block:
   ```nginx
   server_tokens off;

   location ~* ^/(build|brand)/ {
       add_header X-Content-Type-Options "nosniff" always;
       add_header Cache-Control "public, max-age=31536000, immutable" always;
       try_files $uri =404;
   }
   ```
   Then select the template: Hestia › Web › `avinutra.com` › Advanced options › Web
   template **avinutra**. (`/build` files have hashed names and `/brand` files carry a
   `?v=` version, so long caching is safe.)
3. **robots.txt:** it is generated by Laravel. If the template has a
   `location = /robots.txt { ... }` block without `try_files`, change it to
   `try_files $uri /index.php?$query_string;` so the request reaches Laravel.

Check from any computer: `curl -sI https://avinutra.com/ | grep -iE "strict|content-security|nosniff"`
and `curl -sI https://avinutra.com/build/manifest.json` (or any file under `/build/assets/`).
Laravel sees HTTPS through PHP-FPM's `HTTPS` parameter, so no proxy settings are needed
with the nginx + PHP-FPM templates.

## SEO

- `https://avinutra.com/sitemap.xml` is generated: every public page, the service and
  ingredient-category pages, published products and articles, the glossary, and the team
  page once a profile is public. The CRM and unpublished content are never listed.
- `https://avinutra.com/robots.txt` is generated: `Disallow: /crm`, `/crm-api` and
  `/livewire`, plus the `Sitemap:` line. The CRM also sends `X-Robots-Tag: noindex`.
- Every page has a canonical URL, a description, Open Graph and Twitter tags and one H1.
  JSON-LD: Organization and WebSite (home), BreadcrumbList (inner pages), Article and
  Product. Titles and descriptions can be overridden per page in CRM › Website › Page SEO.
- After launch, add the site and the sitemap in Google Search Console (optional; it is
  your account, not part of the site).

## The APP_KEY

> **Production's `APP_KEY` must be stored safely off-server (for example in the
> owner's password manager), and must never be regenerated on an existing
> installation.**

The key in `.env` encrypts every user's MFA secret and recovery codes (and Laravel's
encrypted cookies and sessions). If it is lost or replaced:

- nobody can complete MFA, so every Admin is locked out of the CRM;
- a database backup restored with a different key has the same problem.

Therefore:

- Run `php artisan key:generate` **only once**, on the first installation, before any
  user exists. `deploy.sh` never touches it.
- Keep a copy of the key off-server, separately from the database backups, and check it
  whenever a backup is test-restored.
- If a key is ever compromised, rotate it with Laravel's `APP_PREVIOUS_KEYS` (the old key
  stays readable) instead of overwriting it, and ask users to set up MFA again.
