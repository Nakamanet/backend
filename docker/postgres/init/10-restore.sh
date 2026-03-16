#!/bin/sh
set -e

echo "Looking for dump in /dump ..."

SQL_DUMP="/dump/laravel.sql"

if [ -f "$SQL_DUMP" ]; then
  echo "Found plain SQL dump: $SQL_DUMP"
  echo "Restoring with psql..."
  psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d "$POSTGRES_DB" < "$SQL_DUMP"
  echo "SQL restore complete."
  exit 0
fi


echo "No dump found. Expected /dump/laravel.sql or /dump/laravel.dump"
