<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Razorpay driver. Creates orders via the Orders API and verifies webhook
 * signatures with the webhook secret (HMAC SHA256). Keys live in env only.
 */
class RazorpayGateway implements PaymentGateway
{
    public function createOrder(int $amountPaise, array $meta = []): array
    {
        $key = config('payments.key');
        $secret = config('payments.secret');

        if (! $key || ! $secret) {
            throw new RuntimeException('Razorpay keys are not configured.');
        }

        $response = Http::withBasicAuth($key, $secret)
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amountPaise,
                'currency' => 'INR',
                'notes' => $meta,
            ])->throw()->json();

        return [
            'order_id' => $response['id'],
            'amount' => $amountPaise,
            'currency' => 'INR',
            'key' => $key,
        ];
    }

    public function verifySignature(string $rawBody, ?string $signature): bool
    {
        $secret = (string) config('payments.webhook_secret');
        if (! $secret || ! $signature) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }

    public function name(): string
    {
        return 'razorpay';
    }
}
