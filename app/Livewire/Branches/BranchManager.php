<?php

namespace App\Livewire\Branches;

use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BranchManager extends Component
{
    public ?int $editingId = null;
    public string $name = '';
    public string $code = '';
    public string $city = '';

    public function mount(): void
    {
        // Institute and branch management is gated to Institute Admin.
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);
    }

    public function edit(int $id): void
    {
        $branch = Branch::findOrFail($id);
        $this->editingId = $branch->id;
        $this->name = $branch->name;
        $this->code = $branch->code;
        $this->city = (string) $branch->city;
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'code', 'city']);
        $this->resetValidation();
    }

    public function save(): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
        ]);

        $data['institute_id'] = current_institute()?->id;

        if ($this->editingId) {
            Branch::findOrFail($this->editingId)->update($data);
        } else {
            Branch::create($data);
        }

        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);
        $branch = Branch::findOrFail($id);
        $branch->update(['is_active' => ! $branch->is_active]);
    }

    public function render()
    {
        return view('livewire.branches.branch-manager', [
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }
}
