<?php

namespace App\Livewire\Portal;

use App\Models\Enrollment;
use App\Models\StudyMaterial;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.portal')]
class PortalMaterials extends Component
{
    use WithCurrentStudent;

    public function mount(): void
    {
        $this->initPortal();
    }

    public function render()
    {
        $student = $this->currentStudent();

        $courseIds = $student ? Enrollment::where('student_id', $student->id)->pluck('course_id')->filter()->all() : [];
        $batchIds = $student ? Enrollment::where('student_id', $student->id)->pluck('batch_id')->filter()->all() : [];

        // Published materials that are institute-wide OR match the student's course/batch.
        $materials = $student
            ? StudyMaterial::published()->with(['course', 'subject'])
                ->where(function ($q) use ($courseIds, $batchIds) {
                    $q->where(fn ($w) => $w->whereNull('course_id')->whereNull('batch_id'))
                        ->orWhereIn('course_id', $courseIds ?: [0])
                        ->orWhereIn('batch_id', $batchIds ?: [0]);
                })
                ->latest()->get()
            : collect();

        return view('livewire.portal.portal-materials', [
            'student' => $student,
            'students' => $this->accessibleStudents(),
            'materials' => $materials,
        ]);
    }
}
