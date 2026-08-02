<?php

namespace App\Livewire\Notifications;

use App\Models\MessageLog;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class FailedMessages extends Component
{
    public bool $viewing = false;

    public ?int $viewingId = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess() || Auth::user()?->can('settings.view'), 403);
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

    public function retry(int $id, NotificationService $service): void
    {
        $log = MessageLog::findOrFail($id);
        $service->retry($log);
        session()->flash('ok', 'Retried.');
    }

    public function render()
    {
        return view('livewire.notifications.failed-messages', [
            'messages' => MessageLog::whereIn('status', ['failed', 'queued', 'skipped'])->latest()->limit(200)->get(),
            'record' => $this->viewingId ? MessageLog::find($this->viewingId) : null,
        ]);
    }
}
