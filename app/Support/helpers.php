<?php

use App\Support\ClientSettings;

if (! function_exists('client_settings')) {
    /**
     * Resolve the ClientSettings singleton.
     */
    function client_settings(): ClientSettings
    {
        return app(ClientSettings::class);
    }
}

if (! function_exists('client_setting')) {
    /**
     * Read a single per-client setting, e.g. client_setting('institute_name').
     */
    function client_setting(string $key, mixed $default = null): mixed
    {
        return client_settings()->get($key, $default);
    }
}

if (! function_exists('feature')) {
    /**
     * Is a feature flag on? Unknown flags are off, e.g. feature('online_payments').
     */
    function feature(string $flag): bool
    {
        return client_settings()->feature($flag);
    }
}

if (! function_exists('current_institute')) {
    /**
     * The single institute for this instance (one institute per client).
     */
    function current_institute(): ?\App\Models\Institute
    {
        return \App\Models\Institute::query()->orderBy('id')->first();
    }
}

if (! function_exists('active_session')) {
    /**
     * The active academic session for this instance.
     */
    function active_session(): ?\App\Models\AcademicSession
    {
        return \App\Models\AcademicSession::query()->where('is_active', true)->orderByDesc('id')->first();
    }
}

if (! function_exists('paise_to_rupees')) {
    /**
     * Format integer paise as a full Indian-grouped rupee string (no paise shown
     * when whole). Money is stored in integer paise everywhere.
     */
    function paise_to_rupees(int $paise, bool $sign = true): string
    {
        $rupees = intdiv(abs($paise), 100);
        $fraction = abs($paise) % 100;

        // Indian digit grouping (lakh/crore)
        $s = (string) $rupees;
        if (strlen($s) > 3) {
            $last3 = substr($s, -3);
            $rest = substr($s, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $grouped = $rest.','.$last3;
        } else {
            $grouped = $s;
        }

        $out = ($paise < 0 ? '-' : '').($sign ? '₹' : '').$grouped;
        if ($fraction > 0) {
            $out .= '.'.str_pad((string) $fraction, 2, '0', STR_PAD_LEFT);
        }

        return $out;
    }
}
