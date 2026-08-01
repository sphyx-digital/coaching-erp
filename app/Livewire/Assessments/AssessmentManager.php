<?php

namespace App\Livewire\Assessments;

use App\Models\Assessment;
use App\Models\AssessmentSubject;
use App\Models\Batch;
use App\Models\GradeScale;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Assessments\AssessmentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AssessmentManager extends Component
{
    // create assessment
    public ?int $batchId = null;

    public string $name = '';

    public string $type = 'test';

    public ?string $assessmentDate = null;

    // selection
    public ?int $selectedId = null;

    // add subject
    public ?int $subjectId = null;

    public ?float $maxMarks = 100;

    // marks grid: [assessmentSubjectId][studentId] => value
    public array $marks = [];

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('assessment.view'), 403);
    }

    public function createAssessment(): void
    {
        abort_unless(Auth::user()?->can('assessment.create'), 403);
        $data = $this->validate([
            'batchId' => ['required', 'exists:batches,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:test,exam'],
            'assessmentDate' => ['nullable', 'date'],
        ]);

        $batch = Batch::findOrFail($data['batchId']);
        $assessment = Assessment::create([
            'institute_id' => current_institute()?->id,
            'branch_id' => $batch->branch_id,
            'batch_id' => $batch->id,
            'academic_session_id' => $batch->academic_session_id,
            'name' => $data['name'],
            'type' => $data['type'],
            'assessment_date' => $data['assessmentDate'],
        ]);
        $this->reset(['name', 'assessmentDate']);
        $this->selectedId = $assessment->id;
    }

    public function select(int $id): void
    {
        $this->selectedId = $id;
        $this->loadMarks();
    }

    public function addSubject(): void
    {
        abort_unless(Auth::user()?->can('assessment.update'), 403);
        $data = $this->validate([
            'subjectId' => ['required', 'exists:subjects,id'],
            'maxMarks' => ['required', 'numeric', 'min:1'],
        ]);

        AssessmentSubject::firstOrCreate(
            ['assessment_id' => $this->selectedId, 'subject_id' => $data['subjectId']],
            ['max_marks' => $data['maxMarks']],
        );
        $this->reset(['subjectId']);
        $this->maxMarks = 100;
        $this->loadMarks();
    }

    private function loadMarks(): void
    {
        $this->marks = [];
        $assessment = Assessment::with('subjects.marks')->find($this->selectedId);
        if (! $assessment) {
            return;
        }
        foreach ($assessment->subjects as $as) {
            foreach ($as->marks as $m) {
                $this->marks[$as->id][$m->student_id] = $m->is_absent ? 'A' : $m->marks_obtained;
            }
        }
    }

    public function saveMarks(AssessmentService $service): void
    {
        abort_unless(Auth::user()?->can('assessment.update'), 403);
        $assessment = Assessment::with('subjects')->findOrFail($this->selectedId);

        try {
            foreach ($assessment->subjects as $as) {
                foreach (($this->marks[$as->id] ?? []) as $studentId => $value) {
                    if ($value === '' || $value === null) {
                        continue;
                    }
                    $absent = strtoupper((string) $value) === 'A';
                    $service->enterMark($as, (int) $studentId, $absent ? null : (float) $value, $absent);
                }
            }
            session()->flash('ok', 'Marks saved.');
        } catch (\DomainException $e) {
            $this->addError('marks', $e->getMessage());
        }
    }

    public function publish(): void
    {
        abort_unless(Auth::user()?->can('assessment.approve'), 403);
        Assessment::findOrFail($this->selectedId)->update(['status' => 'published', 'published_at' => now()]);
        session()->flash('ok', 'Assessment published.');
    }

    public function generateCards(AssessmentService $service): void
    {
        abort_unless(Auth::user()?->can('assessment.approve'), 403);
        $assessment = Assessment::findOrFail($this->selectedId);
        $scale = GradeScale::where('is_active', true)->firstOrFail();

        foreach ($this->rosterIds($assessment) as $sid) {
            $service->generateReportCard($assessment, (int) $sid, $scale);
        }
        session()->flash('ok', 'Report cards generated.');
    }

    private function rosterIds(Assessment $assessment): array
    {
        return $assessment->batch->enrollments()->whereIn('status', ['provisional', 'active', 'on_hold'])
            ->pluck('student_id')->unique()->all();
    }

    public function render(AssessmentService $service)
    {
        $assessment = $this->selectedId ? Assessment::with('subjects.subject')->find($this->selectedId) : null;
        $scale = GradeScale::where('is_active', true)->first();

        return view('livewire.assessments.assessment-manager', [
            'batches' => Batch::orderBy('name')->pluck('name', 'id'),
            'assessments' => Assessment::with('batch')->latest()->limit(50)->get(),
            'assessment' => $assessment,
            'students' => $assessment ? Student::whereIn('id', $this->rosterIds($assessment))->orderBy('name')->get() : collect(),
            'subjectOptions' => Subject::orderBy('name')->pluck('name', 'id'),
            'performance' => ($assessment && $scale) ? $service->batchPerformance($assessment, $scale) : null,
        ]);
    }
}
