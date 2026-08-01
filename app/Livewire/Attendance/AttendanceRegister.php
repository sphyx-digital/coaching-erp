<?php

namespace App\Livewire\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Batch;
use App\Models\Student;
use App\Services\Attendance\AttendanceService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AttendanceRegister extends Component
{
    public ?int $batchId = null;

    public string $date = '';

    public array $marks = [];      // studentId => status value

    public bool $finalised = false;

    public function mount(AttendanceService $service): void
    {
        abort_unless(Auth::user()?->can('attendance.view'), 403);
        $this->date = now()->toDateString();
        $this->batchId = $this->allowedBatches()->keys()->first();
        $this->loadMarks($service);
    }

    public function updatedBatchId(AttendanceService $service): void
    {
        $this->loadMarks($service);
    }

    public function updatedDate(AttendanceService $service): void
    {
        $this->loadMarks($service);
    }

    private function allowedBatches()
    {
        $user = Auth::user();
        $query = Batch::where('is_active', true);

        // Teachers see only their assigned batches; admins see all.
        if (! $user->hasAllBranchAccess() && $user->staff) {
            $query->where('teacher_id', $user->staff->id);
        }

        return $query->orderBy('name')->pluck('name', 'id');
    }

    private function loadMarks(AttendanceService $service): void
    {
        $this->marks = [];
        $this->finalised = false;
        if (! $this->batchId) {
            return;
        }

        $batch = Batch::find($this->batchId);
        $roster = $service->rosterIds($batch);

        $session = AttendanceSession::where('batch_id', $this->batchId)
            ->whereDate('session_date', $this->date)->whereNull('timetable_slot_id')->first();
        $existing = $session
            ? AttendanceRecord::where('attendance_session_id', $session->id)->pluck('status', 'student_id')->all()
            : [];
        $this->finalised = $session?->status === 'finalised';

        foreach ($roster as $sid) {
            $this->marks[$sid] = $existing[$sid] ?? 'present';
        }
    }

    public function setMark(int $studentId, string $status): void
    {
        $this->marks[$studentId] = $status;
    }

    public function markAll(string $status): void
    {
        foreach (array_keys($this->marks) as $sid) {
            $this->marks[$sid] = $status;
        }
    }

    public function save(AttendanceService $service): void
    {
        abort_unless(Auth::user()?->can('attendance.create'), 403);
        $batch = Batch::findOrFail($this->batchId);

        $user = Auth::user();
        abort_if(! $user->hasAllBranchAccess() && $batch->teacher_id !== $user->staff?->id, 403, 'Not your batch.');

        $session = $service->openSession($batch, $this->date);
        $service->mark($session, $this->marks);
        session()->flash('ok', 'Attendance saved.');
    }

    public function finalize(AttendanceService $service): void
    {
        abort_unless(Auth::user()?->can('attendance.update'), 403);
        $batch = Batch::findOrFail($this->batchId);
        $session = $service->openSession($batch, $this->date);
        $service->mark($session, $this->marks);
        try {
            $service->finalize($session);
            $this->finalised = true;
            session()->flash('ok', 'Attendance finalised.');
        } catch (\DomainException $e) {
            $this->addError('finalize', $e->getMessage());
        }
    }

    public function render(AttendanceService $service)
    {
        $present = collect($this->marks)->filter(fn ($s) => AttendanceStatus::from($s)->countsAsPresent())->count();
        $total = collect($this->marks)->filter(fn ($s) => AttendanceStatus::from($s)->inDenominator())->count();

        return view('livewire.attendance.attendance-register', [
            'batches' => $this->allowedBatches(),
            'students' => $this->batchId ? Student::whereIn('id', array_keys($this->marks))->orderBy('name')->get() : collect(),
            'present' => $present,
            'total' => $total,
            'percentBp' => $total > 0 ? (int) round($present / $total * 10000) : 0,
            'statuses' => AttendanceStatus::cases(),
            'low' => $this->batchId ? $service->lowAttendance($this->batchId) : [],
        ]);
    }
}
