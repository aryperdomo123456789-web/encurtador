#!/usr/bin/env sh
set -eu

if [ $# -lt 1 ]; then
    echo "Uso: $0 <backup.sql|backup.sql.gz>" >&2
    exit 1
fi

INPUT_FILE=$1
if [ ! -f "$INPUT_FILE" ]; then
    echo "Arquivo nao encontrado: $INPUT_FILE" >&2
    exit 1
fi

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/../.." && pwd)
COMPOSE_FILE="$ROOT_DIR/deploy/compose.prod.yml"

case "$INPUT_FILE" in
    *.gz)
        DECOMPRESS='gzip -dc'
        ;;
    *.sql)
        DECOMPRESS='cat'
        ;;
    *)
        echo "Formato nao suportado. Use .sql ou .sql.gz." >&2
        exit 1
        ;;
esac

echo "Restaurando banco a partir de $INPUT_FILE" >&2

if [ "$DECOMPRESS" = 'gzip -dc' ]; then
    gzip -dc "$INPUT_FILE" | docker compose -f "$COMPOSE_FILE" exec -T db sh -lc \
        'exec mariadb -u root -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"'
else
    cat "$INPUT_FILE" | docker compose -f "$COMPOSE_FILE" exec -T db sh -lc \
        'exec mariadb -u root -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"'
fi
