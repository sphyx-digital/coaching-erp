<?php

namespace App\Livewire\Portal;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\Exams\ExamAttemptService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.portal')]
class PortalExamAttempt extends Component
{
    use WithCurrentStudent;

    public int $examId;

    public ?int $attemptId = null;

    public bool $submitted = false;

    public ?string $error = null;

    /** @var array<int,string> question_id => selected option key */
    public array $answers = [];

    public function mount(Exam $exam): void
    {
        $this->initPortal();
        $student = $this->currentStudent();

        // Only the student themselves may sit an exam (not a parent account).
        abort_unless($student && Auth::user()->studentProfile?->id === $student->id, 403);

        $this->examId = $exam->id;

        try {
            $attempt = app(ExamAttemptService::class)->start($exam, $student);
        } catch (DomainException $e) {
            // Already submitted, or the exam is closed: show whatever exists.
            $attempt = ExamAttempt::where('exam_id', $exam->id)->where('student_id', $student->id)->first();
            if (! $attempt) {
                $this->error = $e->getMessage();

                return;
            }
        }

        $this->attemptId = $attempt->id;
        $this->submitted = $attempt->isSubmitted();

        // Resume: pre-fill previously saved answers.
        foreach ($attempt->answers as $ans) {
            if ($ans->selected_option) {
                $this->answers[$ans->question_id] = $ans->selected_option;
            }
        }
    }

    public function submitExam(): void
    {
        if ($this->submitted || ! $this->attemptId) {
            return;
        }

        $attempt = ExamAttempt::findOrFail($this->attemptId);
        // Ownership guard.
        abort_unless(Auth::user()->studentProfile?->id === $attempt->student_id, 403);

        try {
            app(ExamAttemptService::class)->submit($attempt, $this->answers);
            $this->submitted = true;
        } catch (DomainException $e) {
            $this->submitted = true; // already submitted elsewhere
        }
    }

    /** Questions in attempt order (deterministically shuffled when configured). */
    private function orderedQuestions(Exam $exam)
    {
        $questions = $exam->questions;
        if ($exam->shuffle_questions && $this->attemptId) {
            return $questions->shuffle($this->attemptId); // seeded => stable across renders
        }

        return $questions;
    }

    public function render()
    {
        $exam = Exam::with('questions')->findOrFail($this->examId);
        $attempt = $this->attemptId ? ExamAttempt::with('answers')->find($this->attemptId) : null;

        $secondsRemaining = 0;
        if ($attempt && ! $this->submitted && $attempt->deadline()) {
            $secondsRemaining = max(0, now()->diffInSeconds($attempt->deadline(), false));
        }

        return view('livewire.portal.portal-exam-attempt', [
            'exam' => $exam,
            'attempt' => $attempt,
            'questions' => $this->orderedQuestions($exam),
            'secondsRemaining' => (int) $secondsRemaining,
            'reviewAnswers' => $attempt && $this->submitted ? $attempt->answers->keyBy('question_id') : collect(),
        ]);
    }
}
