<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Institute;
use App\Models\Invoice;
use App\Models\Student;
use App\Services\Exceptions\ExceptionService;
use App\Services\Fees\FeeService;
use App\Services\Fees\LedgerService;
use App\Services\Fees\PaymentService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExceptionServiceTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private Student $student;

    private Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institute = Institute::create(['name' => 'Acme', 'state_code' => '27']);
        $branch = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Main', 'code' => 'MN']);
        $session = AcademicSession::create(['institute_id' => $this->institute->id, 'name' => '2026-27', 'is_active' => true]);
        $course = Course::create(['institute_id' => $this->institute->id, 'name' => 'JEE', 'code' => 'JEE']);
        $this->student = Student::create(['institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'name' => 'Riya']);
        $this->enrollment = Enrollment::create([
            'institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'student_id' => $this->student->id,
            'course_id' => $course->id, 'academic_session_id' => $session->id, 'status' => 'active',
        ]);
    }

    private function invoice(): Invoice
    {
        return app(FeeService::class)->createInvoice($this->student, $this->enrollment,
            [['description' => 'Tuition', 'taxable_value' => 100000, 'rate_bp' => 1800]], false);
    }

    public function test_reversing_a_payment_restores_balance_and_keeps_the_ledger_balanced(): void
    {
        $invoice = $this->invoice();
        $payment = app(PaymentService::class)->record($this->student, 118000, 'cash', null, '2026-08-01', [$invoice->id => 118000]);

        app(ExceptionService::class)->reversePayment($payment);

        $this->assertSame('reversed', $payment->fresh()->status);
        $this->assertSame(118000, $invoice->fresh()->balance);   // restored
        $this->assertSame(0, $invoice->fresh()->amount_paid);
        $this->assertTrue(app(LedgerService::class)->isBalanced($this->institute->id)); // compensating, still balanced
        $this->assertDatabaseHas('payments', ['id' => $payment->id]); // original kept
    }

    public function test_a_payment_cannot_be_voided_twice(): void
    {
        $invoice = $this->invoice();
        $payment = app(PaymentService::class)->record($this->student, 118000, 'cash', null, '2026-08-01', [$invoice->id => 118000]);
        app(ExceptionService::class)->reversePayment($payment);

        $this->expectException(DomainException::class);
        app(ExceptionService::class)->reversePayment($payment->fresh());
    }

    public function test_cancelling_an_unpaid_invoice_keeps_the_ledger_balanced(): void
    {
        $invoice = $this->invoice();

        app(ExceptionService::class)->cancelInvoice($invoice, 'Duplicate');

        $this->assertSame('cancelled', $invoice->fresh()->status);
        $this->assertSame(0, $invoice->fresh()->balance);
        $this->assertTrue(app(LedgerService::class)->isBalanced($this->institute->id));
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]); // kept for audit
    }

    public function test_a_paid_invoice_cannot_be_cancelled(): void
    {
        $invoice = $this->invoice();
        app(PaymentService::class)->record($this->student, 50000, 'cash', null, '2026-08-01', [$invoice->id => 50000]);

        $this->expectException(DomainException::class);
        app(ExceptionService::class)->cancelInvoice($invoice->fresh());
    }

    public function test_backdated_entries_outside_the_window_are_blocked(): void
    {
        $service = app(ExceptionService::class);

        $service->assertBackdateAllowed(now()->subDays(3)->toDateString()); // within window, no throw
        $this->addToAssertionCount(1);

        $this->expectException(DomainException::class);
        $service->assertBackdateAllowed(now()->subDays(60)->toDateString());
    }

    public function test_exceptions_appear_in_the_audit_override_log(): void
    {
        $invoice = $this->invoice();
        $payment = app(PaymentService::class)->record($this->student, 118000, 'cash', null, '2026-08-01', [$invoice->id => 118000]);
        app(ExceptionService::class)->reversePayment($payment);

        $another = $this->invoice();
        app(ExceptionService::class)->cancelInvoice($another, 'Error');

        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.reversed']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'invoice.cancelled']);
    }
}
