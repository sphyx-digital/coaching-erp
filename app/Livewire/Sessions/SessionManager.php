<?php

namespace App\Livewire\Sessions;

use App\Models\AcademicSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SessionManager extends Component
{
    public string $name = '';

    public ?string $starts_on = null;

    public ?string $ends_on = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);
    }

    public function save(): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ]);

        $data['institute_id'] = current_institute()?->id;

        AcademicSession::create($data);
        $this->reset(['name', 'starts_on', 'ends_on']);
    }

    public function markActive(int $id): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);

        DB::transaction(function () use ($id) {
            $session = AcademicSession::findOrFail($id);
            AcademicSession::where('institute_id', $session->institute_id)->update(['is_active' => false]);
            $session->update(['is_active' => true]);
        });
    }

    public function render()
    {
        return view('livewire.sessions.session-manager', [
            'sessions' => AcademicSession::orderByDesc('is_active')->orderBy('name')->get(),
        ]);
    }
}
