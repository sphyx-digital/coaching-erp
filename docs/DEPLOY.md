# Deploy, backup & monitoring

GitHub is the source of truth. Each client instance runs a tagged release on the
shared cPanel / AlmaLinux server, one cPanel account and one MySQL database per
client. The app lives under `~/coaching-erp` (outside the web root); the docroot
`public_html/coaching-erp/public` is a symlink to `~/coaching-erp/public`.

## Release (per client)

1. Tag a release: `git tag v1.x.y && git push origin v1.x.y`.
2. `.github/workflows/deploy.yml` builds (composer `--no-dev`, `npm run build`)
   and rsyncs the tag to the client's cPanel account over SSH, then runs
   `migrate --force` and clears caches.
3. Required GitHub secrets: `DEPLOY_SSH_KEY`, `DEPLOY_HOST`, `DEPLOY_PORT`,
   `DEPLOY_USER`, `DEPLOY_PATH`, `DEPLOY_PHP`.
4. A failed migration halts that client's release and alerts, without touching
   other clients. Deploys are idempotent (rsync + migrate).

## Provision a new client

Run `scripts/provision-client.sh` (checklist): create the cPanel subdomain and
database, place the per-client `.env` (DB creds, branding, GSTIN, feature flags),
point the docroot symlink, set the PHP handler to `ea-php82`, then
`php artisan migrate --force && php artisan db:seed --force`.

## Scheduler & queue (cPanel cron)

    * * * * *  <php82> /home/<acct>/coaching-erp/artisan schedule:run >/dev/null 2>&1
    # queue worker (supervised): a cron guard restarts it if it dies
    * * * * *  <php82> /home/<acct>/coaching-erp/artisan queue:work --stop-when-empty >/dev/null 2>&1

The scheduler runs the approvals escalation and any future crons.

## Backups & restore

- Daily DB backup: `scripts/backup.sh` (mysqldump + gzip, 14-day retention,
  optional `OFFSITE` rsync target). Cron: `30 1 * * *`.
- Restore: `scripts/restore.sh <backup.sql.gz> <target_db>`.
- Restore test (do periodically): create a scratch DB, restore the latest backup
  into it, confirm table/row counts, drop the scratch DB.

## Monitoring

- Uptime + error alerts on `/up` (health endpoint).
- Disk / resource check on the shared server (WHM).
- Queue-failure alert feeds the in-app **Message delivery** failure list.

## Incident: DB reset

If demo/live data is lost, `php artisan db:seed --force` restores roles, the
institute, the Platform Admin, and the demo dataset (idempotent).
