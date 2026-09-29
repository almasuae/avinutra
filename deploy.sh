#!/usr/bin/env bash
#
# AviNutra deploy (v5 §F4). Run on the server as the Hestia site user:
#
#   cd /home/avinutra/web/avinutra.com/public_html && ./deploy.sh
#
# Safe by design — this script never:
#   - resets or wipes the database (no migrate:fresh / refresh / reset, no db:wipe, no seeding);
#   - rewrites or force-pushes git history (no reset --hard, no clean, no push);
#   - deletes storage/ or uploaded files.
# It only fast-forwards to the latest main, installs, builds, migrates forward and caches.
# If a step fails, the script stops and the site is brought back up.

set -Eeuo pipefail

APP_DIR="${APP_DIR:-/home/avinutra/web/avinutra.com/public_html}"
PHP="${PHP_BIN:-php8.3}"
BRANCH="${DEPLOY_BRANCH:-main}"

if [ -n "${COMPOSER_CMD:-}" ]; then
    read -r -a COMPOSER_RUN <<< "$COMPOSER_CMD"
elif [ -x "$HOME/.composer/composer" ]; then
    COMPOSER_RUN=("$PHP" "$HOME/.composer/composer")   # installed by v-add-user-composer
else
    COMPOSER_RUN=("$PHP" "$(command -v composer)")
fi

cd "$APP_DIR"

if [ ! -f .env ]; then
    echo "No .env in $APP_DIR — see README, 'First install'." >&2
    exit 1
fi

if [ "$(git rev-parse --abbrev-ref HEAD)" != "$BRANCH" ]; then
    echo "The server is not on the $BRANCH branch; refusing to deploy." >&2
    exit 1
fi

if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "There are local changes on the server; refusing to deploy. Review them with 'git status'." >&2
    git status --short >&2
    exit 1
fi

finish() {
    local status=$?
    "$PHP" artisan up >/dev/null 2>&1 || true
    if [ "$status" -ne 0 ]; then
        echo "" >&2
        echo "DEPLOY FAILED (exit $status). The site is up again; read the output above before retrying." >&2
    fi
}
trap finish EXIT

echo "== Maintenance mode"
"$PHP" artisan down --render="errors::503" --retry=60 || true

echo "== Code: fast-forward to origin/$BRANCH"
git pull --ff-only origin "$BRANCH"

echo "== PHP dependencies"
"${COMPOSER_RUN[@]}" install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "== Front-end build"
npm ci --no-audit --no-fund
npm run build

echo "== Database: forward migrations only"
"$PHP" artisan migrate --force

echo "== Caches"
"$PHP" artisan optimize:clear
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache
"$PHP" artisan event:cache
"$PHP" artisan filament:optimize

echo "== Queue: workers pick up the new code"
"$PHP" artisan queue:restart

"$PHP" artisan up

echo "== Health check"
"$PHP" artisan lite-crm:doctor || echo "lite-crm:doctor reported problems (see above)."

echo "Deployed $(git rev-parse --short HEAD)."
