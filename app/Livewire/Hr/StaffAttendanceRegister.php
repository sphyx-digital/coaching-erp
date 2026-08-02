<?php

namespace App\Livewire\Hr;

use App\Models\Staff;
use App\Services\Hr\StaffAttendanceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class StaffAttendanceRegister extends Component
{
    public string $date;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);
        $this->date = Carbon::now()->toDateString();
    }

    public function mark(int $staffId, string $status): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);
        $staff = Staff::findOrFail($staffId);
        app(StaffAttendanceService::class)->mark($staff, $this->date, $status);
    }

    public function render()
    {
        $staff = Staff::where('is_active', true)->orderBy('name')->get();
        $today = app(StaffAttendanceService::class)->matrix($this->date);
        $day = (int) Carbon::parse($this->date)->day;

        $marks = [];
        foreach ($staff as $s) {
            $marks[$s->id] = $today[$s->id][$day] ?? null;
        }

        return view('livewire.hr.staff-attendance-register', [
            'staff' => $staff,
            'marks' => $marks,
            'statuses' => StaffAttendanceService::STATUSES,
        ]);
    }
}
