#!/bin/bash
# Restore a Coaching ERP backup into a target database.
# Usage: restore.sh <backup.sql.gz> <target_db>
set -euo pipefail

FILE="$1"
TARGET="$2"
CREDS="${DB_CREDS:-$HOME/.coaching_db_credentials}"
# shellcheck disable=SC1090
source "$CREDS"

gunzip -c "$FILE" | mysql -u "$USER" -p"$PW" "$TARGET"
echo "restored $FILE into $TARGET"
