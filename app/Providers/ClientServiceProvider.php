<?php

namespace App\Providers;

use App\Support\ClientSettings;
use App\Support\ThemeGuard;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ClientSettings::class, fn () => new ClientSettings);
    }

    public function boot(): void
    {
        // Fail loudly at boot if the client action colour is inaccessible.
        // Guarded so console/migration commands on a raw checkout still run.
        if (config('client.enforce_contrast', true)) {
            ThemeGuard::verify((string) config('client.action_color', '#4338ca'));
        }

        // Every back-office and portal view gets branding, recomputed per render
        // (not shared once at boot) so per-request client settings are honoured.
        View::composer('*', function ($view) {
            $view->with('branding', app(ClientSettings::class)->branding());
        });
    }
}
