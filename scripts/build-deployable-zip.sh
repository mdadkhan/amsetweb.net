#!/usr/bin/env bash
# Zips the app as-is (whatever's already on disk in vendor/ and public/build/) into a
# single folder you can unzip directly into a HostGator subdomain document root, next
# to the other Laravel subdomains that already show an artisan file at their root.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT_DIR="$ROOT/build"
STAMP="$(date +%Y%m%d-%H%M%S)"
ZIP_NAME="amsetweb-deploy-singlefolder-${STAMP}.zip"
STAGE=""

log() { printf '\n[DEPLOY] %s\n' "$1"; }
fail() { printf '\n[DEPLOY] ERROR: %s\n' "$1" >&2; exit 1; }
cleanup() { [[ -n "$STAGE" ]] && rm -rf "$STAGE"; }
trap cleanup EXIT

command -v zip >/dev/null 2>&1 || fail 'The zip command is required.'
command -v rsync >/dev/null 2>&1 || fail 'rsync is required.'
[[ -d "$ROOT/vendor" ]] || fail 'vendor/ is missing. Run composer install first.'
[[ -d "$ROOT/public/build" ]] || fail 'public/build is missing. Run npm run build first.'

cd "$ROOT"

STAGE="$(mktemp -d "${TMPDIR:-/tmp}/amset-deploy.XXXXXX")"

log 'Staging application files'
rsync -a \
    --exclude .git \
    --exclude .github \
    --exclude .claude \
    --exclude .vscode \
    --exclude .idea \
    --exclude .tools \
    --exclude node_modules \
    --exclude tests \
    --exclude /build \
    --exclude .env \
    --exclude .env.backup \
    --exclude .env.production \
    --exclude .env.production.local \
    --exclude '*.sqlite' \
    --exclude .DS_Store \
    --exclude .phpunit.result.cache \
    --exclude .phpactor.json \
    --exclude 'storage/logs/*' \
    --exclude 'storage/framework/cache/data/*' \
    --exclude 'storage/framework/sessions/*' \
    --exclude 'storage/framework/views/*' \
    --exclude 'storage/framework/testing/*' \
    --exclude storage/app/private/hostgator-import.sql \
    --exclude 'bootstrap/cache/*.php' \
    "$ROOT/" "$STAGE/"

log 'Normalizing file/folder permissions (dirs 755, files 644, artisan executable)'
find "$STAGE" -type d -exec chmod 755 {} +
find "$STAGE" -type f -exec chmod 644 {} +
chmod 755 "$STAGE/artisan"

log 'Writing root .htaccess to route requests into public/'
cat > "$STAGE/.htaccess" <<'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
EOF

# Apache's DirectoryIndex is honored even when AllowOverride disables .htaccess,
# so the home page still needs a literal index.php at the root as a fallback.
log 'Writing root index.php fallback (paths adjusted for root, not public/)'
cat > "$STAGE/index.php" <<'EOF'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
EOF

# Rewrite of /favicon.ico -> public/favicon.ico depends on the same broken .htaccess,
# so ship a root-level copy too (browsers request /favicon.ico from the doc root).
cp "$ROOT/public/favicon.ico" "$STAGE/favicon.ico"

mkdir -p "$OUT_DIR"
(cd "$STAGE" && zip -r -q "$OUT_DIR/$ZIP_NAME" . -x '*.DS_Store')

log "Created $OUT_DIR/$ZIP_NAME"
log 'Unzip this directly into the subdomain document root folder (the one that already contains other Laravel apps with an artisan file visible).'
log 'After extracting: copy .env.production.example to .env, fill in real values, run php artisan key:generate, php artisan migrate --force, and set storage/ + bootstrap/cache/ writable.'
