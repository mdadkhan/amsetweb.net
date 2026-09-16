#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TOOLS="$ROOT/.tools"
HOST="${AMSET_HOST:-127.0.0.1}"
PORT="${AMSET_PORT:-8000}"
CHECK_ONLY=false

for argument in "$@"; do
    case "$argument" in
        --check) CHECK_ONLY=true ;;
        --host=*) HOST="${argument#*=}" ;;
        --port=*) PORT="${argument#*=}" ;;
        --help)
            printf 'Usage: %s [--check] [--host=127.0.0.1] [--port=8000]\n' "$0"
            exit 0
            ;;
        *) printf 'Unknown option: %s\n' "$argument" >&2; exit 2 ;;
    esac
done

fail() { printf '[AMSET] ERROR: %s\n' "$1" >&2; exit 1; }

find_php() {
    local candidate compatible
    for candidate in "$TOOLS/bin/php" /usr/local/opt/php@8.3/bin/php /opt/homebrew/opt/php@8.3/bin/php /usr/local/opt/php/bin/php /opt/homebrew/opt/php/bin/php "$(command -v php 2>/dev/null || true)"; do
        [[ -n "$candidate" && -x "$candidate" ]] || continue
        compatible="$($candidate -r 'echo version_compare(PHP_VERSION, "8.3.0", ">=") ? "yes" : "no";' 2>/dev/null || true)"
        if [[ "$compatible" == yes ]]; then printf '%s' "$candidate"; return 0; fi
    done
    return 1
}

PHP_BIN="$(find_php)" || fail 'PHP 8.3 or newer is required. Run scripts/setup-macos.sh first.'

if [[ -x "$TOOLS/node/bin/node" ]]; then
    NODE_BIN="$TOOLS/node/bin/node"
elif [[ -x /usr/local/opt/node/bin/node ]]; then
    NODE_BIN=/usr/local/opt/node/bin/node
elif [[ -x /opt/homebrew/opt/node/bin/node ]]; then
    NODE_BIN=/opt/homebrew/opt/node/bin/node
else
    NODE_BIN="$(command -v node 2>/dev/null || true)"
fi
[[ -n "$NODE_BIN" && -x "$NODE_BIN" ]] || fail 'Node.js is required. Run scripts/setup-macos.sh first.'
"$NODE_BIN" -e 'const [major, minor] = process.versions.node.split(".").map(Number); process.exit(major > 22 || (major === 22 && minor >= 12) ? 0 : 1)' || fail 'Node.js 22.12 or newer is required.'

NODE_DIRECTORY="$(dirname "$NODE_BIN")"
NPM_BIN="$NODE_DIRECTORY/npm"
[[ -x "$NPM_BIN" ]] || fail "npm was not found next to $NODE_BIN."

if [[ -f "$TOOLS/composer/composer.phar" ]]; then
    COMPOSER=("$PHP_BIN" "$TOOLS/composer/composer.phar")
elif command -v composer >/dev/null 2>&1; then
    COMPOSER=("$PHP_BIN" "$(command -v composer)")
else
    fail 'Composer is required. Run scripts/setup-macos.sh first.'
fi

cd "$ROOT"
[[ -f .env ]] || cp .env.example .env
if grep -Eq '^DB_CONNECTION=sqlite$' .env; then touch database/database.sqlite; fi

printf '[AMSET] Installing PHP dependencies\n'
"${COMPOSER[@]}" install --no-interaction
printf '[AMSET] Installing frontend dependencies\n'
PATH="$NODE_DIRECTORY:$PATH" "$NPM_BIN" install
if ! grep -Eq '^APP_KEY=base64:.+' .env; then "$PHP_BIN" artisan key:generate --force; fi
printf '[AMSET] Applying database migrations\n'
"$PHP_BIN" artisan migrate --force
printf '[AMSET] Building production assets\n'
PATH="$NODE_DIRECTORY:$PATH" "$NPM_BIN" run build

if [[ "$CHECK_ONLY" == true ]]; then
    printf '[AMSET] Build check complete. Server launch skipped.\n'
    exit 0
fi

if command -v lsof >/dev/null 2>&1; then
    SERVER_PIDS="$(lsof -tiTCP:"$PORT" -sTCP:LISTEN 2>/dev/null || true)"
    if [[ -n "$SERVER_PIDS" ]]; then
        printf '[AMSET] Stopping existing server on port %s (PID%s: %s)\n' "$PORT" "$([[ "$SERVER_PIDS" == *$'\n'* ]] && printf 's' || true)" "$(printf '%s' "$SERVER_PIDS" | tr '\n' ' ')"
        while IFS= read -r server_pid; do
            [[ -n "$server_pid" ]] && kill "$server_pid" 2>/dev/null || true
        done <<< "$SERVER_PIDS"

        for _ in {1..20}; do
            lsof -tiTCP:"$PORT" -sTCP:LISTEN >/dev/null 2>&1 || break
            sleep 0.1
        done

        REMAINING_PIDS="$(lsof -tiTCP:"$PORT" -sTCP:LISTEN 2>/dev/null || true)"
        if [[ -n "$REMAINING_PIDS" ]]; then
            while IFS= read -r server_pid; do
                [[ -n "$server_pid" ]] && kill -KILL "$server_pid" 2>/dev/null || true
            done <<< "$REMAINING_PIDS"
        fi
    fi
else
    fail 'lsof is required to check whether the local server port is already in use.'
fi

printf '[AMSET] Starting server at http://%s:%s\n' "$HOST" "$PORT"
exec "$PHP_BIN" artisan serve --host="$HOST" --port="$PORT"
