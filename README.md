# Coaching Institute ERP

A productized SaaS ERP for coaching and test-prep institutes (India), by **Sphyx Digital**.
One codebase, deployed as a **separate instance per client** on the shared cPanel / AlmaLinux
server, with an **isolated database per client**. GitHub is the source of truth; per-client
customization lives in a config and extension layer so the core stays mergeable.

## Stack

| Layer | Choice |
|-------|--------|
| Backend | Laravel 11, PHP 8.2¹, Eloquent |
| Database | MySQL 8 / MariaDB 10.11 (one per client) |
| Front end | Blade + Livewire 3, Alpine.js, Vite, PWA |
| Auth / RBAC | Laravel auth + spatie/laravel-permission + branch scoping |
| Background | Laravel database queue + scheduler |
| CI / CD | GitHub Actions → cPanel over SSH/rsync |

¹ **Stack deviation — PHP 8.2, not 8.3.** The locked stack specifies PHP 8.3, but the shared
host only has a working `ea-php82` CLI/handler; `ea-php83` is registered without a runtime.
Laravel 11's floor is PHP 8.2, so the product runs cleanly on it. This is fully reversible: to
reach exact 8.3 parity, root installs `ea-php83-php-cli` + extensions, the vhost is switched to
`ea-php83`, and `composer.json`'s `require.php` is bumped. Tracked in `DEVIATIONS.md`.

## Design system

Every screen conforms to the design system contract: a token-driven interface (CSS custom
properties in `resources/css/tokens.css`), Poppins headings + DM Sans body, and a two-token
brand model — a **decorative brand hue** and a **darker accessible action colour** used behind
white text. The action colour is contrast-checked against white **at boot** (`App\Support\ThemeGuard`);
an inaccessible theme fails loudly. Status is never carried by colour alone.

## Local setup

```bash
composer install
cp .env.example .env      # then set DB_* and CLIENT_* values
php artisan key:generate
php artisan migrate
composer serve            # http://127.0.0.1:8000
npm install && npm run dev # assets
```

Composer scripts: `composer setup`, `composer test`, `composer lint`, `composer serve`.

## Per-client config & branding

Read one setting with `client_setting('institute_name')`; read a flag with `feature('online_payments')`
(unknown flags are off). Config lives in `config/client.php` and `.env` (`CLIENT_*`, `FEATURE_*`);
from Phase 1 the `client_settings` table overrides config at runtime. Client-specific code goes in
`app/ClientExtensions/` (auto-loaded) — never edit core files to customize one client.

## Deployment (this instance)

- App: `~/coaching-erp` on the Sphyx cPanel account.
- Live at **https://coaching.sphyx.in** (AutoSSL), PHP handler `ea-php82`.
- Docroot: cPanel serves `~/public_html/coaching-erp/public`, which is a **symlink** to
  `~/coaching-erp/public` (standard cPanel + Laravel layout). See `DEVIATIONS.md`.

## Phase status

- **Phase 0 — done:** scaffold, environment, design tokens, app shell, per-client config +
  branding, boot-time contrast guard, feature flags, client extension loader, PWA skeleton, CI.
- Phases 1–9 (data model → assessments) in progress per the build plan.
