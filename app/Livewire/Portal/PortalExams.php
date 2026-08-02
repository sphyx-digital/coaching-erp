<?php

namespace App\Livewire\Portal;

use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.portal')]
class PortalExams extends Component
{
    use WithCurrentStudent;

    public function mount(): void
    {
        $this->initPortal();
    }

    public function render()
    {
        $student = $this->currentStudent();

        $courseIds = $student
            ? Enrollment::where('student_id', $student->id)->pluck('course_id')->filter()->all()
            : [];

        // Published exams for the student's courses (or institute-wide).
        $exams = $student
            ? Exam::where('status', 'published')
                ->where(fn ($q) => $q->whereNull('course_id')->orWhereIn('course_id', $courseIds))
                ->withCount('questions')
                ->orderByDesc('id')->get()
            : collect();

        $attempts = $student
            ? ExamAttempt::where('student_id', $student->id)->get()->keyBy('exam_id')
            : collect();

        return view('livewire.portal.portal-exams', [
            'student' => $student,
            'students' => $this->accessibleStudents(),
            'exams' => $exams,
            'attempts' => $attempts,
        ]);
    }
}
