# Deviations from the locked spec

Deliberate, flagged departures from the phase prompts / build plan, with the reason and the
path back to spec. Reviewed each hardening pass.

## D1 — PHP 8.2 instead of 8.3
- **Spec:** Laravel 11 on PHP 8.3.
- **Reality:** the shared host `cdn.letsgetonline.in` has a working `ea-php82` CLI + Apache
  handler; `ea-php83` is registered in EA4 but its runtime binary is not installed. Installing
  it is a WHM/root action that changes PHP availability for every tenant on the shared box.
- **Impact:** none functional — Laravel 11 requires PHP 8.2+; all required extensions present.
- **Back to spec:** root installs `ea-php83-php-*`, switch the vhost via MultiPHP, bump
  `composer.json` `require.php` to `^8.3`. Do during Phase 18 if the client requires parity.

## D2 — Docroot via symlink
- **Spec:** conventional Laravel `public/` docroot.
- **Reality:** cPanel created the subdomain docroot at `~/public_html/coaching-erp/public`.
  Rather than edit shared Apache config as root, that `public` subdir is a **symlink** to the
  app's real `~/coaching-erp/public`. Fully in userspace, reversible, AutoSSL renews normally.
- **Back to spec:** Phase 17 can set the vhost docroot directly to `~/coaching-erp/public` via
  WHM and drop the symlink.

## D3 — Composer security-audit block disabled
- **Spec:** implicit — clean dependency set.
- **Reality:** Composer 2.9 blocks the entire installable Laravel 11.31–11.55 range under open
  advisories with no satisfying patched 11.x, dead-ending the resolver. `audit.block-insecure`
  is set to `false` in `composer.json` to install the latest 11.x.
- **Back to spec:** Phase 18 re-audits and bumps the framework to a patched release (or Laravel
  12) once one satisfies the advisories, then re-enables the block.
