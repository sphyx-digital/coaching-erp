<?php

namespace App\Livewire\Hr;

use App\Models\Payslip;
use App\Models\SalaryStructure;
use App\Models\Staff;
use App\Services\Hr\PayrollService;
use App\Services\Hr\StaffAttendanceService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PayrollManager extends Component
{
    public string $month; // Y-m-01

    public bool $showStructure = false;

    public ?int $structureStaffId = null;

    public string $grossRupees = '';

    /** @var array<int,array{name:string,amount:string}> */
    public array $deductionRows = [];

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);
        $this->month = CarbonImmutable::now()->startOfMonth()->toDateString();
    }

    public function updatedMonth(): void
    {
        $this->month = CarbonImmutable::parse($this->month)->startOfMonth()->toDateString();
    }

    // ---- Salary structure ----------------------------------------------
    public function editStructure(int $staffId): void
    {
        $this->structureStaffId = $staffId;
        $structure = app(PayrollService::class)->activeStructure(Staff::findOrFail($staffId), $this->month);
        $this->grossRupees = $structure ? number_format($structure->monthly_gross / 100, 2, '.', '') : '';
        $this->deductionRows = collect($structure->deductions ?? [])
            ->map(fn ($d) => ['name' => $d['name'], 'amount' => number_format(($d['amount'] ?? 0) / 100, 2, '.', '')])
            ->values()->all();
        $this->showStructure = true;
    }

    public function addDeduction(): void
    {
        $this->deductionRows[] = ['name' => '', 'amount' => ''];
    }

    public function removeDeduction(int $i): void
    {
        unset($this->deductionRows[$i]);
        $this->deductionRows = array_values($this->deductionRows);
    }

    public function saveStructure(): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);
        $this->validate(['grossRupees' => ['required', 'numeric', 'min:0']]);

        $staff = Staff::findOrFail($this->structureStaffId);
        $grossPaise = (int) round(((float) $this->grossRupees) * 100);

        $deductions = [];
        foreach ($this->deductionRows as $row) {
            $name = trim($row['name'] ?? '');
            $amt = (float) ($row['amount'] ?? 0);
            if ($name !== '' && $amt > 0) {
                $deductions[] = ['name' => $name, 'amount' => (int) round($amt * 100)];
            }
        }

        // Supersede the current active structure, then create the new one.
        SalaryStructure::where('staff_id', $staff->id)->where('is_active', true)->update(['is_active' => false]);
        SalaryStructure::create([
            'institute_id' => $staff->institute_id,
            'staff_id' => $staff->id,
            'effective_from' => CarbonImmutable::parse($this->month)->startOfMonth()->toDateString(),
            'monthly_gross' => $grossPaise,
            'earnings' => [['name' => 'Basic', 'amount' => $grossPaise]],
            'deductions' => $deductions ?: null,
            'is_active' => true,
        ]);

        $this->showStructure = false;
        session()->flash('payroll_saved', true);
    }

    // ---- Payslips -------------------------------------------------------
    public function generate(int $staffId): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);
        try {
            app(PayrollService::class)->generate(Staff::findOrFail($staffId), $this->month);
        } catch (DomainException $e) {
            $this->addError('payroll', $e->getMessage());
        }
    }

    public function finalize(int $payslipId): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);
        app(PayrollService::class)->finalize(Payslip::findOrFail($payslipId));
    }

    public function markPaid(int $payslipId): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);
        try {
            app(PayrollService::class)->markPaid(Payslip::findOrFail($payslipId));
        } catch (DomainException $e) {
            $this->addError('payroll', $e->getMessage());
        }
    }

    public function render()
    {
        $staff = Staff::where('is_active', true)->orderBy('name')->get();
        $svc = app(PayrollService::class);
        $attendance = app(StaffAttendanceService::class);

        $rows = $staff->map(function ($s) use ($svc, $attendance) {
            return [
                'staff' => $s,
                'structure' => $svc->activeStructure($s, $this->month),
                'payslip' => Payslip::where('staff_id', $s->id)->whereDate('month', $this->month)->first(),
                'summary' => $attendance->summary($s, $this->month),
            ];
        });

        return view('livewire.hr.payroll-manager', [
            'rows' => $rows,
            'monthLabel' => CarbonImmutable::parse($this->month)->format('F Y'),
        ]);
    }
}
