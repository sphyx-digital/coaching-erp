<?php

namespace App\Livewire\Enquiries;

use App\Enums\EnquiryStatus;
use App\Livewire\Concerns\WithTableTools;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Enquiry;
use App\Services\Enquiries\EnquiryService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class EnquiryManager extends Component
{
    use WithTableTools;

    public string $statusFilter = '';

    // Create form
    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $guardian_name = '';

    public string $guardian_phone = '';

    public string $source = '';

    public ?int $branch_id = null;

    public ?int $course_id = null;

    // Detail drawer
    public bool $viewing = false;

    public ?int $viewingId = null;

    // Action panel (follow-up note or lost reason), shown inside the drawer
    public string $panelMode = '';   // '' | note | lost

    public string $panelText = '';

    public ?string $panelFollowUp = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('enquiry.view'), 403);
    }

    public function view(int $id): void
    {
        $this->viewingId = $id;
        $this->viewing = true;
        $this->reset(['panelMode', 'panelText', 'panelFollowUp']);
    }

    public function updatedViewing(bool $value): void
    {
        if (! $value) {
            $this->reset(['viewingId', 'panelMode', 'panelText', 'panelFollowUp']);
        }
    }

    public function getIsDuplicateProperty(): bool
    {
        return app(EnquiryService::class)->isDuplicate(
            (int) current_institute()?->id,
            $this->phone ?: null,
            $this->course_id,
            active_session()?->id,
        );
    }

    public function create(EnquiryService $service): void
    {
        abort_unless(Auth::user()?->can('enquiry.create'), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'regex:/^[0-9]{6,15}$/'],
            'email' => ['nullable', 'email'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'regex:/^[0-9]{6,15}$/'],
            'source' => ['nullable', 'string', 'max:255'],
            'branch_id' => ['required', 'exists:branches,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
        ]);

        $service->create($data + [
            'institute_id' => current_institute()?->id,
            'academic_session_id' => active_session()?->id,
            'counsellor_id' => Auth::user()->staff?->id,
        ]);

        $this->reset(['name', 'phone', 'email', 'guardian_name', 'guardian_phone', 'source', 'course_id']);
    }

    public function setStatus(int $id, string $status, EnquiryService $service): void
    {
        abort_unless(Auth::user()?->can('enquiry.update'), 403);
        $service->transition(Enquiry::findOrFail($id), EnquiryStatus::from($status));
    }

    public function convert(int $id, EnquiryService $service): void
    {
        abort_unless(Auth::user()?->can('admission.create'), 403);

        try {
            $service->convert(Enquiry::findOrFail($id));
            session()->flash('ok', 'Converted to a provisional admission.');
        } catch (\DomainException $e) {
            $this->addError('convert', $e->getMessage());
        }
    }

    public function openPanel(string $mode): void
    {
        $this->panelMode = $mode;
        $this->panelText = '';
        $this->panelFollowUp = null;
    }

    public function closePanel(): void
    {
        $this->reset(['panelMode', 'panelText', 'panelFollowUp']);
    }

    public function savePanel(EnquiryService $service): void
    {
        abort_unless(Auth::user()?->can('enquiry.update'), 403);
        $enquiry = Enquiry::findOrFail($this->viewingId);

        if ($this->panelMode === 'lost') {
            $service->transition($enquiry, EnquiryStatus::Lost, $this->panelText ?: null);
        } else {
            $service->logActivity($enquiry, $this->panelText ?: null, $this->panelFollowUp);
        }

        $this->closePanel();
    }

    private function filteredEnquiries()
    {
        $q = Enquiry::with(['course', 'branch']);

        if ($this->search !== '') {
            $s = '%'.$this->search.'%';
            $q->where(fn ($w) => $w->where('name', 'like', $s)->orWhere('phone', 'like', $s)->orWhere('enquiry_number', 'like', $s));
        }
        if ($this->statusFilter !== '') {
            $q->where('status', $this->statusFilter);
        }

        $q = $this->sortField ? $this->applySort($q, 'id') : $q->latest();

        return $q->limit(100)->get();
    }

    public function render()
    {
        $today = now()->toDateString();
        $sessionId = active_session()?->id;

        $record = $this->viewingId
            ? Enquiry::with(['course', 'branch', 'counsellor', 'convertedStudent', 'activities' => fn ($q) => $q->latest()])->find($this->viewingId)
            : null;

        return view('livewire.enquiries.enquiry-manager', [
            'branches' => Branch::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'courses' => Course::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'enquiries' => $this->filteredEnquiries(),
            'record' => $record,
            'dueToday' => Enquiry::dueBy($today)->with('course')->orderBy('next_follow_up_on')->get(),
            'kpiOpen' => Enquiry::open()->count(),
            'kpiDue' => Enquiry::dueBy($today)->count(),
            'kpiConverted' => Enquiry::where('status', EnquiryStatus::Converted->value)
                ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))->count(),
            'statuses' => EnquiryStatus::pipeline(),
        ]);
    }
}
