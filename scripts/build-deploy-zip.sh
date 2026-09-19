#!/usr/bin/env bash
# Builds a zip for FTP/cPanel deployment to HostGator (no SSH/Composer on the server).
#
# Layout produced inside the zip, matching a HostGator subdomain setup where the
# subdomain's document root only contains Laravel's public/ contents:
#   app/          -> everything Laravel needs EXCEPT public/ (upload outside the doc root)
#   public_html/  -> contents of public/, with index.php patched to point at ../app
#   DEPLOY-README.txt
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT_DIR="$ROOT/build"
STAMP="$(date +%Y%m%d-%H%M%S)"
ZIP_NAME="amsetweb-deploy-${STAMP}.zip"
STAGE=""

log() { printf '\n[DEPLOY] %s\n' "$1"; }
fail() { printf '\n[DEPLOY] ERROR: %s\n' "$1" >&2; exit 1; }
cleanup() { [[ -n "$STAGE" ]] && rm -rf "$STAGE"; }
trap cleanup EXIT

find_php() {
    local candidate compatible
    for candidate in "$ROOT/.tools/bin/php" /usr/local/opt/php@8.3/bin/php /opt/homebrew/opt/php@8.3/bin/php /usr/local/opt/php/bin/php /opt/homebrew/opt/php/bin/php "$(command -v php 2>/dev/null || true)"; do
        [[ -n "$candidate" && -x "$candidate" ]] || continue
        compatible="$($candidate -r 'echo version_compare(PHP_VERSION, "8.3.0", ">=") ? "yes" : "no";' 2>/dev/null || true)"
        if [[ "$compatible" == yes ]]; then printf '%s' "$candidate"; return 0; fi
    done
    return 1
}

PHP_BIN="$(find_php)" || fail 'PHP 8.3 or newer is required. Run scripts/setup-macos.sh first.'
COMPOSER_BIN="$(command -v composer)" || fail 'Composer is required (brew install composer).'

