<?php

namespace App\Livewire\Portal;

use App\Models\ReportCard;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.portal')]
class PortalResults extends Component
{
    use WithCurrentStudent;

    public function mount(): void
    {
        $this->initPortal();
    }

    public function render()
    {
        $student = $this->currentStudent();

        return view('livewire.portal.portal-results', [
            'student' => $student,
            'students' => $this->accessibleStudents(),
            'cards' => $student
                ? ReportCard::with('assessment')->where('student_id', $student->id)->where('status', 'published')->latest('published_at')->get()
                : collect(),
        ]);
    }
}
