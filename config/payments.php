<?php

/*
 |--------------------------------------------------------------------------
 | Online payments
 |--------------------------------------------------------------------------
 | Driver "razorpay" uses the real API (keys in env); "fake" backs local,
 | demo and testing. Secrets live in env only.
 */

return [
    'driver' => env('PAYMENT_DRIVER', 'fake'),
    'key' => env('RAZORPAY_KEY'),
    'secret' => env('RAZORPAY_SECRET'),
    'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET', 'valid'),
];
