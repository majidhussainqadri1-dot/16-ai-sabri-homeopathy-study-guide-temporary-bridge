#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
"$ROOT/scripts/verify.sh"
python3 "$ROOT/scripts/build_zip.py"
(cd "$ROOT/dist" && sha256sum -c 16-sabri-classical-homeopathy-ai-1.0.0.zip.sha256)
unzip -t "$ROOT/dist/16-sabri-classical-homeopathy-ai-1.0.0.zip" >/dev/null
echo "PASS: deterministic package and checksum"
