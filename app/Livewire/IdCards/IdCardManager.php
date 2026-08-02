<?php

namespace App\Livewire\IdCards;

use App\Models\Batch;
use App\Models\Branch;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class IdCardManager extends Component
{
    public string $branchId = '';

    public string $batchId = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('admission.view'), 403);
    }

    public function updatedBranchId(): void
    {
        $this->batchId = '';
    }

    public function render()
    {
        $count = Student::query()->where('is_active', true)
            ->when($this->branchId !== '', fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->batchId !== '', function ($q) {
                $ids = Batch::find($this->batchId)?->enrollments()->pluck('student_id') ?? collect();
                $q->whereIn('id', $ids);
            })
            ->count();

        return view('livewire.id-cards.id-card-manager', [
            'branches' => Auth::user()->hasAllBranchAccess()
                ? Branch::orderBy('name')->get()
                : Branch::whereIn('id', Auth::user()->branchIds() ?: [0])->orderBy('name')->get(),
            'batches' => Batch::query()
                ->when($this->branchId !== '', fn ($q) => $q->where('branch_id', $this->branchId))
                ->orderByDesc('id')->get(),
            'count' => $count,
        ]);
    }

    public function sheetUrl(): string
    {
        return route('id-cards.sheet', array_filter([
            'branch' => $this->branchId ?: null,
            'batch' => $this->batchId ?: null,
        ]));
    }
}
