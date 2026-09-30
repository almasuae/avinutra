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
[Backups](#backups) · [Security headers](#security-headers) · [SEO and speed](#seo-and-speed) · [The APP_KEY](#the-app_key)

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
| `lighthouse` | 12.8.2 (exact) | `tools/lighthouse` (local only) | Approved 30 Sep 2026 as a local dev tool only. It lives in its own `tools/lighthouse/package.json`, so `deploy.sh` (which runs `npm ci` in the project root) never installs it on the server. 12.8.2 is the newest version that runs on Node 20; 13.x needs Node 22. |

Every npm package in the root `package.json` must support the server's Node 20.20.2 (a test
checks `package-lock.json`), so `deploy.sh`'s `npm ci` prints no EBADENGINE warnings.
Laravel's `concurrently` and `@laravel/multiplex` were removed (30 Sep 2026): they need
Node 22 and were only used by the optional `composer dev` shortcut; locally run
`npm run dev` and `php artisan serve` instead.

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
composer test:browser                                  # every public page at 390 px: no overflow, CSP violations, JS errors, missing images (after scrolling) or overlapping table cells; OVERFLOW_WIDTH=1440 for desktop
composer audit && npm audit                            # known vulnerabilities
```

Lighthouse (local only, not a phase check): `cd tools/lighthouse && npm ci`, start the site
with production settings and built assets, then `node run.mjs http://127.0.0.1:8765/`. It
prints the four scores (mobile settings) and writes the reports to `tools/lighthouse/reports/`
(not committed). The local PHP server does not compress responses; on the server nginx does
(see SEO and speed below).

`composer test:mariadb` and `composer test:backup` use a MariaDB 11.4 on
`127.0.0.1:3307`. If nothing is listening there, they start the portable server in
`~/.local/mariadb114` (override with `MARIADB_HOME`) and stop it afterwards with SQL
`SHUTDOWN`. They drop and recreate their own test databases, so never point them at a
server with real data. `composer test:browser` runs against your local database.

---

## First install on the server (HestiaCP)

Paths below assume the Hestia user `avinutra` and the domain `avinutra.com`. This order
is the one that worked on the live server (30 Sep 2026). Each step says who runs it:

- **[panel]** — the HestiaCP web panel, logged in as admin;
- **[root]** — an SSH session as root. Hestia's `v-…` commands need root; if the shell
  says "command not found", they are in `/usr/local/hestia/bin/` (run
  `export PATH=$PATH:/usr/local/hestia/bin` once per session);
- **[avinutra]** — an SSH session as the site user `avinutra` (never root for the
  application: files created by root cannot be written by PHP later).

The server runs **nginx as a proxy in front of Apache**, with PHP-FPM 8.3.

1. **DNS [your DNS provider]:** A records for `avinutra.com`, `www` and
   `mail.avinutra.com` → the server's IPv4 address.
