# Client extensions

Per-client customization lives here, outside the core, so the core repository
stays mergeable across every client instance.

## Contract

- The core ships **no** extensions in this directory (only this README and the
  `.gitkeep`). A client instance adds its own.
- Any file named `*ServiceProvider.php` in this directory is auto-registered at
  boot by `App\Providers\ClientExtensionServiceProvider`. Namespace it
  `App\ClientExtensions`.
- Use an extension provider to override bindings, register client-specific
  Blade view namespaces, add routes, or swap a service implementation.
- Never edit core files to customize one client. If the core lacks a seam you
  need, add the seam to the core (benefiting every client) and override it here.

## Example

```php
<?php

namespace App\ClientExtensions;

use Illuminate\Support\ServiceProvider;

class AcmeCoachingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // e.g. override a report template, register client views, etc.
        $this->loadViewsFrom(app_path('ClientExtensions/acme/views'), 'acme');
    }
}
```
