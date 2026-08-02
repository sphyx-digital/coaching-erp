<?php

namespace App\Livewire\Approvals;

use App\Models\Approval;
use App\Services\Approvals\ApprovalService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ApprovalInbox extends Component
{
    public ?int $rejectId = null;

    public string $reason = '';

    public bool $viewing = false;

    public ?int $viewingId = null;

    public function mount(): void
    {
        abort_if(Auth::user()?->isPortalUser(), 403);
    }

    public function view(int $id): void
    {
        $this->viewingId = $id;
        $this->viewing = true;
        $this->reset(['rejectId', 'reason']);
    }

    public function updatedViewing(bool $value): void
    {
        if (! $value) {
            $this->reset(['viewingId', 'rejectId', 'reason']);
        }
    }

    public function approve(int $id, ApprovalService $service): void
    {
        $this->decide($id, true, $service);
    }

    public function confirmReject(ApprovalService $service): void
    {
        if ($this->rejectId) {
            $this->decide($this->rejectId, false, $service, $this->reason ?: 'Rejected');
            $this->reset(['rejectId', 'reason']);
        }
    }

    private function decide(int $id, bool $approve, ApprovalService $service, ?string $reason = null): void
    {
        try {
            $service->decide(Approval::findOrFail($id), Auth::user(), $approve, $reason);
            session()->flash('ok', $approve ? 'Approved.' : 'Rejected.');
        } catch (\DomainException $e) {
            $this->addError('decide', $e->getMessage());
        }
    }

    public function render()
    {
        $user = Auth::user();
        $roles = $user->getRoleNames()->all();

        $q = Approval::with('requester')->where('status', 'pending');
        if (! $user->hasAllBranchAccess()) {
            $q->whereIn('approver_role', $roles);
        }

        return view('livewire.approvals.approval-inbox', [
            'pending' => $q->latest()->get(),
            'record' => $this->viewingId ? Approval::with('requester')->find($this->viewingId) : null,
        ]);
    }
}
