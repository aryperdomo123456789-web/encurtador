#!/usr/bin/env sh
set -eu

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/../.." && pwd)
COMPOSE_FILE="$ROOT_DIR/deploy/compose.prod.yml"
BACKUP_DIR=${BACKUP_DIR:-/www/backups/shlink-panel}
TIMESTAMP=$(date -u +%Y%m%dT%H%M%SZ)
OUTPUT="$BACKUP_DIR/shlink-panel-$TIMESTAMP.sql.gz"
TMP_OUTPUT=$(mktemp "${BACKUP_DIR}/.backup.XXXXXX")

cleanup() {
    rm -f "$TMP_OUTPUT"
}

trap cleanup EXIT INT TERM

mkdir -p "$BACKUP_DIR"

docker compose -f "$COMPOSE_FILE" exec -T db sh -lc \
    'exec mariadb-dump -u root -p"$MARIADB_ROOT_PASSWORD" --single-transaction --routines --triggers --events "$MARIADB_DATABASE"' \
    | gzip -c > "$TMP_OUTPUT"

mv "$TMP_OUTPUT" "$OUTPUT"
trap - EXIT INT TERM

printf '%s\n' "$OUTPUT"
