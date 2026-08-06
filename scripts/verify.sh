#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN="$ROOT/16-sabri-classical-homeopathy-ai"

find "$PLUGIN" -type f -name '*.php' -print0 | sort -z | xargs -0 -n1 php -l >/dev/null
node --check "$PLUGIN/assets/js/public.js"
node --check "$PLUGIN/assets/js/admin.js"
php "$ROOT/tests/run.php"
php "$ROOT/tests/contracts.php"

echo "PASS: PHP syntax, JavaScript syntax, unit tests and contract checks"
