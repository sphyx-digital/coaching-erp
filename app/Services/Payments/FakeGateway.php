<?php

namespace App\Services\Payments;

use Illuminate\Support\Str;

/**
 * Deterministic gateway for local, demo and test environments. Signature is
 * valid when it equals the shared secret (default "valid").
 */
class FakeGateway implements PaymentGateway
{
    public function createOrder(int $amountPaise, array $meta = []): array
    {
        return [
            'order_id' => 'order_'.Str::random(14),
            'amount' => $amountPaise,
            'currency' => 'INR',
            'key' => 'fake_key',
        ];
    }

    public function verifySignature(string $rawBody, ?string $signature): bool
    {
        return $signature === (string) config('payments.webhook_secret', 'valid');
    }

    public function name(): string
    {
        return 'fake';
    }
}
