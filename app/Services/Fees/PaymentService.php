<?php

namespace App\Services\Fees;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Student;
use App\Services\Audit\AuditLogger;
use App\Services\Numbering\NumberingService;
use DomainException;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private NumberingService $numbering,
        private LedgerService $ledger,
        private AuditLogger $audit,
    ) {}

    /**
     * Record an offline payment and allocate it across invoices, issuing a
     * numbered receipt. Cannot allocate beyond a payment's amount or beyond an
     * invoice's balance. Unallocated money is held as advance.
     *
     * @param  array<int,int>  $allocations  invoiceId => paise
     */
    public function record(Student $student, int $amount, string $mode, ?string $reference, string $date, array $allocations = []): Payment
    {
        if ($amount <= 0) {
            throw new DomainException('Payment amount must be positive.');
        }

        $allocations = array_filter($allocations, fn ($v) => (int) $v > 0);
        $sumAlloc = array_sum($allocations);

        if ($sumAlloc > $amount) {
            throw new DomainException('Allocations exceed the payment amount.');
        }

        return DB::transaction(function () use ($student, $amount, $mode, $reference, $date, $allocations, $sumAlloc) {
            $branchId = $student->branch_id;

            $invoices = Invoice::whereIn('id', array_keys($allocations))->lockForUpdate()->get()->keyBy('id');

            foreach ($allocations as $invoiceId => $amt) {
                $invoice = $invoices[$invoiceId] ?? null;
                if (! $invoice) {
                    throw new DomainException("Invoice {$invoiceId} not found.");
                }
                if ($amt > $invoice->balance) {
                    throw new DomainException("Allocation to invoice {$invoice->invoice_number} exceeds its balance.");
                }
            }

            $payment = Payment::create([
                'institute_id' => $student->institute_id,
                'branch_id' => $branchId,
                'student_id' => $student->id,
                'receipt_number' => $this->numbering->next($student->institute_id, 'receipt', $branchId),
                'payment_date' => $date,
                'mode' => $mode,
                'reference' => $reference,
                'amount' => $amount,
                'allocated' => $sumAlloc,
                'unallocated' => $amount - $sumAlloc,
                'status' => 'completed',
            ]);

            foreach ($allocations as $invoiceId => $amt) {
                PaymentAllocation::create(['payment_id' => $payment->id, 'invoice_id' => $invoiceId, 'amount' => $amt]);

                $invoice = $invoices[$invoiceId];
                $invoice->amount_paid += $amt;
                $invoice->balance -= $amt;
                $invoice->status = $invoice->balance <= 0 ? 'paid' : 'partial';
                $invoice->save();
            }

            $legs = [['account' => LedgerService::ACCT_CASH, 'debit' => $amount, 'narration' => "Receipt {$payment->receipt_number}"]];
            if ($sumAlloc > 0) {
                $legs[] = ['account' => LedgerService::ACCT_RECEIVABLE, 'credit' => $sumAlloc];
            }
            if ($amount - $sumAlloc > 0) {
                $legs[] = ['account' => LedgerService::ACCT_ADVANCE, 'credit' => $amount - $sumAlloc];
            }

            $this->ledger->post($legs, $payment, $date, $student->institute_id, $branchId);
            $this->audit->log('payment.recorded', $payment, after: ['receipt_number' => $payment->receipt_number, 'amount' => $amount]);

            return $payment;
        });
    }
}
