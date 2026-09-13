#!/usr/bin/env bash
#
# Package the theme into an importable WordPress ZIP.
#
#   ./build.sh            → sreesaanvika.zip
#   ./build.sh dist/      → dist/sreesaanvika.zip
#
set -euo pipefail

THEME="sreesaanvika"
PLUGINS=("sreesaanvika-delivery" "sreesaanvika-offers")
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OUT_DIR="${1:-$ROOT}"
OUT="$OUT_DIR/$THEME.zip"

cd "$ROOT"

if [ ! -f "$THEME/style.css" ]; then
	echo "error: $THEME/style.css not found — run this from the repo root." >&2
	exit 1
fi

# Fail early on a PHP syntax error rather than shipping something broken.
if command -v php >/dev/null 2>&1; then
	while IFS= read -r file; do
		php -l "$file" >/dev/null || { echo "error: syntax error in $file" >&2; exit 1; }
	done < <(find "$THEME" "${PLUGINS[@]}" -name '*.php' 2>/dev/null)
	echo "PHP syntax OK"
fi

mkdir -p "$OUT_DIR"
rm -f "$OUT"

zip -r -q -9 "$OUT" "$THEME" \
	-x '*.DS_Store' \
	-x '*__MACOSX*' \
	-x '*/.git/*' \
	-x '*/node_modules/*' \
	-x '*.map'

echo "Built $OUT ($(du -h "$OUT" | cut -f1))"

# Each companion plugin ships as its own ZIP, installed like any other.
for plugin in "${PLUGINS[@]}"; do
	[ -f "$plugin/$plugin.php" ] || continue

	plugin_out="$OUT_DIR/$plugin.zip"
	rm -f "$plugin_out"

	zip -r -q -9 "$plugin_out" "$plugin" \
		-x '*.DS_Store' \
		-x '*__MACOSX*' \
		-x '*/.git/*' \
		-x '*/node_modules/*' \
		-x '*.map'

	echo "Built $plugin_out ($(du -h "$plugin_out" | cut -f1))"
done
