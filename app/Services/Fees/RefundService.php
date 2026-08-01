<?php

namespace App\Services\Fees;

use App\Models\Payment;
use App\Models\Refund;
use App\Services\Audit\AuditLogger;
use App\Services\Numbering\NumberingService;
use DomainException;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(
        private NumberingService $numbering,
        private LedgerService $ledger,
        private AuditLogger $audit,
    ) {}

    /** How much of a payment may still be refunded. */
    public function refundableAmount(Payment $payment): int
    {
        $alreadyRefunded = (int) $payment->refunds()->where('status', 'completed')->sum('amount');
        $max = $payment->amount - $alreadyRefunded;

        // Where the client configures advance as non-refundable, unallocated
        // advance cannot be refunded - only the allocated portion.
        if (client_setting('advance_non_refundable', false)) {
            $max = min($max, $payment->allocated - $alreadyRefunded);
        }

        return max($max, 0);
    }

    public function refund(Payment $payment, int $amount, ?string $reason, string $date, string $mode = 'cash'): Refund
    {
        if ($amount <= 0) {
            throw new DomainException('Refund amount must be positive.');
        }

        if ($amount > $this->refundableAmount($payment)) {
            throw new DomainException('Refund exceeds the refundable amount.');
        }

        return DB::transaction(function () use ($payment, $amount, $reason, $date, $mode) {
            $refund = Refund::create([
                'institute_id' => $payment->institute_id,
                'payment_id' => $payment->id,
                'refund_number' => $this->numbering->next($payment->institute_id, 'refund', $payment->branch_id),
                'refund_date' => $date,
                'amount' => $amount,
                'mode' => $mode,
                'notes' => $reason,
                'status' => 'completed',
            ]);

            // Compensating entry: money leaves (credit cash), refund recorded (debit).
            $this->ledger->post([
                ['account' => LedgerService::ACCT_REFUND, 'debit' => $amount, 'narration' => "Refund {$refund->refund_number}"],
                ['account' => LedgerService::ACCT_CASH, 'credit' => $amount],
            ], $refund, $date, $payment->institute_id, $payment->branch_id);

            $this->audit->log('refund.processed', $refund, after: ['refund_number' => $refund->refund_number, 'amount' => $amount]);

            return $refund;
        });
    }
}
