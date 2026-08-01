<?php

namespace App\Services\Fees;

use App\Models\Enrollment;
use App\Models\FeePlan;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Student;
use App\Services\Audit\AuditLogger;
use App\Services\Numbering\NumberingService;
use Illuminate\Support\Facades\DB;

class FeeService
{
    public function __construct(
        private NumberingService $numbering,
        private LedgerService $ledger,
        private AuditLogger $audit,
    ) {}

    /**
     * Raise an invoice for a whole fee plan (one line per component).
     */
    public function invoiceForPlan(Enrollment $enrollment, FeePlan $plan, bool $interstate = false, ?string $date = null): Invoice
    {
        $lines = $plan->components->map(fn ($c) => [
            'description' => $c->name,
            'taxable_value' => (int) $c->amount,
            'rate_bp' => $c->is_taxable && $c->taxRate ? (int) $c->taxRate->rate_bp : 0,
            'fee_component_id' => $c->id,
        ])->all();

        return $this->createInvoice($enrollment->student, $enrollment, $lines, $interstate, $date);
    }

    /**
     * Build an invoice from explicit lines. GST is split per line; the sum of
     * lines equals the invoice total to the paisa. A balanced ledger posting is
     * written. Invoice number comes only from the numbering service.
     *
     * @param  array<array{description:string,taxable_value:int,rate_bp:int,fee_component_id?:?int}>  $lines
     */
    public function createInvoice(Student $student, ?Enrollment $enrollment, array $lines, bool $interstate, ?string $date = null, ?int $feeScheduleId = null): Invoice
    {
        $date ??= now()->toDateString();
        $branchId = $enrollment?->branch_id ?? $student->branch_id;
        $sessionId = $enrollment?->academic_session_id;

        return DB::transaction(function () use ($student, $enrollment, $lines, $interstate, $date, $feeScheduleId, $branchId, $sessionId) {
            $invoice = Invoice::create([
                'institute_id' => $student->institute_id,
                'branch_id' => $branchId,
                'student_id' => $student->id,
                'enrollment_id' => $enrollment?->id,
                'fee_schedule_id' => $feeScheduleId,
                'invoice_number' => $this->numbering->next($student->institute_id, 'invoice', $branchId, $sessionId),
                'invoice_date' => $date,
                'is_interstate' => $interstate,
                'status' => 'issued',
            ]);

            $subtotal = $cgst = $sgst = $igst = 0;

            foreach ($lines as $line) {
                $g = GstCalculator::forLine((int) $line['taxable_value'], (int) $line['rate_bp'], $interstate);

                InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'fee_component_id' => $line['fee_component_id'] ?? null,
                    'description' => $line['description'],
                    'tax_rate_bp' => $g['rate_bp'],
                    'taxable_value' => $g['taxable'],
                    'cgst' => $g['cgst'],
                    'sgst' => $g['sgst'],
                    'igst' => $g['igst'],
                    'line_total' => $g['total'],
                ]);

                $subtotal += $g['taxable'];
                $cgst += $g['cgst'];
                $sgst += $g['sgst'];
                $igst += $g['igst'];
            }

            $taxTotal = $cgst + $sgst + $igst;
            $total = $subtotal + $taxTotal;

            $invoice->update([
                'subtotal' => $subtotal,
                'discount_total' => 0,
                'cgst_total' => $cgst,
                'sgst_total' => $sgst,
                'igst_total' => $igst,
                'tax_total' => $taxTotal,
                'total' => $total,
                'amount_paid' => 0,
                'balance' => $total,
            ]);

            $legs = [
                ['account' => LedgerService::ACCT_RECEIVABLE, 'debit' => $total, 'narration' => "Invoice {$invoice->invoice_number}"],
                ['account' => LedgerService::ACCT_FEE_INCOME, 'credit' => $subtotal],
            ];
            if ($taxTotal > 0) {
                $legs[] = ['account' => LedgerService::ACCT_GST_PAYABLE, 'credit' => $taxTotal];
            }

            $this->ledger->post($legs, $invoice, $date, $student->institute_id, $branchId);
            $this->audit->log('invoice.created', $invoice, after: ['invoice_number' => $invoice->invoice_number, 'total' => $total]);

            return $invoice->refresh();
        });
    }
}
