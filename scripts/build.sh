#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
NAME="16-sabri-classical-homeopathy-ai"
VERSION="2.2.0"
DIST="$ROOT/dist"
STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT
mkdir -p "$DIST" "$STAGE/$NAME"
cp -a "$ROOT/$NAME/." "$STAGE/$NAME/"
find "$STAGE" -type d -exec chmod 0755 {} +
find "$STAGE" -type f -exec chmod 0644 {} +
EPOCH="${SOURCE_DATE_EPOCH:-1786075200}"
find "$STAGE" -exec touch -h -d "@$EPOCH" {} +
rm -f "$DIST/$NAME-$VERSION.zip" "$DIST/$NAME-$VERSION.zip.sha256"
(
  cd "$STAGE"
  find "$NAME" -print | LC_ALL=C sort | zip -X -q "$DIST/$NAME-$VERSION.zip" -@
)
sha256sum "$DIST/$NAME-$VERSION.zip" > "$DIST/$NAME-$VERSION.zip.sha256"
python3 "$ROOT/scripts/manifest.py" "$ROOT/$NAME" "$DIST/manifest.json"
php "$ROOT/tests/package-contract.php"
unzip -t "$DIST/$NAME-$VERSION.zip" >/dev/null
echo "Built $DIST/$NAME-$VERSION.zip"
