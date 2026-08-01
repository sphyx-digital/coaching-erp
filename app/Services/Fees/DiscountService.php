<?php

namespace App\Services\Fees;

use App\Models\Discount;
use App\Models\Invoice;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Applies a discount to an issued invoice as a concession that reduces the
 * receivable balance, with a reason and an approver reference (the approval
 * workflow itself lands in Phase 11). The ledger stays balanced.
 */
class DiscountService
{
    public function __construct(private LedgerService $ledger, private AuditLogger $audit) {}

    public function applyToInvoice(Invoice $invoice, string $kind, int $value, ?string $reason = null, ?int $approverId = null): Discount
    {
        $amount = $kind === 'percent'
            ? (int) round($invoice->subtotal * $value / 10000)
            : $value;

        $amount = max(0, min($amount, $invoice->balance));

        return DB::transaction(function () use ($invoice, $kind, $value, $amount, $reason, $approverId) {
            $discount = Discount::create([
                'institute_id' => $invoice->institute_id,
                'invoice_id' => $invoice->id,
                'name' => $reason ?: 'Discount',
                'kind' => $kind,
                'amount' => $kind === 'fixed' ? $amount : 0,
                'percent_bp' => $kind === 'percent' ? $value : 0,
                'reason' => $reason,
                'approver_id' => $approverId,
                'approved_at' => $approverId ? now() : null,
            ]);

            $invoice->discount_total += $amount;
            $invoice->total -= $amount;
            $invoice->balance -= $amount;
            $invoice->status = $invoice->balance <= 0 ? 'paid' : ($invoice->amount_paid > 0 ? 'partial' : 'issued');
            $invoice->save();

            $this->ledger->post([
                ['account' => LedgerService::ACCT_FEE_INCOME, 'debit' => $amount, 'narration' => 'Discount on '.$invoice->invoice_number],
                ['account' => LedgerService::ACCT_RECEIVABLE, 'credit' => $amount],
            ], $discount, now()->toDateString(), $invoice->institute_id, $invoice->branch_id);

            $this->audit->log('discount.applied', $invoice, after: ['discount' => $amount, 'reason' => $reason]);

            return $discount;
        });
    }
}
