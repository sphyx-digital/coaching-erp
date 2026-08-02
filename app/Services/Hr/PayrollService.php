<?php

namespace App\Services\Hr;

use App\Models\Payslip;
use App\Models\SalaryStructure;
use App\Models\Staff;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Payroll: turns a staff member's salary structure + monthly attendance into a
 * payslip. Loss-of-pay is prorated per day (gross / days-in-month) against
 * unpaid days; fixed deductions are subtracted. All money in integer paise.
 */
class PayrollService
{
    public function __construct(
        private StaffAttendanceService $attendance,
        private AuditLogger $audit,
    ) {}

    /** The salary structure in force for a given pay month. */
    public function activeStructure(Staff $staff, string $month): ?SalaryStructure
    {
        $end = CarbonImmutable::parse($month)->endOfMonth()->toDateString();

        return SalaryStructure::where('staff_id', $staff->id)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $end)
            ->orderByDesc('effective_from')
            ->first();
    }

    /**
     * Generate (or regenerate a draft) payslip for a staff member and month.
     */
    public function generate(Staff $staff, string $month): Payslip
    {
        $month = CarbonImmutable::parse($month)->startOfMonth()->toDateString();

        $structure = $this->activeStructure($staff, $month);
        if (! $structure) {
            throw new DomainException('No active salary structure for this staff member.');
        }

        $existing = Payslip::where('staff_id', $staff->id)->whereDate('month', $month)->first();
        if ($existing && $existing->isFinalized()) {
            throw new DomainException('A finalized payslip already exists for this month.');
        }

        $summary = $this->attendance->summary($staff, $month);
        $gross = (int) $structure->monthly_gross;
        $fixedDeductions = $structure->totalDeductions();
        $unpaidDays = (float) $summary['unpaid_days'];
        $daysInMonth = (int) $summary['days_in_month'];

        // Prorate loss of pay to the paisa.
        $lop = (int) round($gross * $unpaidDays / max(1, $daysInMonth));
        $net = max(0, $gross - $lop - $fixedDeductions);

        return DB::transaction(function () use ($staff, $month, $structure, $gross, $lop, $fixedDeductions, $net, $unpaidDays, $daysInMonth, $existing) {
            $payslip = $existing ?: new Payslip(['staff_id' => $staff->id, 'month' => $month]);
            $payslip->fill([
                'institute_id' => $staff->institute_id,
                'salary_structure_id' => $structure->id,
                'days_in_month' => $daysInMonth,
                'unpaid_days' => $unpaidDays,
                'gross' => $gross,
                'lop_amount' => $lop,
                'fixed_deductions' => $fixedDeductions,
                'net' => $net,
                'earnings' => $structure->earnings,
                'deductions' => $structure->deductions,
                'status' => 'draft',
                'generated_at' => now(),
            ])->save();

            $this->audit->log($existing ? 'payslip.regenerated' : 'payslip.generated', $payslip, after: [
                'staff' => $staff->name, 'month' => $month, 'net' => $net,
            ]);

            return $payslip;
        });
    }

    public function finalize(Payslip $payslip): Payslip
    {
        if ($payslip->status === 'paid') {
            throw new DomainException('This payslip is already paid.');
        }
        $payslip->forceFill(['status' => 'finalized'])->save();
        $this->audit->log('payslip.finalized', $payslip);

        return $payslip;
    }

    public function markPaid(Payslip $payslip): Payslip
    {
        if (! $payslip->isFinalized()) {
            throw new DomainException('Finalize the payslip before marking it paid.');
        }
        $payslip->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
        $this->audit->log('payslip.paid', $payslip, after: ['net' => $payslip->net]);

        return $payslip;
    }
}
