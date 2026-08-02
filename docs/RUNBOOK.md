# Operations runbook

Short, actionable procedures for running Coaching ERP instances on the shared
cPanel / AlmaLinux server. See `docs/DEPLOY.md` for detail.

## Deploy a release
1. `git tag vX.Y.Z && git push origin vX.Y.Z` → the Deploy workflow builds and
   rsyncs the tag to the client account, runs `migrate --force`, clears caches.
2. Verify `https://<client>/up` returns `{"status":"ok"}`.

## Add a new client
`scripts/provision-client.sh <subdomain> <db> <dbuser>` → creates DB + subdomain
+ docroot symlink + PHP handler; then deploy code, set `.env` (DB, `CLIENT_*`,
GSTIN, flags), `key:generate`, `migrate --force`, `db:seed --force`, `chmod 600 .env`,
`npm ci && npm run build`. Add the scheduler + backup cron.

## Backup & restore
- Backups run daily (`scripts/backup.sh`, 14-day retention) to `~/backups/coaching`.
- Manual backup: `bash scripts/backup.sh`.
- Restore: `scripts/restore.sh <backup.sql.gz> <target_db>`.
- Restore test: create a scratch DB, restore the latest backup, confirm table
  count, drop the scratch DB.

## Rotate secrets
- App key: only if compromised (invalidates sessions/encrypted values) —
  `php artisan key:generate`, then re-encrypt any stored secrets.
- DB password: change in cPanel MySQL, update `.env` `DB_PASSWORD` and
  `~/.<sub>_db_credentials`.
- Gateway/webhook secrets and mail creds: env only; never commit.

## Security posture
- App lives outside the web root; only `public/` is symlinked. `.env` is `600`.
- Security headers via middleware; login and payment webhook are rate-limited.
- RBAC + branch scoping on every screen; audit trail + override log for
  sensitive actions; consent-gated messaging.

## Incident: data reset / corruption
`php artisan db:seed --force` restores roles, institute, Platform Admin and the
demo dataset (idempotent). For real data loss, restore the latest backup.

## Incident: security
1. Rotate affected secrets (above). 2. Review the audit log and override log.
3. If a credential leaked, force password resets. 4. Restore from a clean backup
   if integrity is in doubt. 5. Record the timeline.