2. **User and web domain [panel]:** create the user `avinutra` (bash shell). Add the web
   domain `avinutra.com` (alias `www.avinutra.com`) with Let's Encrypt and **Force SSL**.
   Leave the document root as it is for now (step 12 changes it). **Do not tick "Enable
   HSTS"**: Laravel already sends the HSTS header, and enabling it in Hestia as well sends
   it twice (see [Security headers](#security-headers)).
3. **Database [panel]:** DB › add a MariaDB database and user (e.g. `avinutra_crm`).
   Hestia generates the password; keep it for step 8.
4. **Composer [root]:** `v-add-user-composer avinutra`. Then **[avinutra]** check
   `php8.3 -v` and `node -v` (v20.20.2).
5. **pcntl for command-line PHP [root].** HestiaCP disables the `pcntl_*` functions in the
   CLI `php.ini`. The queue worker needs `pcntl_signal`, `pcntl_async_signals` and
   `pcntl_alarm`; without them it crashes at once, with no error anywhere, and e-mails pile
   up. This changes the **command-line** PHP only; PHP-FPM (the website) is not affected:
   ```bash
   cp /etc/php/8.3/cli/php.ini /etc/php/8.3/cli/php.ini.bak-$(date +%F)
   sed -i 's/^disable_functions *=.*/disable_functions =/' /etc/php/8.3/cli/php.ini
   php8.3 -r 'var_dump(function_exists("pcntl_async_signals"));'   # must print bool(true)
   ```
   `php8.3 artisan lite-crm:doctor` reports it as "Queue worker (pcntl)".
6. **PHP-FPM template with a wider open_basedir [root].** With a custom document root
   (step 12), Hestia limits PHP's `open_basedir` to `public_html/public`, so Laravel cannot
   read `../vendor`, `../.env` or `../storage` and **every page returns 500**. Create a
   copy of the PHP-FPM template that also allows the domain folder, and select it:
   ```bash
   cd /usr/local/hestia/data/templates/web/php-fpm/
   cp PHP-8_3.tpl laravel-PHP-8_3.tpl
   sed -i '/open_basedir/ s|= |= /home/%user%/web/%domain%:|' laravel-PHP-8_3.tpl
   v-change-web-domain-backend-tpl avinutra avinutra.com laravel-PHP-8_3
   ```
   The name must end in `PHP-8_3`, so Hestia still recognises the PHP version.
7. **Read-only deploy key [avinutra]** (the server can pull, never push):
   ```bash
   ssh-keygen -t ed25519 -C "avinutra-server-deploy" -f ~/.ssh/id_ed25519   # no passphrase
   cat ~/.ssh/id_ed25519.pub
   ```
   On GitHub: repository `almasuae/avinutra` › Settings › Deploy keys › Add deploy key,
   paste the public key, and leave **Allow write access switched OFF**. The private key
   stays on the server; never copy it anywhere else.
8. **Clone into an EMPTY `public_html` [avinutra]:**
   ```bash
   cd /home/avinutra/web/avinutra.com
   ls -A public_html          # delete only Hestia's default files you see (index.html, robots.txt …)
   ls -A public_html          # must now print nothing
   git clone git@github.com:almasuae/avinutra.git public_html
   cd public_html
   cp .env.example .env && chmod 600 .env
   ```
   Until step 12 the code sits in the served folder. Keep steps 8–12 in one sitting, and
   check at once that `curl -sI https://avinutra.com/.env` answers **403 or 404** (Hestia
   blocks hidden files); if it answers 200, do step 12 immediately.
9. **Fill in `.env` [avinutra]** — without a text editor, as described in
   [.env on the server](#env-on-the-server) (the `setenv` function).
10. **Install [avinutra]** — in this order (`key:generate` needs `vendor/`, so it comes
    after `composer install`):
    ```bash
    php8.3 ~/.composer/composer install --no-dev --optimize-autoloader
    php8.3 artisan key:generate        # ONCE only; then copy the APP_KEY line to your password manager
    npm ci && npm run build
    php8.3 artisan migrate --force --seed    # roles, lists, preset, mailboxes, content, gap tasks; never users
    php8.3 artisan lite-crm:create-admin you@avinutra.com --name="Your Name"
    php8.3 artisan db:seed --class=ContentGapTaskSeeder --force
    php8.3 artisan config:cache
    ```
    Why the last seeder again: `migrate --seed` creates one CRM task per open content gap,
    but no admin exists yet, so the tasks are unassigned. `create-admin` needs the roles
    that `migrate --seed` creates, so it cannot come first. Running `ContentGapTaskSeeder`
    after `create-admin` assigns the open tasks to you (it never duplicates them).
    **It also queues one notification per task (in the CRM and by e-mail).** If mail is not
    set up yet (see [Mail and DNS](#mail-and-dns)), clear them before step 13 with
    `php8.3 artisan queue:clear`; the tasks stay assigned.
11. **Check before going live [avinutra]:** `php8.3 artisan lite-crm:doctor` (the
    scheduler check stays red until step 13).
12. **Document root [panel]:** Web › `avinutra.com` › Advanced options › **Custom document
    root** `public_html/public`. Only now does the site answer; `.env`, `vendor` and
    `storage` are outside the served folder from here on. Open `https://avinutra.com` and
    `https://avinutra.com/crm`; a 500 on every page means step 6 is missing.
13. **Cron [root]** (every minute, as the user `avinutra`):
    ```bash
    v-add-cron-job avinutra '*' '*' '*' '*' '*' 'cd /home/avinutra/web/avinutra.com/public_html && /usr/bin/php8.3 artisan schedule:run >> /dev/null 2>&1'
    ```
14. **Final checks [avinutra]:** `php8.3 artisan lite-crm:doctor` (all OK within two minutes
    of step 13); `curl -sI https://avinutra.com/.env` and
    `curl -sI https://avinutra.com/storage/logs/laravel.log` answer 404;
    `https://avinutra.com/robots.txt` shows the `Sitemap:` line; and
    `curl -sI https://avinutra.com/ | grep -ic strict-transport` prints `1` (one HSTS header).
15. **Mail and DNS records:** see [Mail and DNS](#mail-and-dns). Later updates:
    [Deploying updates](#deploying-updates).

## `.env` on the server

Start from `.env.example`. **No password, token or key is in this repository; you
create each secret yourself** and type it into the server's `.env` (never commit it).

> **After ANY change to `.env`, run `php8.3 artisan config:cache`.** `deploy.sh` caches the
> configuration, so Laravel does not read `.env` again until the cache is rebuilt.

**Setting values without a text editor [avinutra].** Paste this function into the shell
(in `public_html`). It replaces a key's line — also a commented-out one, such as the
`# DB_HOST=` lines — or adds it, and writes the value in single quotes (so `$`, `#` and
spaces are taken literally; a value must not contain a single quote):

```bash
setenv() {
  local key="$1" value="$2" tmp
  case "$value" in *"'"*) echo "setenv: the value for $key contains a single quote" >&2; return 1;; esac
  tmp=$(mktemp) || return 1
  grep -vE "^#? *${key}=" .env > "$tmp"
  printf "%s='%s'\n" "$key" "$value" >> "$tmp"
  cat "$tmp" > .env && rm -f "$tmp"       # cat keeps the file's permissions (600)
}

setenv APP_ENV production
setenv APP_DEBUG false
setenv APP_URL https://avinutra.com
setenv DB_CONNECTION mariadb
setenv DB_HOST 127.0.0.1
setenv DB_PORT 3306
setenv DB_DATABASE avinutra_crm
setenv DB_USERNAME avinutra_crm
setenv SESSION_SECURE_COOKIE true
setenv MAIL_MAILER smtp
setenv MAIL_HOST mail.avinutra.com
setenv MAIL_PORT 587
setenv MAIL_SCHEME smtp
setenv MAIL_USERNAME noreply@avinutra.com
setenv MAIL_FROM_ADDRESS noreply@avinutra.com
setenv LOG_LEVEL warning

# Secrets: typed, never shown on screen or kept in the shell history.
read -rsp 'DB password: ' v && echo && setenv DB_PASSWORD "$v"; unset v
read -rsp 'noreply@ mailbox password: ' v && echo && setenv MAIL_PASSWORD "$v"; unset v
read -rsp 'Backup archive password: ' v && echo && setenv BACKUP_ARCHIVE_PASSWORD "$v"; unset v
```

**Check what is set — non-secret keys only [avinutra]:**

```bash
grep -vE '^[[:space:]]*(#|$)' .env | grep -vE '^(APP_KEY|DB_PASSWORD|MAIL_PASSWORD|BACKUP_ARCHIVE_PASSWORD|REDIS_PASSWORD|AWS_SECRET_ACCESS_KEY)='
for k in APP_KEY DB_PASSWORD MAIL_PASSWORD BACKUP_ARCHIVE_PASSWORD; do
  grep -qE "^$k='?[^'[:space:]]" .env && echo "$k: set" || echo "$k: EMPTY"
done
```

Then `php8.3 artisan config:cache`.

| Key | Production value | Where the value comes from |
|---|---|---|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` | — |
| `APP_URL` | `https://avinutra.com` | — |
| `APP_KEY` | generated | `php8.3 artisan key:generate`, once (install step 10); keep a copy off-server |
| `DB_CONNECTION` | `mariadb` | — |
| `DB_HOST` / `DB_PORT` | `127.0.0.1` / `3306` | — |
| `DB_DATABASE` / `DB_USERNAME` | e.g. `avinutra_crm` | Hestia › DB (install step 3) |
| `DB_PASSWORD` | secret | Hestia › DB, when you create the database |
| `SESSION_DRIVER` / `SESSION_SECURE_COOKIE` | `database` / `true` | — |
| `CACHE_STORE` / `QUEUE_CONNECTION` | `database` / `database` | already so in `.env.example` |
| `MAIL_MAILER` | `smtp` | — |
| `MAIL_HOST` / `MAIL_PORT` / `MAIL_SCHEME` | `mail.avinutra.com` / `587` / `smtp` | — |
| `MAIL_USERNAME` | `noreply@avinutra.com` | Hestia › Mail (see [Mail and DNS](#mail-and-dns)) |
| `MAIL_PASSWORD` | secret | the password you set for the `noreply@` mailbox in Hestia |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | `noreply@avinutra.com` / `AviNutra` | — |
| `LITE_CRM_PATH` | `crm` | — |
| `BACKUP_ARCHIVE_PASSWORD` | secret (recommended) | create a long password in your password manager; it encrypts the backup zips |
| `BACKUP_NOTIFICATION_EMAIL` | `info@avinutra.com` | who is told if a backup fails or is missing |
| `BACKUP_PATH` | not set | only to keep backups elsewhere; default `storage/app/backups` |
| `DB_DUMP_BINARY_PATH` | not set | only if `mariadb-dump` is not on the PATH (e.g. `/usr/bin`) |
| `SECURITY_CSP` / `SECURITY_HSTS` | `true` / `true` | Laravel sends both headers; do not enable HSTS in Hestia too |

An empty value (`KEY=`) counts as "not set" and falls back to the default; a test
checks this for every key above. The enquiry API token is not an `.env` value: it is
generated in CRM › CRM settings and only its hash is stored. The API stays switched off
unless `LITE_CRM_ENQUIRY_API=true`.

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

Changed only `.env`? Run `php8.3 artisan config:cache` (deploy.sh does it too). If e-mails
stop going out, run `php8.3 artisan lite-crm:doctor`: it reports disabled `pcntl`
functions for command-line PHP and scheduler locks left behind by a crashed command
(fix: `php8.3 artisan schedule:clear-cache`).

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

All mail goes through the server's own Exim (no external e-mail service). The domain's
DNS is kept at an external DNS provider, so records from HestiaCP are copied there.

1. **[panel]** Mail › add the mail domain `avinutra.com` with **DKIM** and Let's Encrypt
   for `mail.avinutra.com`.
2. **[panel]** Create the mailboxes `info@`, `nutrition@`, `sales@`, `partners@` and
   `noreply@`. The site sends from `noreply@` (its password goes into `MAIL_PASSWORD`).
3. **DNS records at your DNS provider:**

   | Type | Name | Value |
   |---|---|---|
   | A | `mail.avinutra.com` | the server's IPv4 address |
   | MX | `avinutra.com` | `mail.avinutra.com` (priority 10) |
   | TXT (SPF) | `avinutra.com` | `v=spf1 a mx ip4:<server-IPv4> -all` |
   | TXT (DKIM) | `mail._domainkey.avinutra.com` | copy it from **Hestia › Mail › `avinutra.com` › DNS records** (the `mail._domainkey` line), exactly as shown |
   | TXT (DMARC) | `_dmarc.avinutra.com` | `v=DMARC1; p=quarantine; pct=100` (as HestiaCP suggests) |

   This server sends mail over **IPv4 only** (Exim `disable_ipv6 = true`), so SPF needs
   only the `ip4:` entry; no `ip6:` is needed.
4. **Do not change the server's reverse DNS (PTR).** The server is shared with other
   domains and its PTR already names the server; changing it to `mail.avinutra.com` would
   affect every other domain's mail. SPF, DKIM and DMARC above are what receivers check for
   `avinutra.com`.
5. If mail does not leave the server at all, test outbound port 25 **[root]**:
   `nc -vz gmail-smtp-in.l.google.com 25`; if it is blocked, ask Hetzner support.
6. Test: send an enquiry from the Contact page. The mailbox for its type receives the
   notification, and you receive the acknowledgement from `noreply@`. An online checker
   (e.g. a DKIM/SPF/DMARC test mailbox) should report all three as passed.

After changing any `MAIL_*` value: `php8.3 artisan config:cache`.

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
are e-mailed too.

> **Backup password.** Set `BACKUP_ARCHIVE_PASSWORD` in the server's `.env` (you choose
> it; it encrypts every backup zip). **Store it safely off the server** — in your
> password manager, next to the `APP_KEY` — because **an encrypted backup cannot be
> opened or restored without it**. If you ever change it, keep the old password as long
> as backups made with it are kept.

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
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` — only over HTTPS in production; `SECURITY_HSTS=true` (default). Laravel is the only sender: do not enable HSTS in HestiaCP |
| `X-Content-Type-Options` | `nosniff` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `X-Frame-Options` | `SAMEORIGIN` |
| `Cross-Origin-Opener-Policy` | `same-origin` |
| `Permissions-Policy` | camera, microphone, geolocation, payment, USB off |
| `X-Robots-Tag` | `noindex, nofollow` on everything under `/crm` (the CRM package) |

**Script policy — owner's decision (29 Sep 2026):** the policy allows `'unsafe-eval'`
for scripts on the whole site, and `'unsafe-inline'` scripts **in the CRM only**.
Reason: Alpine.js (bundled with Livewire, used by the forms, calculators and CRM)
evaluates its expressions at run time, which needs `'unsafe-eval'`; Filament, which draws
the CRM, adds small inline scripts without a nonce, which need `'unsafe-inline'` there.
The public site's scripts still need a per-request nonce, and everywhere only this site
is allowed as a source, so no script can load from another website. Removing these
allowances would mean replacing Livewire/Alpine and Filament.

**How it works on this server:** nginx runs as a proxy in front of Apache (PHP-FPM 8.3).
Pages go through nginx to Apache and Laravel, which sets the headers above. Laravel sees
the requests as HTTPS without extra proxy settings (on the live server it sends HSTS, which
it does only for secure requests).

- **HSTS comes from Laravel only.** Do **not** tick "Enable HSTS" in Hestia (Web ›
  `avinutra.com` › SSL), or the header is sent twice. If it is already on, switch it off
  there **[panel]** (or **[root]** `v-delete-web-domain-ssl-hsts avinutra avinutra.com`).
  `SECURITY_HSTS=false` in `.env` would do the opposite (Laravel stops sending it); keep
  it `true`.
- **Static files** (`/build/*`, `/brand/*`, images, CSS, JavaScript) are served by nginx
  directly and are already cached by Hestia's default proxy template. They do not carry
  Laravel's headers, which is acceptable: they are not HTML.
- **Optional:** a custom nginx proxy template can add `X-Content-Type-Options: nosniff` and
  a one-year `immutable` cache to `/build` and `/brand` (their files have hashed names or a
  `?v=` version). Not required. If you want it **[root]**: copy
  `/usr/local/hestia/data/templates/web/nginx/default.tpl` and `default.stpl` to
  `avinutra.tpl` / `avinutra.stpl`, add inside the `server { … }` block
  ```nginx
  location ~* ^/(build|brand)/ {
      add_header X-Content-Type-Options "nosniff" always;
      add_header Cache-Control "public, max-age=31536000, immutable" always;
      try_files $uri @fallback;
  }
  ```
  and select it in Hestia › Web › `avinutra.com` › Advanced options › Proxy template.
- `robots.txt` and `sitemap.xml` are generated by Laravel; there is no static file, so
  nginx passes the request on.

Check from any computer:
`curl -sI https://avinutra.com/ | grep -iE "strict|content-security|nosniff"` (each header
exactly once).

## SEO and speed

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
- **Compression:** HestiaCP's nginx proxy switches gzip on for HTML, CSS and JavaScript.
  After the first deploy, check it: `curl -sI -H 'Accept-Encoding: gzip' https://avinutra.com/ | grep -i content-encoding`
  should print `content-encoding: gzip` (also try a `/build/assets/*.css` URL). If it prints
  nothing, enable gzip in the domain's nginx proxy template in HestiaCP. Lighthouse (mobile,
  local, 30 Sep 2026): Performance 96, Accessibility 100, Best Practices 100, SEO 100; its only
  remaining suggestion was text compression, which nginx provides.

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
