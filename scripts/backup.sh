#!/bin/bash
# Daily database backup for a Coaching ERP client instance.
# Reads DB creds from ~/.coaching_db_credentials (DB, USER, PW).
set -euo pipefail

DIR="${BACKUP_DIR:-$HOME/backups/coaching}"
CREDS="${DB_CREDS:-$HOME/.coaching_db_credentials}"
RETAIN_DAYS="${RETAIN_DAYS:-14}"

mkdir -p "$DIR"
# shellcheck disable=SC1090
source "$CREDS"

STAMP="$(date +%Y%m%d-%H%M%S)"
FILE="$DIR/coaching-$STAMP.sql.gz"

mysqldump --single-transaction --quick -u "$USER" -p"$PW" "$DB" | gzip > "$FILE"

# Retention: delete backups older than RETAIN_DAYS.
find "$DIR" -name 'coaching-*.sql.gz' -mtime +"$RETAIN_DAYS" -delete

# Offsite copy target (configure OFFSITE to an rsync/scp destination to enable).
if [ -n "${OFFSITE:-}" ]; then
  rsync -az "$FILE" "$OFFSITE/" || echo "offsite copy failed" >&2
fi

echo "backup ok: $(basename "$FILE") ($(du -h "$FILE" | cut -f1))"