# npm's shebang is `#!/usr/bin/env node`, which can resolve to a stale nvm default
# node (e.g. v14) regardless of which npm binary is picked. Resolve a modern
# node + matching npm-cli.js pair explicitly so `npm` isn't invoked via PATH.
find_npm() {
    local node_candidate npm_cli version
    for node_candidate in "$ROOT/.tools/node/bin/node" /usr/local/opt/node/bin/node /opt/homebrew/opt/node/bin/node "$HOME"/.nvm/versions/node/*/bin/node; do
        [[ -n "$node_candidate" && -x "$node_candidate" ]] || continue
        version="$("$node_candidate" -p 'process.versions.node.split(".")[0]' 2>/dev/null || true)"
        [[ -n "$version" && "$version" -ge 18 ]] || continue
        npm_cli="$(dirname "$(dirname "$node_candidate")")/lib/node_modules/npm/bin/npm-cli.js"
        [[ -f "$npm_cli" ]] || continue
        NODE_BIN="$node_candidate"
        NPM_CLI="$npm_cli"
        return 0
    done
    return 1
}

find_npm || fail 'A working Node.js 18+ with npm was not found. Run scripts/setup-macos.sh first.'
# Also put the modern node first on PATH: npm scripts (vite, esbuild, ...) spawn
# their own binaries via `#!/usr/bin/env node`, which ignores NODE_BIN otherwise.
export PATH
PATH="$(dirname "$NODE_BIN"):$PATH"
npm() { "$NODE_BIN" "$NPM_CLI" "$@"; }

command -v zip >/dev/null 2>&1 || fail 'The zip command is required.'
command -v rsync >/dev/null 2>&1 || fail 'rsync is required.'

cd "$ROOT"

log "Using node $("$NODE_BIN" --version) at $NODE_BIN"
log 'Installing locked npm dependencies'
npm ci

log 'Building frontend assets'
rm -f "$ROOT/public/build/manifest.json" "$ROOT/public/build"/.vite/manifest.json 2>/dev/null || true
npm run build
[[ -d "$ROOT/public/build" ]] || fail 'Vite build did not produce public/build.'
find "$ROOT/public/build" -name manifest.json | grep -q . || fail 'Vite build did not produce a manifest.json (build likely failed silently).'

STAGE="$(mktemp -d "${TMPDIR:-/tmp}/amset-deploy.XXXXXX")"
APP_DIR="$STAGE/app"
PUBLIC_DIR="$STAGE/public_html"
mkdir -p "$APP_DIR" "$PUBLIC_DIR"

log 'Staging application files'
rsync -a \
    --exclude .git \
    --exclude .github \
    --exclude .claude \
    --exclude .vscode \
    --exclude .idea \
    --exclude .tools \
    --exclude node_modules \
    --exclude vendor \
    --exclude public \
    --exclude tests \
    --exclude build \
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
    "$ROOT/" "$APP_DIR/"

log 'Installing production Composer dependencies'
(cd "$APP_DIR" && "$PHP_BIN" "$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction --no-progress)

log 'Staging public_html contents'
rsync -a --exclude .DS_Store "$ROOT/public/" "$PUBLIC_DIR/"

# `composer install` above ran filament:upgrade, which republishes vendor-matched
# static assets into $APP_DIR/public (created fresh since public/ was excluded).
# Overlay those onto public_html so they match the exact vendor version shipped,
# then drop the app-side public/ since the live app dir must not have its own.
if [[ -d "$APP_DIR/public" ]]; then
    rsync -a --exclude .DS_Store "$APP_DIR/public/" "$PUBLIC_DIR/"
    rm -rf "$APP_DIR/public"
fi

log 'Patching public_html/index.php to reference ../app'
perl -pi -e "s{__DIR__\.'/\.\./vendor/autoload\.php'}{__DIR__.'/../app/vendor/autoload.php'}" "$PUBLIC_DIR/index.php"
perl -pi -e "s{__DIR__\.'/\.\./bootstrap/app\.php'}{__DIR__.'/../app/bootstrap/app.php'}" "$PUBLIC_DIR/index.php"
perl -pi -e "s{__DIR__\.'/\.\./storage/framework/maintenance\.php'}{__DIR__.'/../app/storage/framework/maintenance.php'}" "$PUBLIC_DIR/index.php"
grep -q '../app/vendor/autoload.php' "$PUBLIC_DIR/index.php" || fail 'Failed to patch public_html/index.php.'

cat > "$STAGE/DEPLOY-README.txt" <<'EOF'
AMSET deploy package
=====================

This zip has two top-level folders:

  app/          Laravel application (composer deps already installed, no dev tools).
  public_html/  Contents of Laravel's public/ folder, with index.php pointing at ../app.

HostGator subdomain layout (no SSH assumed)
--------------------------------------------
1. In cPanel, create/use a subdomain whose document root is, e.g.:
     /home/USER/amsetweb_public
   Laravel's "app" folder must live OUTSIDE that document root, as a sibling, e.g.:
     /home/USER/amsetweb_app

2. Upload this zip via FTP into /home/USER/ (or File Manager) and use cPanel
   File Manager's "Extract" feature (do this in cPanel, not just FTP, so
   permissions/ownership stay correct).

3. Move/rename the extracted folders so you end up with:
     /home/USER/amsetweb_app        <- contents of app/
     /home/USER/amsetweb_public     <- contents of public_html/ (= subdomain doc root)

4. In amsetweb_app, copy .env.production.example to .env and fill in real values
   (DB_*, APP_URL, MAIL_*, etc.). Then generate an app key. Without SSH, run this
   once via cPanel "Terminal" if your plan has it; otherwise temporarily add a
   guarded one-off script (delete it immediately after running) or ask HostGator
   support to run `php artisan key:generate` for you.

5. Make sure these are writable by the web server (usually 755, sometimes 775):
     amsetweb_app/storage
     amsetweb_app/storage/framework/cache
     amsetweb_app/storage/framework/sessions
     amsetweb_app/storage/framework/views
     amsetweb_app/storage/logs
     amsetweb_app/bootstrap/cache

6. Run migrations once (`php artisan migrate --force`) using cPanel Terminal if
   available. If you truly have no shell access at all, use
   `php artisan amset:sqlite-to-mysql` locally against production MySQL
   credentials over a secure tunnel, or ask HostGator support about enabling
   SSH/Terminal for your account.

Repeat deploys
--------------
Re-run scripts/build-deploy-zip.sh, upload the new zip, extract it over the
existing amsetweb_app / amsetweb_public folders (extracting will overwrite
matching files), then re-check file permissions from step 5.
EOF

mkdir -p "$OUT_DIR"
(cd "$STAGE" && zip -r -q "$OUT_DIR/$ZIP_NAME" app public_html DEPLOY-README.txt -x '*.DS_Store')

log "Created $OUT_DIR/$ZIP_NAME"
