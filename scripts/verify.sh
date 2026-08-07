#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
find "$ROOT/16-sabri-classical-homeopathy-ai" -name '*.php' -print0 | sort -z | xargs -0 -n1 php -l >/dev/null
node --check "$ROOT/16-sabri-classical-homeopathy-ai/assets/js/public.js"
node --check "$ROOT/16-sabri-classical-homeopathy-ai/assets/js/admin.js"
php "$ROOT/tests/run.php"
php "$ROOT/tests/four-plan-compliance.php"
php "$ROOT/tests/review40.php"
php "$ROOT/tests/package-contract.php"
if grep -RInE 'BEGIN (RSA|OPENSSH|EC|DSA) PRIVATE KEY|AKIA[0-9A-Z]{16}|sk-ant-[A-Za-z0-9_-]{20,}' "$ROOT" --exclude-dir=dist; then
  echo 'FAIL: potential secret material found' >&2; exit 1
fi
echo 'PASS: PHP, JavaScript, unit, adversarial, four-plan, forty-round, package and secret checks'
