<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\ConsentRecord;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Institute;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Services\Fees\FeeService;
use App\Services\Notifications\NotificationService;
use App\Services\Payments\OnlinePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationsTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private Student $student;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institute = Institute::create(['name' => 'Acme', 'state_code' => '27']);
        $branch = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Main', 'code' => 'MN']);
        $session = AcademicSession::create(['institute_id' => $this->institute->id, 'name' => '2026-27', 'is_active' => true]);
        $course = Course::create(['institute_id' => $this->institute->id, 'name' => 'JEE', 'code' => 'JEE']);
        $this->student = Student::create(['institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'name' => 'Riya']);
        $enrollment = Enrollment::create(['institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'student_id' => $this->student->id, 'course_id' => $course->id, 'academic_session_id' => $session->id, 'status' => 'active']);
        $this->invoice = app(FeeService::class)->createInvoice($this->student, $enrollment,
            [['description' => 'Tuition', 'taxable_value' => 100000, 'rate_bp' => 1800]], false); // total 118000
    }

    private function online(): OnlinePaymentService
    {
        return app(OnlinePaymentService::class);
    }

    private function payload(string $status, string $paymentId = 'pay_ABC'): string
    {
        return json_encode(['status' => $status, 'payment_id' => $paymentId, 'order_id' => 'order_1', 'invoice_id' => $this->invoice->id, 'amount' => 118000]);
    }

    public function test_captured_webhook_posts_a_receipt(): void
    {
        $result = $this->online()->handleWebhook($this->payload('captured'), 'valid');

        $this->assertSame('posted', $result['status']);
        $this->assertNotEmpty($result['receipt']);
        $this->assertSame(0, $this->invoice->fresh()->balance);
        $this->assertDatabaseHas('payments', ['gateway_payment_id' => 'pay_ABC', 'mode' => 'online']);
    }

    public function test_repeated_webhook_does_not_double_post(): void
    {
        $this->online()->handleWebhook($this->payload('captured'), 'valid');
        $result = $this->online()->handleWebhook($this->payload('captured'), 'valid'); // same payment_id

        $this->assertSame('duplicate', $result['status']);
        $this->assertSame(1, Payment::where('gateway_payment_id', 'pay_ABC')->count());
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $result = $this->online()->handleWebhook($this->payload('captured'), 'wrong-signature');

        $this->assertSame('invalid_signature', $result['status']);
        $this->assertSame(118000, $this->invoice->fresh()->balance);
    }

    public function test_failed_payment_leaves_the_invoice_unpaid(): void
    {
        $result = $this->online()->handleWebhook($this->payload('failed'), 'valid');

        $this->assertSame('unpaid', $result['status']);
        $this->assertSame(118000, $this->invoice->fresh()->balance);
    }

    public function test_messaging_is_gated_by_consent(): void
    {
        config()->set('client.features.whatsapp', true);
        $svc = app(NotificationService::class);

        // No consent → skipped.
        $skipped = $svc->dispatch('whatsapp', ['student_id' => $this->student->id, 'recipient' => '+919999999999', 'body' => 'Hi']);
        $this->assertSame('skipped', $skipped->status);

        // With communication consent → delivered (stub queues it).
        ConsentRecord::create(['institute_id' => $this->institute->id, 'student_id' => $this->student->id, 'consent_type' => 'communication', 'granted' => true]);
        $sent = $svc->dispatch('whatsapp', ['student_id' => $this->student->id, 'recipient' => '+919999999999', 'body' => 'Hi']);
        $this->assertSame('queued', $sent->status);
    }

    public function test_a_failed_message_is_dead_lettered_and_can_be_retried(): void
    {
        config()->set('client.features.email', true);
        $svc = app(NotificationService::class);

        $failed = $svc->dispatch('email', ['recipient' => null, 'subject' => 'Hi', 'body' => 'x']); // no recipient
        $this->assertSame('failed', $failed->status);

        $failed->update(['recipient' => 'parent@example.com']);
        $retried = $svc->retry($failed->fresh());
        $this->assertSame('sent', $retried->status);
    }
}
