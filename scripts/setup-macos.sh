#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TOOLS="$ROOT/.tools"
NODE_MAJOR=22

mkdir -p "$TOOLS/bin" "$TOOLS/cache" "$TOOLS/composer" "$TOOLS/node" "$TOOLS/tmp"

log() { printf '\n[AMSET] %s\n' "$1"; }
fail() { printf '\n[AMSET] ERROR: %s\n' "$1" >&2; exit 1; }

command -v curl >/dev/null 2>&1 || fail 'curl is required.'
command -v shasum >/dev/null 2>&1 || fail 'shasum is required.'

find_php() {
    local candidate version
    for candidate in "${AMSET_PHP_BIN:-}" /usr/local/opt/php@8.3/bin/php /opt/homebrew/opt/php@8.3/bin/php "$(command -v php 2>/dev/null || true)"; do
        [[ -n "$candidate" && -x "$candidate" ]] || continue
        version="$($candidate -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || true)"
        if [[ "$version" == "8.3" ]]; then printf '%s' "$candidate"; return 0; fi
    done
    return 1
}

if PHP_BIN="$(find_php)"; then
    log "Using PHP $($PHP_BIN -r 'echo PHP_VERSION;') at $PHP_BIN"
else
    command -v brew >/dev/null 2>&1 || fail 'PHP 8.3 is missing. Install Homebrew from https://brew.sh, then rerun this script.'
    log 'Installing PHP 8.3 with Homebrew'
    HOMEBREW_NO_AUTO_UPDATE=1 HOMEBREW_NO_INSTALL_CLEANUP=1 brew install php@8.3
    PHP_BIN="$(brew --prefix php@8.3)/bin/php"
fi

required_extensions=(ctype curl dom fileinfo filter mbstring openssl pdo pdo_mysql pdo_sqlite session tokenizer xml)
missing_extensions=()
for extension in "${required_extensions[@]}"; do
    "$PHP_BIN" -r "exit(extension_loaded('$extension') ? 0 : 1);" || missing_extensions+=("$extension")
done
(( ${#missing_extensions[@]} == 0 )) || fail "Missing PHP extensions: ${missing_extensions[*]}"

case "$(uname -m)" in
    arm64) node_arch=arm64 ;;
    x86_64) node_arch=x64 ;;
    *) fail "Unsupported macOS architecture: $(uname -m)" ;;
esac

node_version="$(curl -fsSL https://nodejs.org/dist/index.json | sed -nE 's/.*"version":"(v22\.[0-9]+\.[0-9]+)".*/\1/p' | head -n 1)"
[[ -n "$node_version" ]] || fail 'Could not resolve the latest Node.js 22 release.'
node_archive="node-${node_version}-darwin-${node_arch}.tar.gz"

if [[ ! -x "$TOOLS/node/bin/node" || "$($TOOLS/node/bin/node -p 'process.versions.node.split(".")[0]' 2>/dev/null || true)" != "$NODE_MAJOR" ]]; then
    log "Installing Node.js $node_version in .tools/node"
    curl -fsSL "https://nodejs.org/dist/${node_version}/SHASUMS256.txt" -o "$TOOLS/tmp/SHASUMS256.txt"
    curl -fsSL "https://nodejs.org/dist/${node_version}/${node_archive}" -o "$TOOLS/tmp/$node_archive"
    (cd "$TOOLS/tmp" && grep " ${node_archive}$" SHASUMS256.txt | shasum -a 256 -c -)
    rm -rf "$TOOLS/node"
    mkdir -p "$TOOLS/node"
    tar -xzf "$TOOLS/tmp/$node_archive" -C "$TOOLS/node" --strip-components=1
else
    log "Node.js $($TOOLS/node/bin/node --version) is already installed"
fi

expected_signature="$(curl -fsSL https://composer.github.io/installer.sig)"
curl -fsSL https://getcomposer.org/installer -o "$TOOLS/tmp/composer-setup.php"
actual_signature="$(shasum -a 384 "$TOOLS/tmp/composer-setup.php" | awk '{print $1}')"
[[ "$expected_signature" == "$actual_signature" ]] || fail 'Composer installer signature verification failed.'

if [[ ! -f "$TOOLS/composer/composer.phar" ]]; then
    log 'Installing Composer in .tools/composer'
    "$PHP_BIN" "$TOOLS/tmp/composer-setup.php" --quiet --install-dir="$TOOLS/composer" --filename=composer.phar
else
    log "Composer $($PHP_BIN $TOOLS/composer/composer.phar --version --no-ansi | awk '{print $3}') is already installed"
fi

cat > "$TOOLS/bin/php" <<EOF
#!/usr/bin/env bash
exec "$PHP_BIN" "\$@"
EOF
cat > "$TOOLS/bin/composer" <<EOF
#!/usr/bin/env bash
exec "$PHP_BIN" "$TOOLS/composer/composer.phar" "\$@"
EOF
cat > "$TOOLS/bin/amset" <<EOF
#!/usr/bin/env bash
export PATH="$TOOLS/node/bin:$TOOLS/bin:\$PATH"
cd "$ROOT"
exec "\$@"
EOF
chmod +x "$TOOLS/bin/php" "$TOOLS/bin/composer" "$TOOLS/bin/amset"

export PATH="$TOOLS/node/bin:$TOOLS/bin:$PATH"
cd "$ROOT"
[[ -f .env ]] || cp .env.example .env
touch database/database.sqlite

log 'Installing application dependencies'
"$PHP_BIN" "$TOOLS/composer/composer.phar" install --no-interaction
npm install
if ! grep -Eq '^APP_KEY=base64:.+' .env; then "$PHP_BIN" artisan key:generate --force; fi
"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan storage:link 2>/dev/null || true
npm run build

log 'Setup complete'
printf 'PHP: %s\nNode: %s\nComposer: %s\n' "$($PHP_BIN -r 'echo PHP_VERSION;')" "$(node --version)" "$($PHP_BIN $TOOLS/composer/composer.phar --version --no-ansi | awk '{print $3}')"
printf 'Run: .tools/bin/amset .tools/bin/php artisan serve\n'
