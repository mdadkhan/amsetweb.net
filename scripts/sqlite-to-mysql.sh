#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [[ -x "$ROOT/.tools/bin/php" ]]; then
    PHP_BIN="$ROOT/.tools/bin/php"
elif [[ -x /usr/local/opt/php@8.3/bin/php ]]; then
    PHP_BIN=/usr/local/opt/php@8.3/bin/php
elif [[ -x /opt/homebrew/opt/php@8.3/bin/php ]]; then
    PHP_BIN=/opt/homebrew/opt/php@8.3/bin/php
elif command -v php >/dev/null 2>&1; then
    PHP_BIN="$(command -v php)"
else
    printf 'PHP 8.3 or newer is required. Run scripts/setup-macos.sh first.\n' >&2
    exit 1
fi

cd "$ROOT"
exec "$PHP_BIN" artisan amset:sqlite-to-mysql "$@"
