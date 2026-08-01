<?php

namespace App\Livewire\Portal;

use App\Models\Enrollment;
use App\Models\TimetableSlot;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.portal')]
class PortalTimetable extends Component
{
    use WithCurrentStudent;

    public const DAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'];

    public function mount(): void
    {
        $this->initPortal();
    }

    public function render()
    {
        $student = $this->currentStudent();
        $batchId = $student
            ? Enrollment::withoutGlobalScopes()->where('student_id', $student->id)
                ->whereIn('status', ['provisional', 'active', 'on_hold'])->whereNotNull('batch_id')->value('batch_id')
            : null;

        return view('livewire.portal.portal-timetable', [
            'student' => $student,
            'students' => $this->accessibleStudents(),
            'days' => self::DAYS,
            'slots' => $batchId
                ? TimetableSlot::with(['subject', 'teacher', 'classroom'])->where('batch_id', $batchId)
                    ->orderBy('start_time')->get()->groupBy('day_of_week')
                : collect(),
        ]);
    }
}
