<?php

namespace App\Livewire\Exams;

use App\Models\Course;
use App\Models\Exam;
use App\Models\Question;
use App\Services\Exams\ExamAnalyticsService;
use App\Services\Exams\ExamService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ExamManager extends Component
{
    // Exam create/edit
    public bool $showExam = false;

    public ?int $editingExam = null;

    /** @var array<string,mixed> */
    public array $exam = [];

    // Question builder
    public bool $showBuilder = false;

    public ?int $builderExamId = null;

    /** @var array<string,mixed> */
    public array $newQ = [];

    // Results
    public bool $showResults = false;

    public ?int $resultsExamId = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('assessment.view') || Auth::user()?->hasAllBranchAccess(), 403);
    }

    private function canManage(): bool
    {
        return Auth::user()?->can('assessment.create') || Auth::user()?->hasAllBranchAccess();
    }

    private function blankExam(): array
    {
        return [
            'title' => '', 'course_id' => '', 'duration_minutes' => 60, 'pass_percentage' => 40,
            'negative_marking' => true, 'shuffle_questions' => false,
            'starts_at' => '', 'ends_at' => '', 'instructions' => '',
        ];
    }

    private function blankQuestion(): array
    {
        return [
            'body' => '', 'course_id' => '',
            'A' => '', 'B' => '', 'C' => '', 'D' => '',
            'correct' => 'A', 'marks' => 4, 'negative_marks' => 1,
        ];
    }

    // ---- Exam CRUD ------------------------------------------------------
    public function openCreate(): void
    {
        abort_unless($this->canManage(), 403);
        $this->resetValidation();
        $this->editingExam = null;
        $this->exam = $this->blankExam();
        $this->showExam = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $e = Exam::findOrFail($id);
        $this->editingExam = $id;
        $this->exam = [
            'title' => $e->title, 'course_id' => $e->course_id ?: '',
            'duration_minutes' => $e->duration_minutes, 'pass_percentage' => $e->pass_percentage,
            'negative_marking' => (bool) $e->negative_marking, 'shuffle_questions' => (bool) $e->shuffle_questions,
            'starts_at' => optional($e->starts_at)->format('Y-m-d\TH:i'),
            'ends_at' => optional($e->ends_at)->format('Y-m-d\TH:i'),
            'instructions' => $e->instructions ?? '',
        ];
        $this->showExam = true;
    }

    public function saveExam(): void
    {
        abort_unless($this->canManage(), 403);
        $this->validate([
            'exam.title' => ['required', 'string', 'max:150'],
            'exam.duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'exam.pass_percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $payload = [
            'institute_id' => current_institute()?->id,
            'title' => $this->exam['title'],
            'course_id' => $this->exam['course_id'] ?: null,
            'academic_session_id' => active_session()?->id,
            'duration_minutes' => (int) $this->exam['duration_minutes'],
            'pass_percentage' => (int) $this->exam['pass_percentage'],
            'negative_marking' => (bool) $this->exam['negative_marking'],
            'shuffle_questions' => (bool) $this->exam['shuffle_questions'],
            'starts_at' => $this->exam['starts_at'] ?: null,
            'ends_at' => $this->exam['ends_at'] ?: null,
            'instructions' => $this->exam['instructions'] ?: null,
        ];

        if ($this->editingExam) {
            Exam::findOrFail($this->editingExam)->update($payload);
        } else {
            Exam::create($payload);
        }

        $this->showExam = false;
        session()->flash('exam_saved', true);
    }

    public function publish(int $id): void
    {
        abort_unless($this->canManage(), 403);
        try {
            app(ExamService::class)->publish(Exam::findOrFail($id));
        } catch (\DomainException $e) {
            $this->addError('publish', $e->getMessage());
        }
    }

    public function close(int $id): void
    {
        abort_unless($this->canManage(), 403);
        app(ExamService::class)->close(Exam::findOrFail($id));
    }

    // ---- Question builder ----------------------------------------------
    public function openBuilder(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $this->builderExamId = $id;
        $this->newQ = $this->blankQuestion();
        $this->showBuilder = true;
    }

    public function addQuestion(): void
    {
        abort_unless($this->canManage(), 403);
        $this->validate([
            'newQ.body' => ['required', 'string'],
            'newQ.A' => ['required', 'string', 'max:255'],
            'newQ.B' => ['required', 'string', 'max:255'],
            'newQ.C' => ['nullable', 'string', 'max:255'],
            'newQ.D' => ['nullable', 'string', 'max:255'],
            'newQ.correct' => ['required', 'in:A,B,C,D'],
            'newQ.marks' => ['required', 'integer', 'min:1', 'max:100'],
            'newQ.negative_marks' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $options = [];
        foreach (['A', 'B', 'C', 'D'] as $k) {
            if (trim((string) $this->newQ[$k]) !== '') {
                $options[] = ['key' => $k, 'text' => $this->newQ[$k]];
            }
        }
        // Correct option must exist among the filled options.
        if (! collect($options)->pluck('key')->contains($this->newQ['correct'])) {
            $this->addError('newQ.correct', 'The correct option must be one you filled in.');

            return;
        }

        $exam = Exam::findOrFail($this->builderExamId);
        $question = Question::create([
            'institute_id' => current_institute()?->id,
            'course_id' => $exam->course_id,
            'body' => $this->newQ['body'],
            'options' => $options,
            'correct_option' => $this->newQ['correct'],
            'marks' => (int) $this->newQ['marks'],
            'negative_marks' => (int) $this->newQ['negative_marks'],
        ]);

        app(ExamService::class)->addQuestions($exam, [$question->id]);
        $this->newQ = $this->blankQuestion();
        session()->flash('q_added', true);
    }

    public function removeQuestion(int $questionId): void
    {
        abort_unless($this->canManage(), 403);
        app(ExamService::class)->removeQuestion(Exam::findOrFail($this->builderExamId), $questionId);
    }

    // ---- Results --------------------------------------------------------
    public function openResults(int $id): void
    {
        $this->resultsExamId = $id;
        $this->showResults = true;
    }

    public function render()
    {
        $builderExam = $this->builderExamId ? Exam::with('questions')->find($this->builderExamId) : null;

        $resultsExam = $this->resultsExamId ? Exam::with('questions')->find($this->resultsExamId) : null;
        $analytics = null;
        $attempts = collect();
        $qStats = [];
        if ($resultsExam) {
            $svc = app(ExamAnalyticsService::class);
            $analytics = $svc->summary($resultsExam);
            $qStats = collect($svc->questionStats($resultsExam))->keyBy('question_id');
            $attempts = $resultsExam->attempts()->with('student')->whereIn('status', ['submitted', 'auto_submitted'])->orderByDesc('score')->get();
        }

        return view('livewire.exams.exam-manager', [
            'exams' => Exam::withCount(['questions', 'attempts'])->latest()->get(),
            'courses' => Course::orderBy('name')->get(),
            'builderExam' => $builderExam,
            'resultsExam' => $resultsExam,
            'analytics' => $analytics,
            'attempts' => $attempts,
            'qStats' => $qStats,
        ]);
    }
}
