#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SCHEMA_FILE="${ROOT_DIR}/database/schema.sql"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"

if [[ ! -f "${SCHEMA_FILE}" ]]; then
  echo "Erro: ficheiro schema não encontrado em ${SCHEMA_FILE}" >&2
  exit 1
fi

MYSQL_CMD=(mysql --protocol=TCP -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}")

if [[ -n "${DB_PASS}" ]]; then
  MYSQL_CMD+=("-p${DB_PASS}")
fi

echo "A criar e popular a base de dados usando ${SCHEMA_FILE}..."
"${MYSQL_CMD[@]}" < "${SCHEMA_FILE}"

echo "Base de dados criada com sucesso."
