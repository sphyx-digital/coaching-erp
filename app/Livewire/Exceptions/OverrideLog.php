<?php

namespace App\Livewire\Exceptions;

use App\Livewire\Concerns\WithTableTools;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class OverrideLog extends Component
{
    use WithTableTools;

    /** Actions considered controlled exceptions / overrides. */
    public const ACTIONS = [
        'payment.reversed', 'invoice.cancelled', 'refund.processed',
        'mark.changed', 'attendance.edited', 'discount.applied',
        'approval.approved', 'approval.rejected', 'approval.escalated',
    ];

    public bool $viewing = false;

    public ?int $viewingId = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess() || Auth::user()?->can('fee.view'), 403);
    }

    public function view(int $id): void
    {
        $this->viewingId = $id;
        $this->viewing = true;
    }

    public function updatedViewing(bool $value): void
    {
        if (! $value) {
            $this->viewingId = null;
        }
    }

    public function render()
    {
        $q = AuditLog::with('user')->whereIn('action', self::ACTIONS);
        if ($this->search !== '') {
            $s = '%'.$this->search.'%';
            $q->where(fn ($w) => $w->where('action', 'like', $s)->orWhere('auditable_type', 'like', $s));
        }

        return view('livewire.exceptions.override-log', [
            'entries' => $q->latest()->limit(200)->get(),
            'record' => $this->viewingId ? AuditLog::with('user')->find($this->viewingId) : null,
        ]);
    }
}
