<?php

namespace App\Services\Exams;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Student;
use App\Services\Audit\AuditLogger;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Taking side of the exam engine: starting an attempt, and grading it
 * deterministically on submit (single-correct MCQ, optional negative marking).
 */
class ExamAttemptService
{
    public function __construct(
        private ExamService $exams,
        private AuditLogger $audit,
    ) {}

    /**
     * Begin (or resume) a student's attempt. One attempt per student per exam.
     */
    public function start(Exam $exam, Student $student): ExamAttempt
    {
        if (! $exam->isOpen()) {
            throw new DomainException('This exam is not open for attempts right now.');
        }

        return DB::transaction(function () use ($exam, $student) {
            $existing = ExamAttempt::where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->isSubmitted()) {
                    throw new DomainException('You have already submitted this exam.');
                }

                return $existing; // resume in-progress
            }

            $this->exams->syncTotal($exam->fresh('questions'));

            return ExamAttempt::create([
                'institute_id' => $exam->institute_id,
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'started_at' => now(),
                'status' => 'in_progress',
                'max_score' => $exam->fresh()->total_marks,
            ]);
        });
    }

    /**
     * Grade and finalise an attempt.
     *
     * @param  array<int,string|null>  $answers  question_id => selected option key
     */
    public function submit(ExamAttempt $attempt, array $answers): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $answers) {
            $attempt = ExamAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($attempt->isSubmitted()) {
                throw new DomainException('This attempt has already been submitted.');
            }

            $exam = $attempt->exam()->with('questions')->first();
            $expired = $attempt->deadline() && now()->greaterThan($attempt->deadline());

            $score = 0;
            $correct = 0;
            $wrong = 0;
            $unanswered = 0;

            foreach ($exam->questions as $question) {
                $selected = $this->normalise($question, $answers[$question->id] ?? null);
                $marksFor = $exam->marksFor($question);

                if ($selected === null) {
                    $isCorrect = false;
                    $awarded = 0;
                    $unanswered++;
                } elseif ($selected === $question->correct_option) {
                    $isCorrect = true;
                    $awarded = $marksFor;
                    $correct++;
                    $score += $awarded;
                } else {
                    $isCorrect = false;
                    $awarded = $exam->negative_marking ? -1 * (int) $question->negative_marks : 0;
                    $wrong++;
                    $score += $awarded;
                }

                $attempt->answers()->updateOrCreate(
                    ['question_id' => $question->id],
                    ['selected_option' => $selected, 'is_correct' => $isCorrect, 'marks_awarded' => $awarded],
                );
            }

            $attempt->forceFill([
                'status' => $expired ? 'auto_submitted' : 'submitted',
                'submitted_at' => now(),
                'score' => $score,
                'max_score' => $exam->total_marks,
                'correct_count' => $correct,
                'wrong_count' => $wrong,
                'unanswered_count' => $unanswered,
            ])->save();

            $this->audit->log('exam.submitted', $attempt, after: [
                'exam' => $exam->title,
                'score' => $score,
                'max_score' => $exam->total_marks,
                'auto' => $expired,
            ]);

            return $attempt;
        });
    }

    /** Finalise any in-progress attempts whose deadline has passed (cron-safe). */
    public function autoSubmitExpired(): int
    {
        $count = 0;
        ExamAttempt::where('status', 'in_progress')->with('exam')->chunkById(100, function ($attempts) use (&$count) {
            foreach ($attempts as $attempt) {
                if ($attempt->deadline() && now()->greaterThan($attempt->deadline())) {
                    $this->submit($attempt, []); // grade whatever was saved
                    $count++;
                }
            }
        });

        return $count;
    }

    /** Only accept a selection that is a real option key for this question. */
    private function normalise(Question $question, mixed $selected): ?string
    {
        if ($selected === null || $selected === '') {
            return null;
        }

        $keys = collect($question->options ?? [])->pluck('key')->all();

        return in_array($selected, $keys, true) ? (string) $selected : null;
    }
}
