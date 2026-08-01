<?php

namespace App\Livewire\Portal;

use App\Models\AttendanceRecord;
use App\Services\Attendance\AttendanceService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.portal')]
class PortalAttendance extends Component
{
    use WithCurrentStudent;

    public function mount(): void
    {
        $this->initPortal();
    }

    public function render(AttendanceService $attendance)
    {
        $student = $this->currentStudent();

        return view('livewire.portal.portal-attendance', [
            'student' => $student,
            'students' => $this->accessibleStudents(),
            'summary' => $student ? $attendance->studentSummary($student->id) : ['present' => 0, 'total' => 0, 'percent_bp' => 0],
            'history' => $student
                ? AttendanceRecord::query()
                    ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendance_records.attendance_session_id')
                    ->where('attendance_records.student_id', $student->id)
                    ->orderByDesc('attendance_sessions.session_date')
                    ->limit(30)
                    ->get(['attendance_records.status', 'attendance_sessions.session_date'])
                : collect(),
        ]);
    }
}
