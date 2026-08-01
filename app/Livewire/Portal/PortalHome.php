<?php

namespace App\Livewire\Portal;

use App\Models\Invoice;
use App\Models\Notification;
use App\Models\ReportCard;
use App\Services\Attendance\AttendanceService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.portal')]
class PortalHome extends Component
{
    use WithCurrentStudent;

    public function mount(): void
    {
        $this->initPortal();
    }

    public function render(AttendanceService $attendance)
    {
        $student = $this->currentStudent();

        return view('livewire.portal.portal-home', [
            'student' => $student,
            'students' => $this->accessibleStudents(),
            'feeDue' => $student
                ? (int) Invoice::withoutGlobalScopes()->where('student_id', $student->id)
                    ->whereNotIn('status', ['paid', 'cancelled'])->sum('balance')
                : 0,
            'attendanceBp' => $student ? $attendance->studentSummary($student->id)['percent_bp'] : 0,
            'latestResult' => $student
                ? ReportCard::where('student_id', $student->id)->where('status', 'published')->latest('published_at')->first()
                : null,
            'notices' => Notification::where('user_id', auth()->id())->latest()->limit(5)->get(),
            'canPay' => feature('online_payments'),
        ]);
    }
}
