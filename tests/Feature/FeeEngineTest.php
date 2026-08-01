<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\FeeComponent;
use App\Models\FeePlan;
use App\Models\Institute;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\TaxRate;
use App\Services\Fees\DiscountService;
use App\Services\Fees\FeeService;
use App\Services\Fees\LedgerService;
use App\Services\Fees\PaymentService;
use App\Services\Fees\RefundService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeEngineTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private Enrollment $enrollment;

    private FeePlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institute = Institute::create(['name' => 'Acme', 'state_code' => '27']);
        $branch = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Main', 'code' => 'MN']);
        $session = AcademicSession::create(['institute_id' => $this->institute->id, 'name' => '2026-27', 'is_active' => true]);
        $course = Course::create(['institute_id' => $this->institute->id, 'name' => 'JEE', 'code' => 'JEE']);
        $student = Student::create(['institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'name' => 'Riya']);
        $this->enrollment = Enrollment::create([
            'institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'student_id' => $student->id,
            'course_id' => $course->id, 'academic_session_id' => $session->id, 'status' => 'active',
        ]);

        $gst18 = TaxRate::create(['institute_id' => $this->institute->id, 'name' => 'GST 18%', 'rate_bp' => 1800]);
        $this->plan = FeePlan::create(['institute_id' => $this->institute->id, 'course_id' => $course->id, 'name' => 'JEE Plan']);
        FeeComponent::create(['fee_plan_id' => $this->plan->id, 'tax_rate_id' => $gst18->id, 'name' => 'Tuition', 'is_taxable' => true, 'amount' => 100000]);
        FeeComponent::create(['fee_plan_id' => $this->plan->id, 'tax_rate_id' => $gst18->id, 'name' => 'Registration', 'is_taxable' => true, 'amount' => 20000]);
        FeeComponent::create(['fee_plan_id' => $this->plan->id, 'name' => 'Material', 'is_taxable' => false, 'amount' => 5000]);
    }

    private function fees(): FeeService
    {
        return app(FeeService::class);
    }

    private function invoice(bool $interstate = false): Invoice
    {
        return $this->fees()->invoiceForPlan($this->enrollment->fresh(), $this->plan->fresh()->load('components.taxRate'), $interstate);
    }

    public function test_in_state_invoice_splits_cgst_sgst_and_sums_to_total(): void
    {
        $invoice = $this->invoice(interstate: false);

        $this->assertSame(125000, $invoice->subtotal);
        $this->assertSame(10800, $invoice->cgst_total);
        $this->assertSame(10800, $invoice->sgst_total);
        $this->assertSame(0, $invoice->igst_total);
        $this->assertSame(21600, $invoice->tax_total);
        $this->assertSame(146600, $invoice->total);

        // Sum of line totals equals the invoice total to the paisa.
        $this->assertSame(146600, (int) $invoice->lines()->sum('line_total'));
        $this->assertSame($invoice->subtotal, (int) $invoice->lines()->sum('taxable_value'));
    }

    public function test_out_of_state_invoice_is_igst_only(): void
    {
        $invoice = $this->invoice(interstate: true);

        $this->assertSame(21600, $invoice->igst_total);
        $this->assertSame(0, $invoice->cgst_total);
        $this->assertSame(146600, $invoice->total);
    }

    public function test_invoice_posting_balances_the_ledger(): void
    {
        $this->invoice();
        $this->assertTrue(app(LedgerService::class)->isBalanced($this->institute->id));
    }

    public function test_partial_payment_leaves_a_correct_balance(): void
    {
        $invoice = $this->invoice();
        $student = $invoice->student;

        app(PaymentService::class)->record($student, 50000, 'cash', null, '2026-08-01', [$invoice->id => 50000]);

        $invoice->refresh();
        $this->assertSame(50000, $invoice->amount_paid);
        $this->assertSame(96600, $invoice->balance);
        $this->assertSame('partial', $invoice->status);
    }

    public function test_over_allocation_is_blocked(): void
    {
        $invoice = $this->invoice();

        $this->expectException(DomainException::class);
        app(PaymentService::class)->record($invoice->student, 200000, 'cash', null, '2026-08-01', [$invoice->id => 200000]);
    }

    public function test_receipt_numbers_are_unique_across_payments(): void
    {
        $invoice = $this->invoice();
        $student = $invoice->student;
        $svc = app(PaymentService::class);

        $r1 = $svc->record($student, 1000, 'cash', null, '2026-08-01');
        $r2 = $svc->record($student, 1000, 'cash', null, '2026-08-01');
        $r3 = $svc->record($student, 1000, 'cash', null, '2026-08-01');

        $this->assertSame(['RCPT/0001', 'RCPT/0002', 'RCPT/0003'], [$r1->receipt_number, $r2->receipt_number, $r3->receipt_number]);
    }

    public function test_refund_cannot_exceed_the_received_amount(): void
    {
        $invoice = $this->invoice();
        $payment = app(PaymentService::class)->record($invoice->student, 50000, 'cash', null, '2026-08-01', [$invoice->id => 50000]);
        $refunds = app(RefundService::class);

        $this->assertSame(50000, $refunds->refundableAmount($payment));
        $refunds->refund($payment, 30000, 'Withdrew', '2026-08-02');
        $this->assertSame(20000, $refunds->refundableAmount($payment->fresh()));

        $this->expectException(DomainException::class);
        $refunds->refund($payment->fresh(), 30000, 'Too much', '2026-08-03');
    }

    public function test_discount_reduces_balance_and_books_stay_balanced(): void
    {
        $invoice = $this->invoice();

        app(DiscountService::class)->applyToInvoice($invoice, 'fixed', 10000, 'Sibling discount');

        $invoice->refresh();
        $this->assertSame(10000, $invoice->discount_total);
        $this->assertSame(136600, $invoice->balance);
        $this->assertTrue(app(LedgerService::class)->isBalanced($this->institute->id));
    }

    public function test_full_flow_keeps_the_ledger_balanced(): void
    {
        $invoice = $this->invoice();
        $student = $invoice->student;
        $payment = app(PaymentService::class)->record($student, 100000, 'cash', null, '2026-08-01', [$invoice->id => 100000]);
        app(RefundService::class)->refund($payment, 10000, 'Adjustment', '2026-08-02');
        app(DiscountService::class)->applyToInvoice($invoice->fresh(), 'fixed', 5000, 'Goodwill');

        $this->assertTrue(app(LedgerService::class)->isBalanced($this->institute->id));
    }
}
