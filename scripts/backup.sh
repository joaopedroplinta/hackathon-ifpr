#!/usr/bin/env bash
set -euo pipefail

# Backup do banco (pg_dump) + uploads (storage/app/private) -- PLANO.md
# Anexo A.5. Funciona tanto com Postgres nativo (pg_dump no PATH) quanto
# com o Postgres do docker-compose deste projeto.
#
# Uso:
#   scripts/backup.sh                    # só carimbo de data/hora
#   scripts/backup.sh antes-do-prazo     # carimbo + rótulo, pra achar rápido no dia
#
# No dia do evento, agendar via cron a cada 15 min:
#   */15 * * * * cd /caminho/do/projeto && scripts/backup.sh >> storage/logs/backup.log 2>&1

cd "$(dirname "$0")/.."

ROTULO="${1:-}"
CARIMBO="$(date +%Y%m%d-%H%M%S)"
NOME="${CARIMBO}${ROTULO:+_${ROTULO}}"
DESTINO="backups/${NOME}"

mkdir -p "$DESTINO"

set -a
# shellcheck disable=SC1091
source .env
set +a

echo "==> Backup ${NOME}"

echo "-> Banco (${DB_DATABASE})"
if command -v pg_dump >/dev/null 2>&1; then
    PGPASSWORD="$DB_PASSWORD" pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" \
        | gzip > "${DESTINO}/database.sql.gz"
elif docker compose ps postgres >/dev/null 2>&1; then
    docker compose exec -T postgres pg_dump -U "$DB_USERNAME" -d "$DB_DATABASE" \
        | gzip > "${DESTINO}/database.sql.gz"
else
    echo "pg_dump não encontrado no PATH nem via docker compose. Abortando." >&2
    rmdir "$DESTINO" 2>/dev/null || true
    exit 1
fi

echo "-> Uploads (storage/app/private)"
if [ -d storage/app/private ] && [ -n "$(ls -A storage/app/private 2>/dev/null)" ]; then
    tar -czf "${DESTINO}/uploads.tar.gz" -C storage/app private
else
    echo "   (nada em storage/app/private ainda -- pulando)"
fi

echo "-> Cópia em nuvem: PENDENTE. Provedor de hospedagem ainda não foi decidido"
echo "   (PLANO.md, seção 10). Quando decidido, adicionar aqui o comando de"
echo "   sync (ex.: rclone copy \"$DESTINO\" remoto:hackathon-backups/${NOME})."

du -sh "$DESTINO"
echo "==> Backup salvo em ${DESTINO}"
