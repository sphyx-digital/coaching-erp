<?php

namespace App\Services\Exceptions;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Audit\AuditLogger;
use App\Services\Fees\LedgerService;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Controlled exception paths for finance and academics. Reversals are
 * compensating (never destructive), always audited, and never touch history.
 */
class ExceptionService
{
    public function __construct(private LedgerService $ledger, private AuditLogger $audit) {}

    /**
     * Void a receipt / reverse a payment: restore invoice balances and post a
     * balancing ledger entry. The original payment is kept, marked reversed.
     */
    public function reversePayment(Payment $payment, ?string $reason = null, ?string $date = null): Payment
    {
        if ($payment->status === 'reversed') {
            throw new DomainException('This payment is already reversed.');
        }

        $date ??= now()->toDateString();

        return DB::transaction(function () use ($payment, $reason, $date) {
            // Restore balances on the invoices this payment was allocated to.
            foreach ($payment->allocations()->with('invoice')->get() as $alloc) {
                if ($invoice = $alloc->invoice) {
                    $invoice->amount_paid = max(0, $invoice->amount_paid - $alloc->amount);
                    $invoice->balance = $invoice->balance + $alloc->amount;
                    $invoice->status = $invoice->amount_paid > 0 ? 'partial' : 'issued';
                    $invoice->save();
                }
            }

            // Compensating entry: money out (credit cash), receivable/advance restored (debit).
            $legs = [['account' => LedgerService::ACCT_CASH, 'credit' => $payment->amount, 'narration' => 'Reversal of '.$payment->receipt_number]];
            if ($payment->allocated > 0) {
                $legs[] = ['account' => LedgerService::ACCT_RECEIVABLE, 'debit' => $payment->allocated];
            }
            if ($payment->unallocated > 0) {
                $legs[] = ['account' => LedgerService::ACCT_ADVANCE, 'debit' => $payment->unallocated];
            }
            $this->ledger->post($legs, $payment, $date, $payment->institute_id, $payment->branch_id);

            $payment->update(['status' => 'reversed']);
            $this->audit->log('payment.reversed', $payment,
                before: ['status' => 'completed'],
                after: ['status' => 'reversed', 'reason' => $reason],
            );

            return $payment;
        });
    }

    /**
     * Cancel an unpaid invoice with a reversing ledger entry, keeping the original.
     */
    public function cancelInvoice(Invoice $invoice, ?string $reason = null, ?string $date = null): Invoice
    {
        if ($invoice->status === 'cancelled') {
            throw new DomainException('This invoice is already cancelled.');
        }
        if ($invoice->amount_paid > 0) {
            throw new DomainException('Reverse the payments before cancelling this invoice.');
        }

        $date ??= now()->toDateString();

        return DB::transaction(function () use ($invoice, $reason, $date) {
            $legs = [
                ['account' => LedgerService::ACCT_RECEIVABLE, 'credit' => $invoice->total, 'narration' => 'Cancel '.$invoice->invoice_number],
                ['account' => LedgerService::ACCT_FEE_INCOME, 'debit' => $invoice->subtotal - $invoice->discount_total],
            ];
            if ($invoice->tax_total > 0) {
                $legs[] = ['account' => LedgerService::ACCT_GST_PAYABLE, 'debit' => $invoice->tax_total];
            }
            if ($invoice->discount_total > 0) {
                $legs[] = ['account' => LedgerService::ACCT_FEE_INCOME, 'credit' => $invoice->discount_total];
            }
            $this->ledger->post($legs, $invoice, $date, $invoice->institute_id, $invoice->branch_id);

            $invoice->update(['status' => 'cancelled', 'balance' => 0]);
            $this->audit->log('invoice.cancelled', $invoice, after: ['reason' => $reason]);

            return $invoice;
        });
    }

    /**
     * Guard a backdated entry: only within the configured window, else block.
     */
    public function assertBackdateAllowed(string $date): void
    {
        $window = (int) client_setting('backdate_window_days', 7);
        $earliest = now()->subDays($window)->startOfDay();

        if (Carbon::parse($date)->startOfDay()->lt($earliest)) {
            throw new DomainException("Backdated entries are only allowed within {$window} days.");
        }
    }
}
