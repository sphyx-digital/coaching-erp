<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Client extension loader.
 *
 * Per-client customization lives OUTSIDE the core, under app/ClientExtensions,
 * so the core repository stays mergeable across every client instance. Any
 * *ServiceProvider.php placed there is auto-registered on boot. A client
 * instance enables only its own extension; the core ships none.
 *
 * See app/ClientExtensions/README.md for the contract.
 */
class ClientExtensionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $dir = app_path('ClientExtensions');

        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir.'/*ServiceProvider.php') ?: [] as $file) {
            $class = 'App\\ClientExtensions\\'.basename($file, '.php');

            if (class_exists($class)) {
                $this->app->register($class);
            }
        }
    }
}
