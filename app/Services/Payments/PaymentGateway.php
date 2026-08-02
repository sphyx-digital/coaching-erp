<?php

namespace App\Services\Payments;

/**
 * Online payment gateway driver. Razorpay is the default implementation; a fake
 * driver backs local/demo/testing. Alternates are added behind this interface.
 */
interface PaymentGateway
{
    /**
     * Create a payment order for an amount in paise.
     *
     * @return array{order_id:string, amount:int, currency:string, key?:string}
     */
    public function createOrder(int $amountPaise, array $meta = []): array;

    /** Verify a webhook signature against the raw body. */
    public function verifySignature(string $rawBody, ?string $signature): bool;

    public function name(): string;
}
