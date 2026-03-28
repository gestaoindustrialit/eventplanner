#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SCHEMA_FILE="${ROOT_DIR}/database/schema.sql"
DB_FILE="${SQLITE_PATH:-${ROOT_DIR}/database/eventplanner.sqlite}"

if [[ ! -f "${SCHEMA_FILE}" ]]; then
  echo "Erro: ficheiro schema não encontrado em ${SCHEMA_FILE}" >&2
  exit 1
fi

if ! command -v sqlite3 >/dev/null 2>&1; then
  echo "Erro: sqlite3 não está instalado no sistema." >&2
  exit 1
fi

mkdir -p "$(dirname "${DB_FILE}")"

echo "A criar e popular a base de dados SQLite em ${DB_FILE} usando ${SCHEMA_FILE}..."
sqlite3 "${DB_FILE}" < "${SCHEMA_FILE}"

echo "Base de dados criada com sucesso."
