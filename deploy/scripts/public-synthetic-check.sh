#!/usr/bin/env bash
set -euo pipefail

BASE_URL=${1:-https://me.vr766.com}
BASE_URL=${BASE_URL%/}
TMP_DIR=$(mktemp -d)
trap 'rm -rf "$TMP_DIR"' EXIT

fetch() {
    local path=$1
    local expected=$2
    local name=$3
    local headers="$TMP_DIR/${name}.headers"
    local body="$TMP_DIR/${name}.body"
    local status

    status=$(curl --silent --show-error --location --max-time 15 --dump-header "$headers" --output "$body" --write-out '%{http_code}' "$BASE_URL$path")
    test "$status" = "$expected"
    printf '%s=%s\n' "$name" "$status"
}

fetch '/' 200 home
fetch '/healthz' 200 healthz
fetch '/health/ready' 200 ready
fetch '/health/release' 200 release
fetch '/api/v1/openapi.json' 200 openapi

for name in healthz ready release openapi; do
    ! grep -qi '^set-cookie:' "$TMP_DIR/${name}.headers"
    grep -qi '^x-request-id:' "$TMP_DIR/${name}.headers"
done

python3 - "$TMP_DIR/release.body" "$TMP_DIR/openapi.body" <<'PY'
import json
import re
import sys
from pathlib import Path

release = json.loads(Path(sys.argv[1]).read_text())
assert release.get('service') == 'panel'
assert re.fullmatch(r'[0-9a-f]{7,40}', str(release.get('release', '')))
assert re.fullmatch(r'\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z', str(release.get('built_at', '')))

openapi = json.loads(Path(sys.argv[2]).read_text())
assert openapi.get('openapi') == '3.1.0'
paths = openapi.get('paths', {})
for path in ('/api/v1/openapi.json', '/api/v1/me', '/api/v1/links', '/api/v1/events'):
    assert path in paths
raw = Path(sys.argv[2]).read_text()
for value in ('sk_' + 'live_', 'wh' + 'sec_', 'APP_KEY'):
    assert value not in raw
print('contract_and_release=valid')
PY
