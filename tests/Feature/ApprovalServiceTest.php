<?php

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\Branch;
use App\Models\Institute;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\User;
use App\Services\Approvals\ApprovalService;
use App\Services\Fees\DiscountService;
use Database\Seeders\RolesAndPermissionsSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->institute = Institute::create(['name' => 'Acme']);
        $branch = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Main', 'code' => 'MN']);
        $student = Student::create(['institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'name' => 'Riya']);
        $this->invoice = Invoice::create([
            'institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'student_id' => $student->id,
            'invoice_number' => 'INV/0001', 'invoice_date' => '2026-08-01',
            'subtotal' => 1000000, 'total' => 1000000, 'balance' => 1000000, 'status' => 'issued',
        ]);
    }

    private function discounts(): DiscountService
    {
        return app(DiscountService::class);
    }

    private function user(string $role): User
    {
        return tap(User::factory()->create())->assignRole($role);
    }

    public function test_a_small_discount_applies_without_approval(): void
    {
        $this->actingAs($this->user('Accountant'));

        $approval = $this->discounts()->propose($this->invoice, 'fixed', 100000, 'Small'); // ₹1,000 < ₹5,000

        $this->assertNull($approval);
        $this->assertSame(900000, $this->invoice->fresh()->balance);
    }

    public function test_a_large_discount_requires_approval_and_is_not_applied_yet(): void
    {
        $this->actingAs($this->user('Accountant'));

        $approval = $this->discounts()->propose($this->invoice, 'fixed', 600000, 'Scholarship'); // ₹6,000

        $this->assertInstanceOf(Approval::class, $approval);
        $this->assertSame('pending', $approval->status);
        $this->assertSame(1000000, $this->invoice->fresh()->balance); // unchanged until approved
    }

    public function test_approving_applies_the_discount(): void
    {
        $this->actingAs($this->user('Accountant'));
        $approval = $this->discounts()->propose($this->invoice, 'fixed', 600000, 'Scholarship');

        app(ApprovalService::class)->decide($approval, $this->user('Institute Admin'), true, 'OK');

        $this->assertSame('approved', $approval->fresh()->status);
        $this->assertSame(400000, $this->invoice->fresh()->balance); // ₹6,000 applied
        $this->assertDatabaseHas('audit_logs', ['action' => 'approval.approved']);
    }

    public function test_self_approval_is_blocked(): void
    {
        $admin = $this->user('Institute Admin');
        $this->actingAs($admin);
        $approval = $this->discounts()->propose($this->invoice, 'fixed', 600000, 'Mine');

        $this->expectException(DomainException::class);
        app(ApprovalService::class)->decide($approval, $admin, true); // same user requested it
    }

    public function test_a_decided_request_cannot_be_decided_again(): void
    {
        $this->actingAs($this->user('Accountant'));
        $approval = $this->discounts()->propose($this->invoice, 'fixed', 600000, 'Scholarship');
        $approver = $this->user('Institute Admin');

        app(ApprovalService::class)->decide($approval, $approver, true);

        $this->expectException(DomainException::class);
        app(ApprovalService::class)->decide($approval->fresh(), $approver, false);
    }

    public function test_overdue_requests_escalate(): void
    {
        $this->actingAs($this->user('Accountant'));
        $approval = $this->discounts()->propose($this->invoice, 'fixed', 600000, 'Scholarship');
        $approval->update(['due_at' => now()->subHour()]); // past SLA

        $count = app(ApprovalService::class)->escalateOverdue();

        $this->assertSame(1, $count);
        $this->assertNotNull($approval->fresh()->escalated_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'approval.escalated']);
    }
}
