<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Enquiry;
use App\Models\Institute;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\Reports\ReportService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private Branch $a;

    private Branch $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->institute = Institute::create(['name' => 'Acme']);
        $this->a = Branch::create(['institute_id' => $this->institute->id, 'name' => 'A', 'code' => 'A']);
        $this->b = Branch::create(['institute_id' => $this->institute->id, 'name' => 'B', 'code' => 'B']);
    }

    private function reports(): ReportService
    {
        return app(ReportService::class);
    }

    private function invoice(Branch $branch, int $total, int $balance, string $status = 'issued'): Invoice
    {
        $s = Student::create(['institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'name' => 'S']);

        return Invoice::create([
            'institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'student_id' => $s->id,
            'invoice_number' => 'INV'.uniqid(), 'invoice_date' => now()->toDateString(),
            'total' => $total, 'balance' => $balance, 'status' => $status,
        ]);
    }

    private function payment(Branch $branch, int $amount, string $mode): Payment
    {
        $s = Student::create(['institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'name' => 'P']);

        return Payment::create([
            'institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'student_id' => $s->id,
            'payment_date' => now()->toDateString(), 'mode' => $mode, 'amount' => $amount, 'status' => 'completed',
        ]);
    }

    public function test_collections_by_mode_and_kpis_are_accurate(): void
    {
        $this->invoice($this->a, 118000, 68000); // 50000 collected against it
        $this->payment($this->a, 30000, 'cash');
        $this->payment($this->a, 20000, 'upi');
        Enquiry::create(['institute_id' => $this->institute->id, 'branch_id' => $this->a->id, 'name' => 'L1', 'status' => 'converted']);
        Enquiry::create(['institute_id' => $this->institute->id, 'branch_id' => $this->a->id, 'name' => 'L2', 'status' => 'new']);

        $this->assertSame(['cash' => 30000, 'upi' => 20000], $this->reports()->collectionsByMode());

        $kpis = $this->reports()->kpis();
        $this->assertSame(50000, $kpis['collected_month']);
        $this->assertSame(68000, $kpis['outstanding']);
        $this->assertSame(5000, $kpis['conversion_bp']); // 1 of 2 converted = 50%
    }

    public function test_reports_respect_branch_scope(): void
    {
        $this->invoice($this->a, 100000, 100000);
        $this->invoice($this->b, 200000, 200000);

        // A branch admin scoped to branch A sees only branch A's outstanding.
        $user = User::factory()->create();
        Staff::create(['user_id' => $user->id, 'institute_id' => $this->institute->id, 'branch_id' => $this->a->id, 'name' => 'Admin A']);
        $user->assignRole('Branch Admin');
        $this->actingAs($user);

        $this->assertSame(100000, $this->reports()->kpis()['outstanding']);
    }
}
