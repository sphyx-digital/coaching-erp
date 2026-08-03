<?php

namespace App\Livewire\Setup;

use App\Services\Setup\SetupChecklist;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SetupGuide extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);
    }

    public function render(SetupChecklist $checklist)
    {
        return view('livewire.setup.setup-guide', [
            'steps' => $checklist->steps(Auth::user()),
            'progress' => $checklist->progress(Auth::user()),
        ]);
    }
}
