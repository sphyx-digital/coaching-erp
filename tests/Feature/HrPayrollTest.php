<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\SalaryStructure;
use App\Models\Staff;
use App\Models\User;
use App\Services\Hr\PayrollService;
use App\Services\Hr\StaffAttendanceService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrPayrollTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private StaffAttendanceService $attendance;

    private PayrollService $payroll;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institute = Institute::create(['name' => 'Acme']);
        $this->attendance = app(StaffAttendanceService::class);
        $this->payroll = app(PayrollService::class);
    }

    private function staff(): Staff
    {
        $user = User::factory()->create();

        return Staff::create(['user_id' => $user->id, 'institute_id' => $this->institute->id, 'name' => 'Teacher A']);
    }

    public function test_attendance_summary_counts_unpaid_days_with_paid_leave_allowance(): void
    {
        $staff = $this->staff();
        // April 2026 (30 days).
        $this->attendance->mark($staff, '2026-04-01', 'absent');
        $this->attendance->mark($staff, '2026-04-02', 'absent');
        $this->attendance->mark($staff, '2026-04-03', 'half_day');
        $this->attendance->mark($staff, '2026-04-04', 'leave');
        $this->attendance->mark($staff, '2026-04-05', 'leave');
        $this->attendance->mark($staff, '2026-04-06', 'leave'); // 3 leaves, 2 allowed => 1 excess
        $this->attendance->mark($staff, '2026-04-07', 'present');

        $s = $this->attendance->summary($staff, '2026-04-01');

        $this->assertSame(30, $s['days_in_month']);
        $this->assertSame(2, $s['absent']);
        $this->assertSame(1, $s['half_day']);
        $this->assertSame(3, $s['leave']);
        // unpaid = 2 absent + 1 excess leave + 0.5 half = 3.5
        $this->assertSame(3.5, $s['unpaid_days']);
    }

    public function test_marking_is_idempotent_per_day(): void
    {
        $staff = $this->staff();
        $this->attendance->mark($staff, '2026-04-01', 'absent');
        $this->attendance->mark($staff, '2026-04-01', 'present'); // correction

        $s = $this->attendance->summary($staff, '2026-04-01');
        $this->assertSame(0, $s['absent']);
        $this->assertSame(1, $s['present']);
    }

    public function test_payroll_prorates_loss_of_pay_and_subtracts_deductions(): void
    {
        $staff = $this->staff();
        SalaryStructure::create([
            'institute_id' => $this->institute->id, 'staff_id' => $staff->id, 'effective_from' => '2026-04-01',
            'monthly_gross' => 3000000, // ₹30,000
            'earnings' => [['name' => 'Basic', 'amount' => 3000000]],
            'deductions' => [['name' => 'PF', 'amount' => 180000]], // ₹1,800
            'is_active' => true,
        ]);

        // 3.5 unpaid days in a 30-day month.
        $this->attendance->mark($staff, '2026-04-01', 'absent');
        $this->attendance->mark($staff, '2026-04-02', 'absent');
        $this->attendance->mark($staff, '2026-04-03', 'half_day');
        foreach (['04', '05', '06'] as $d) {
            $this->attendance->mark($staff, "2026-04-$d", 'leave'); // 1 excess
        }

        $payslip = $this->payroll->generate($staff, '2026-04-01');

        $this->assertSame(3000000, $payslip->gross);
        $this->assertSame(350000, $payslip->lop_amount);       // round(3000000 * 3.5 / 30)
        $this->assertSame(180000, $payslip->fixed_deductions);
        $this->assertSame(2470000, $payslip->net);             // 3000000 - 350000 - 180000
        $this->assertSame('draft', $payslip->status);
    }

    public function test_payroll_requires_a_salary_structure(): void
    {
        $this->expectException(DomainException::class);
        $this->payroll->generate($this->staff(), '2026-04-01');
    }

    public function test_finalize_then_pay_lifecycle_and_guards(): void
    {
        $staff = $this->staff();
        SalaryStructure::create([
            'institute_id' => $this->institute->id, 'staff_id' => $staff->id, 'effective_from' => '2026-04-01',
            'monthly_gross' => 3000000, 'earnings' => [['name' => 'Basic', 'amount' => 3000000]], 'is_active' => true,
        ]);

        $payslip = $this->payroll->generate($staff, '2026-04-01');
        $this->payroll->finalize($payslip);
        $this->assertSame('finalized', $payslip->fresh()->status);

        // Cannot regenerate a finalized payslip.
        try {
            $this->payroll->generate($staff, '2026-04-01');
            $this->fail('Expected regeneration to be refused.');
        } catch (DomainException) {
            $this->assertTrue(true);
        }

        $this->payroll->markPaid($payslip->fresh());
        $this->assertSame('paid', $payslip->fresh()->status);
    }
}
