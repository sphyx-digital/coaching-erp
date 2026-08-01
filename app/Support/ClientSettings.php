<?php

namespace App\Support;

/**
 * Single entry point for reading per-client configuration and branding.
 *
 * Phase 0: values come from config/client.php.
 * Phase 1+: the client_settings database table overrides config values here,
 * so callers never change. Reading a setting is one call: client_setting('x').
 */
class ClientSettings
{
    /** @var array<string,mixed>|null cached DB overrides */
    protected ?array $overrides = null;

    /**
     * Read a single client setting by dot key, falling back to config/client.php.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $overrides = $this->overrides();

        if (array_key_exists($key, $overrides)) {
            return $overrides[$key];
        }

        return config("client.{$key}", $default);
    }

    /**
     * Is a feature flag on? A missing flag is treated as off.
     */
    public function feature(string $flag): bool
    {
        $overrides = $this->overrides();
        $key = "features.{$flag}";

        if (array_key_exists($key, $overrides)) {
            return (bool) $overrides[$key];
        }

        return (bool) config("client.features.{$flag}", false);
    }

    /**
     * Branding block used by the app shell and PWA manifest.
     *
     * @return array<string,string>
     */
    public function branding(): array
    {
        return [
            'name'         => (string) $this->get('institute_name', 'Coaching Institute'),
            'logo'         => (string) $this->get('logo', ''),
            'brand_hue'    => (string) $this->get('brand_hue', '#6366f1'),
            'action_color' => (string) $this->get('action_color', '#4338ca'),
        ];
    }

    /**
     * Load DB overrides once per request. Returns [] until the client_settings
     * table exists (Phase 1) or when running before migration.
     *
     * @return array<string,mixed>
     */
    protected function overrides(): array
    {
        if ($this->overrides !== null) {
            return $this->overrides;
        }

        // Phase 1 wires the client_settings table read here. Kept empty and
        // exception-safe so Phase 0 boots on a fresh database.
        return $this->overrides = [];
    }
}
