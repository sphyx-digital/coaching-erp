#!/bin/bash
# Provision a new Coaching ERP client instance on the shared cPanel server.
# Run as the cPanel account user. Idempotent-ish; review each step.
#
# Usage: provision-client.sh <subdomain> <db_name> <db_user>
set -euo pipefail

SUB="${1:?subdomain, e.g. coaching}"
DB="${2:?db name, e.g. sphyx_coaching}"
DBUSER="${3:?db user}"
ROOTDOMAIN="${ROOTDOMAIN:-sphyx.in}"
APPDIR="$HOME/${SUB}-erp"

echo "1) Database + user"
PW="$(openssl rand -hex 12)Aa9"
uapi Mysql create_database name="$DB"
uapi Mysql create_user name="$DBUSER" password="$PW"
uapi Mysql set_privileges_on_database user="$DBUSER" database="$DB" privileges="ALL PRIVILEGES"
printf 'DB=%s\nUSER=%s\nPW=%s\n' "$DB" "$DBUSER" "$PW" > "$HOME/.${SUB}_db_credentials"
chmod 600 "$HOME/.${SUB}_db_credentials"

echo "2) Subdomain (docroot symlinked to app public/)"
uapi SubDomain addsubdomain domain="$SUB" rootdomain="$ROOTDOMAIN" dir="${SUB}-erp/public"
rm -rf "$HOME/public_html/${SUB}-erp/public"
ln -s "$APPDIR/public" "$HOME/public_html/${SUB}-erp/public"
uapi LangPHP php_set_vhost_versions version=ea-php82 vhost="${SUB}.${ROOTDOMAIN}"

echo "3) App: deploy code to $APPDIR, then:"
cat <<'STEPS'
   cp .env.example .env   # set DB_*, CLIENT_* (name, GSTIN, brand colours), feature flags
   php artisan key:generate
   php artisan migrate --force
   php artisan db:seed --force   # roles + institute + Platform Admin (+ demo if DEMO_MODE)
   chmod 600 .env
   npm ci && npm run build
STEPS

echo "4) Cron: add scheduler + daily backup (see docs/DEPLOY.md)"
echo "Done. Credentials saved to ~/.${SUB}_db_credentials"
