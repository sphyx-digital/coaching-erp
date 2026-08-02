<?php

namespace App\Livewire\Batches;

use App\Enums\EnrollmentStatus;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Staff;
use App\Services\Batches\BatchService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BatchManager extends Component
{
    public string $name = '';

    public string $code = '';

    public int $capacity = 30;

    public ?int $course_id = null;

    public ?int $teacher_id = null;

    public ?int $classroom_id = null;

    // assignment / move
    public array $assignTo = [];   // enrollmentId => batchId

    public ?int $moveId = null;

    public ?int $moveTo = null;

    // detail drawer
    public bool $viewing = false;

    public ?int $viewingId = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('batch.view'), 403);
    }

    public function view(int $id): void
    {
        $this->viewingId = $id;
        $this->viewing = true;
        $this->reset(['moveId', 'moveTo']);
    }

    public function updatedViewing(bool $value): void
    {
        if (! $value) {
            $this->reset(['viewingId', 'moveId', 'moveTo']);
        }
    }

    public function create(): void
    {
        abort_unless(Auth::user()?->can('batch.create'), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30'],
            'capacity' => ['required', 'integer', 'min:0', 'max:1000'],
            'course_id' => ['required', 'exists:courses,id'],
            'teacher_id' => ['nullable', 'exists:staff,id'],
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
        ]);

        Batch::create($data + [
            'institute_id' => current_institute()?->id,
            'branch_id' => Auth::user()->staff?->branch_id ?? current_institute()->branches()->value('id'),
            'academic_session_id' => active_session()?->id,
        ]);

        $this->reset(['name', 'code', 'course_id', 'teacher_id', 'classroom_id']);
        $this->capacity = 30;
    }

    public function assign(int $enrollmentId, BatchService $service): void
    {
        abort_unless(Auth::user()?->can('batch.update'), 403);
        $batchId = $this->assignTo[$enrollmentId] ?? null;
        if (! $batchId) {
            return;
        }

        try {
            $service->assign(Enrollment::findOrFail($enrollmentId), Batch::findOrFail($batchId));
        } catch (\DomainException $e) {
            $this->addError('assign', $e->getMessage());
        }
    }

    public function doMove(BatchService $service): void
    {
        abort_unless(Auth::user()?->can('batch.update'), 403);
        if (! $this->moveId || ! $this->moveTo) {
            return;
        }

        try {
            $service->move(Enrollment::findOrFail($this->moveId), Batch::findOrFail($this->moveTo), 'Moved by admin');
            $this->reset(['moveId', 'moveTo']);
        } catch (\DomainException $e) {
            $this->addError('move', $e->getMessage());
        }
    }

    public function deleteBatch(int $id, BatchService $service): void
    {
        abort_unless(Auth::user()?->can('batch.delete'), 403);
        try {
            $service->delete(Batch::findOrFail($id));
        } catch (\DomainException $e) {
            $this->addError('delete', $e->getMessage());
        }
    }

    public function render()
    {
        $batches = Batch::with(['course', 'teacher'])->withCount(['enrollments as live_count' => fn ($q) => $q->whereIn('status', EnrollmentStatus::liveValues())])
            ->orderBy('name')->get();

        return view('livewire.batches.batch-manager', [
            'batches' => $batches,
            'record' => $this->viewingId ? Batch::with(['course', 'teacher', 'classroom'])->find($this->viewingId) : null,
            'batchOptions' => $batches->pluck('name', 'id'),
            'unassigned' => Enrollment::with(['student', 'course'])
                ->whereNull('batch_id')->whereIn('status', EnrollmentStatus::liveValues())->get(),
            'assignedByBatch' => Enrollment::with('student')
                ->whereNotNull('batch_id')->whereIn('status', EnrollmentStatus::liveValues())->get()->groupBy('batch_id'),
            'courses' => Course::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'teachers' => Staff::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'rooms' => Classroom::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
