<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Audit\AuditLogger;
use App\Services\Fees\PaymentService;

/**
 * Bridges the payment gateway to the fee ledger. A captured payment posts
 * through the same audited path as an offline receipt; a failure leaves the
 * invoice unpaid; a repeated webhook never double-posts (idempotent by
 * gateway payment id).
 */
class OnlinePaymentService
{
    public function __construct(
        private PaymentService $payments,
        private AuditLogger $audit,
    ) {}

    public function gateway(): PaymentGateway
    {
        return match (config('payments.driver')) {
            'razorpay' => new RazorpayGateway,
            default => new FakeGateway,
        };
    }

    /** Start a checkout for an invoice's balance. */
    public function initiate(Invoice $invoice): array
    {
        return $this->gateway()->createOrder((int) $invoice->balance, ['invoice_id' => $invoice->id]);
    }

    /**
     * Process a gateway webhook. Verifies the signature, is idempotent, and
     * posts a receipt only on capture.
     *
     * @return array{status:string, receipt?:string}
     */
    public function handleWebhook(string $rawBody, ?string $signature): array
    {
        $gateway = $this->gateway();

        if (! $gateway->verifySignature($rawBody, $signature)) {
            return ['status' => 'invalid_signature'];
        }

        $payload = json_decode($rawBody, true) ?: [];
        $paymentId = $payload['payment_id'] ?? null;

        // Idempotency: a repeated callback must not double-post.
        if ($paymentId && Payment::where('gateway_payment_id', $paymentId)->exists()) {
            return ['status' => 'duplicate'];
        }

        if (($payload['status'] ?? null) !== 'captured') {
            return ['status' => 'unpaid']; // failed/pending — invoice stays unpaid
        }

        $invoice = Invoice::withoutGlobalScopes()->find($payload['invoice_id'] ?? null);
        if (! $invoice) {
            return ['status' => 'unknown_invoice'];
        }

        $amount = min((int) ($payload['amount'] ?? $invoice->balance), (int) $invoice->balance);
        $payment = $this->payments->record(
            $invoice->student, $amount, 'online', $paymentId, now()->toDateString(), [$invoice->id => $amount],
        );
        $payment->update([
            'gateway' => $gateway->name(),
            'gateway_payment_id' => $paymentId,
            'gateway_order_id' => $payload['order_id'] ?? null,
        ]);

        $this->audit->log('payment.online', $payment, after: ['gateway_payment_id' => $paymentId, 'amount' => $amount]);

        return ['status' => 'posted', 'receipt' => $payment->receipt_number];
    }
}
