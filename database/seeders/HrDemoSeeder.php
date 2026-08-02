<?php

namespace Database\Seeders;

use App\Models\SalaryStructure;
use App\Models\Staff;
use App\Services\Hr\PayrollService;
use App\Services\Hr\StaffAttendanceService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Demo HR data: a salary structure per staff member, last month's attendance,
 * and generated draft payslips. Idempotent: skips if any salary structure
 * already exists.
 */
class HrDemoSeeder extends Seeder
{
    public function run(): void
    {
        $institute = current_institute();
        if (! $institute || SalaryStructure::count() > 0) {
            return;
        }

        $attendance = app(StaffAttendanceService::class);
        $payroll = app(PayrollService::class);
        $month = CarbonImmutable::now()->subMonthNoOverflow()->startOfMonth();

        $staff = Staff::where('institute_id', $institute->id)->where('is_active', true)->orderBy('id')->get();

        foreach ($staff->values() as $i => $member) {
            $gross = 2500000 + $i * 500000; // ₹25,000, ₹30,000, ...
            SalaryStructure::create([
                'institute_id' => $institute->id,
                'staff_id' => $member->id,
                'effective_from' => $month->toDateString(),
                'monthly_gross' => $gross,
                'earnings' => [
                    ['name' => 'Basic', 'amount' => (int) round($gross * 0.6)],
                    ['name' => 'HRA', 'amount' => (int) round($gross * 0.3)],
                    ['name' => 'Special allowance', 'amount' => $gross - (int) round($gross * 0.6) - (int) round($gross * 0.3)],
                ],
                'deductions' => [['name' => 'Professional tax', 'amount' => 20000]], // ₹200
                'is_active' => true,
            ]);

            // Mark the month: mostly present, Sundays off, a couple of variances.
            for ($d = 1; $d <= $month->daysInMonth; $d++) {
                $day = $month->addDays($d - 1);
                $status = $day->isSunday() ? 'week_off' : 'present';
                if ($i === 0 && in_array($d, [7, 8], true)) {
                    $status = 'absent';
                }
                if ($i === 1 && $d === 12) {
                    $status = 'half_day';
                }
                $attendance->mark($member, $day->toDateString(), $status);
            }

            $payroll->generate($member, $month->toDateString());
        }
    }
}
