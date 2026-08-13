#!/usr/bin/env bash
set -euo pipefail

# Restaura banco + uploads de um snapshot criado por scripts/backup.sh --
# PLANO.md Anexo A.5. Apaga o banco atual e substitui pelo do snapshot,
# por isso pede confirmação explícita.
#
# Uso: scripts/restore.sh <pasta-do-snapshot>
#   scripts/restore.sh backups/20260812-153000_antes-do-prazo

cd "$(dirname "$0")/.."

ORIGEM="${1:-}"
if [ -z "$ORIGEM" ] || [ ! -d "$ORIGEM" ]; then
    echo "Uso: $0 <pasta-do-snapshot>" >&2
    echo "Snapshots disponíveis:" >&2
    ls -1 backups 2>/dev/null | sed 's/^/  /' >&2 || echo "  (nenhum em backups)" >&2
    exit 1
fi

if [ ! -f "${ORIGEM}/database.sql.gz" ]; then
    echo "${ORIGEM}/database.sql.gz não existe -- não é um snapshot válido." >&2
    exit 1
fi

set -a
# shellcheck disable=SC1091
source .env
set +a

echo "==> Restaurando de ${ORIGEM}"
echo "AVISO: isto apaga o banco atual (${DB_DATABASE}) e substitui pelo do snapshot."
read -r -p "Confirma? (digite 'restaurar' pra continuar) " CONFIRMACAO
if [ "$CONFIRMACAO" != "restaurar" ]; then
    echo "Cancelado."
    exit 1
fi

echo "-> Banco"
if command -v psql >/dev/null 2>&1; then
    PGPASSWORD="$DB_PASSWORD" dropdb -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" --if-exists "$DB_DATABASE"
    PGPASSWORD="$DB_PASSWORD" createdb -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" "$DB_DATABASE"
    gunzip -c "${ORIGEM}/database.sql.gz" | PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -q
elif docker compose ps postgres >/dev/null 2>&1; then
    docker compose exec -T postgres dropdb -U "$DB_USERNAME" --if-exists "$DB_DATABASE"
    docker compose exec -T postgres createdb -U "$DB_USERNAME" "$DB_DATABASE"
    gunzip -c "${ORIGEM}/database.sql.gz" | docker compose exec -T postgres psql -U "$DB_USERNAME" -d "$DB_DATABASE" -q
else
    echo "psql não encontrado no PATH nem via docker compose. Abortando." >&2
    exit 1
fi

echo "-> Uploads"
if [ -f "${ORIGEM}/uploads.tar.gz" ]; then
    rm -rf storage/app/private
    tar -xzf "${ORIGEM}/uploads.tar.gz" -C storage/app
else
    echo "   (snapshot sem uploads.tar.gz -- pulando)"
fi

echo "==> Restauração concluída."
