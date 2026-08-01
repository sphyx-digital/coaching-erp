<?php

/*
 |--------------------------------------------------------------------------
 | Per-client configuration and branding
 |--------------------------------------------------------------------------
 |
 | One productized codebase, one deployed instance per client. This file is
 | the config-file half of the client_settings mechanism; the database half
 | (the client_settings table) is completed in Phase 1 and, once present,
 | overrides these values at runtime through the ClientSettings service.
 |
 | Read a single setting with the client_setting() helper, e.g.
 |   client_setting('institute_name')
 | Read a feature flag with feature(), e.g. feature('online_payments').
 |
 | Branding carries TWO brand colours by contract:
 |   brand_hue    decorative hue (logo, accents) - may fail contrast at button size
 |   action_color darker accessible colour used behind white text (must pass WCAG AA)
 | The action colour is contrast-checked against white at boot; an inaccessible
 | value fails loudly so no client can ship an unreadable theme.
 */

return [

    // Institute identity
    'institute_name' => env('CLIENT_NAME', 'Coaching Institute'),
    'gstin' => env('CLIENT_GSTIN', ''),
    'logo' => env('CLIENT_LOGO', ''), // URL or storage path; empty = initial monogram

    // Branding (two-token model)
    'brand_hue' => env('CLIENT_BRAND_HUE', '#6366f1'),
    'action_color' => env('CLIENT_ACTION_COLOR', '#4338ca'),

    // Reject an inaccessible action colour at boot (WCAG AA vs white text).
    'enforce_contrast' => env('CLIENT_ENFORCE_CONTRAST', true),

    // Locale
    'currency' => env('CLIENT_CURRENCY', 'INR'),
    'currency_sign' => '₹',
    'timezone' => env('CLIENT_TIMEZONE', 'Asia/Kolkata'), // display tz; storage is UTC

    // Feature flags (read through feature()). A missing flag is treated as off.
    'features' => [
        'online_payments' => env('FEATURE_ONLINE_PAYMENTS', false),
        'whatsapp' => env('FEATURE_WHATSAPP', false),
        'sms' => env('FEATURE_SMS', false),
        'email' => env('FEATURE_EMAIL', true),
        'parent_portal' => env('FEATURE_PARENT_PORTAL', true),
    ],

];
