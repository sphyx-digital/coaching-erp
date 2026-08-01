<?php

namespace App\Livewire\Timetable;

use App\Models\Batch;
use App\Models\Classroom;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\TimetableSlot;
use App\Services\Timetable\TimetableService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class TimetableManager extends Component
{
    public string $mode = 'batch';        // batch | teacher | room

    #[Url]
    public ?int $batch = null;            // selected batch id (also the view target in batch mode)
    public ?int $viewTeacher = null;
    public ?int $viewRoom = null;

    // Add-slot form
    public ?int $day_of_week = 1;
    public ?string $start_time = null;
    public ?string $end_time = null;
    public ?int $subject_id = null;
    public ?int $teacher_id = null;
    public ?int $classroom_id = null;

    public const DAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'];

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('batch.view'), 403);
        $this->batch ??= Batch::orderBy('name')->value('id');
    }

    public function addSlot(TimetableService $service): void
    {
        abort_unless(Auth::user()?->can('batch.update'), 403);

        $data = $this->validate([
            'batch' => ['required', 'exists:batches,id'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'exists:staff,id'],
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
        ]);

        try {
            $service->addSlot([
                'batch_id' => $data['batch'],
                'day_of_week' => $data['day_of_week'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'subject_id' => $data['subject_id'],
                'teacher_id' => $data['teacher_id'],
                'classroom_id' => $data['classroom_id'],
            ]);
            $this->reset(['start_time', 'end_time', 'subject_id', 'teacher_id', 'classroom_id']);
        } catch (\DomainException $e) {
            $this->addError('slot', $e->getMessage());
        }
    }

    public function removeSlot(int $id, TimetableService $service): void
    {
        abort_unless(Auth::user()?->can('batch.update'), 403);
        $service->removeSlot(TimetableSlot::findOrFail($id));
    }

    public function render()
    {
        $query = TimetableSlot::with(['batch', 'subject', 'teacher', 'classroom']);
        $query = match ($this->mode) {
            'teacher' => $query->where('teacher_id', $this->viewTeacher),
            'room' => $query->where('classroom_id', $this->viewRoom),
            default => $query->where('batch_id', $this->batch),
        };

        $slots = $query->orderBy('day_of_week')->orderBy('start_time')->get()->groupBy('day_of_week');

        $selectedBatch = $this->batch ? Batch::find($this->batch) : null;

        return view('livewire.timetable.timetable-manager', [
            'days' => self::DAYS,
            'slots' => $slots,
            'batches' => Batch::orderBy('name')->pluck('name', 'id'),
            'teachers' => Staff::orderBy('name')->pluck('name', 'id'),
            'rooms' => Classroom::orderBy('name')->pluck('name', 'id'),
            'subjects' => $selectedBatch
                ? Subject::where('course_id', $selectedBatch->course_id)->orWhereNull('course_id')->orderBy('name')->pluck('name', 'id')
                : Subject::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
