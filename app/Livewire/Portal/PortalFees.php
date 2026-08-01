<?php

namespace App\Livewire\Portal;

use App\Models\Invoice;
use App\Models\Payment;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.portal')]
class PortalFees extends Component
{
    use WithCurrentStudent;

    public function mount(): void
    {
        $this->initPortal();
    }

    public function render()
    {
        $student = $this->currentStudent();

        return view('livewire.portal.portal-fees', [
            'student' => $student,
            'students' => $this->accessibleStudents(),
            'invoices' => $student ? Invoice::withoutGlobalScopes()->where('student_id', $student->id)->latest()->get() : collect(),
            'payments' => $student ? Payment::withoutGlobalScopes()->where('student_id', $student->id)->latest()->get() : collect(),
            'due' => $student ? (int) Invoice::withoutGlobalScopes()->where('student_id', $student->id)->whereNotIn('status', ['paid', 'cancelled'])->sum('balance') : 0,
            'canPay' => feature('online_payments'),
        ]);
    }
}
