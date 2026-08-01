<?php

namespace App\Support;

use App\Models\ClientSetting;
use App\Models\FeatureFlag;
use Illuminate\Support\Facades\Schema;

/**
 * Single entry point for reading per-client configuration and branding.
 *
 * Values come from the client_settings table when present, otherwise from
 * config/client.php. Feature flags check the feature_flags table first.
 * Callers never change: client_setting('x') and feature('x').
 */
class ClientSettings
{
    /** @var array<string,mixed>|null cached DB overrides */
    protected ?array $overrides = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $overrides = $this->overrides();

        if (array_key_exists($key, $overrides)) {
            return $overrides[$key];
        }

        return config("client.{$key}", $default);
    }

    public function feature(string $flag): bool
    {
        if (Schema::hasTable('feature_flags')) {
            $row = FeatureFlag::where('key', $flag)->first();
            if ($row) {
                return (bool) $row->enabled;
            }
        }

        return (bool) config("client.features.{$flag}", false);
    }

    /**
     * Write a client setting override (used by the settings screen). Clears the
     * request cache so a subsequent read reflects the change.
     */
    public function set(string $key, mixed $value, string $type = 'string'): void
    {
        ClientSetting::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value, 'type' => $type],
        );

        $this->overrides = null;
    }

    /**
     * @return array<string,string>
     */
    public function branding(): array
    {
        return [
            'name' => (string) $this->get('institute_name', 'Coaching Institute'),
            'logo' => (string) $this->get('logo', ''),
            'brand_hue' => (string) $this->get('brand_hue', '#6366f1'),
            'action_color' => (string) $this->get('action_color', '#4338ca'),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    protected function overrides(): array
    {
        if ($this->overrides !== null) {
            return $this->overrides;
        }

        if (! Schema::hasTable('client_settings')) {
            return $this->overrides = [];
        }

        $out = [];
        foreach (ClientSetting::all() as $row) {
            $out[$row->key] = match ($row->type) {
                'bool' => filter_var($row->value, FILTER_VALIDATE_BOOLEAN),
                'int' => (int) $row->value,
                'json' => json_decode((string) $row->value, true),
                default => $row->value,
            };
        }

        return $this->overrides = $out;
    }
}
