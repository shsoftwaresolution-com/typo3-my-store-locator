#!/usr/bin/env bash
# Builds the TER upload zip of the TYPO3 extension "my_store_locator" into typo3/dist/ (gitignored, never commit zips).
# Only runtime files go into the zip: Classes, Configuration, Resources, composer.json, ext_emconf.php,
# ext_localconf.php, LICENSE.txt, README.md. The extension files sit at the zip root (TER format).
# Usage: bin/build-release.sh            (version from ext_emconf.php)
# Needs: bash, python3 (php optional for a syntax check).
set -euo pipefail
cd "$(dirname "$0")/.."
KEY=my_store_locator
VERSION=$(sed -n "s/.*'version' *=> *'\([0-9.]*\)'.*/\1/p" ext_emconf.php | head -1)
[ -n "$VERSION" ] || { echo "version not found in ext_emconf.php" >&2; exit 1; }
FILES=(Classes Configuration Resources composer.json ext_emconf.php ext_localconf.php LICENSE.txt README.md)

# sanity checks: no secrets, PHP syntax, valid JSON
if grep -rIlE '(api[_-]?key|secret|password|token)\s*[:=]\s*["'\''][A-Za-z0-9+/_-]{20,}' "${FILES[@]}"; then
  echo "possible secret in the files above – aborting" >&2; exit 1
fi
if command -v php >/dev/null; then
  find Classes Configuration ext_emconf.php ext_localconf.php -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
fi
python3 -c 'import json; json.load(open("composer.json"))'

mkdir -p dist
OUT="dist/${KEY}_${VERSION}.zip"
rm -f "$OUT"
python3 - "$OUT" "${FILES[@]}" <<'PY'
import os, sys, zipfile
out, items = sys.argv[1], sys.argv[2:]
def files(item):
    if os.path.isfile(item):
        yield item
        return
    for root, dirs, names in os.walk(item):
        dirs.sort()
        for n in sorted(names):
            if n == '.DS_Store' or n.endswith(('.env', '~', '.orig')) or n.startswith('.env'):
                continue
            yield os.path.join(root, n)
with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
    for item in items:
        for path in files(item):
            info = zipfile.ZipInfo(path, date_time=(2026, 1, 1, 0, 0, 0))  # fixed time -> reproducible zip
            info.external_attr = 0o644 << 16
            info.compress_type = zipfile.ZIP_DEFLATED
            with open(path, 'rb') as f:
                z.writestr(info, f.read())
for n in zipfile.ZipFile(out).namelist():
    print('  ' + n)
PY
echo "built $OUT ($(du -h "$OUT" | cut -f1))"
