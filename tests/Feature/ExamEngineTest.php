<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Institute;
use App\Models\Question;
use App\Models\Student;
use App\Services\Exams\ExamAnalyticsService;
use App\Services\Exams\ExamAttemptService;
use App\Services\Exams\ExamService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamEngineTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private ExamService $exams;

    private ExamAttemptService $attempts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institute = Institute::create(['name' => 'Acme']);
        $this->exams = app(ExamService::class);
        $this->attempts = app(ExamAttemptService::class);
    }

    private function question(string $correct, int $marks = 4, int $neg = 1): Question
    {
        return Question::create([
            'institute_id' => $this->institute->id,
            'body' => 'What is 2 + 2?',
            'options' => [['key' => 'A', 'text' => '3'], ['key' => 'B', 'text' => '4'], ['key' => 'C', 'text' => '5'], ['key' => 'D', 'text' => '6']],
            'correct_option' => $correct,
            'marks' => $marks,
            'negative_marks' => $neg,
        ]);
    }

    private function exam(bool $negative = true, int $duration = 60): Exam
    {
        return Exam::create([
            'institute_id' => $this->institute->id,
            'title' => 'Mock Test 1',
            'duration_minutes' => $duration,
            'pass_percentage' => 40,
            'negative_marking' => $negative,
            'status' => 'draft',
        ]);
    }

    private function student(): Student
    {
        return Student::create(['institute_id' => $this->institute->id, 'name' => 'Test Student']);
    }

    public function test_adding_questions_caches_the_total_marks(): void
    {
        $exam = $this->exam();
        $this->exams->addQuestions($exam, [$this->question('B', 4)->id, $this->question('B', 4)->id, $this->question('B', 2)->id]);

        $this->assertSame(10, $exam->fresh()->total_marks);
    }

    public function test_cannot_publish_an_empty_exam(): void
    {
        $this->expectException(DomainException::class);
        $this->exams->publish($this->exam());
    }

    public function test_cannot_start_an_unpublished_exam(): void
    {
        $exam = $this->exam();
        $this->exams->addQuestions($exam, [$this->question('B')->id]);

        $this->expectException(DomainException::class);
        $this->attempts->start($exam->fresh(), $this->student());
    }

    public function test_scoring_with_negative_marking(): void
    {
        $exam = $this->exam(negative: true);
        $q1 = $this->question('B', 4, 1); // will answer correctly
        $q2 = $this->question('B', 4, 1); // will answer wrongly
        $q3 = $this->question('B', 4, 1); // will leave blank
        $q4 = $this->question('B', 4, 1); // invalid option -> treated as blank
        $this->exams->addQuestions($exam, [$q1->id, $q2->id, $q3->id, $q4->id]);
        $this->exams->publish($exam->fresh());

        $attempt = $this->attempts->start($exam->fresh(), $this->student());
        $attempt = $this->attempts->submit($attempt, [
            $q1->id => 'B',   // correct  => +4
            $q2->id => 'A',   // wrong    => -1
            $q3->id => null,  // blank    =>  0
            $q4->id => 'Z',   // invalid  =>  0 (blank)
        ]);

        $this->assertSame(3, $attempt->score);          // 4 - 1
        $this->assertSame(16, $attempt->max_score);     // 4 * 4
        $this->assertSame(1, $attempt->correct_count);
        $this->assertSame(1, $attempt->wrong_count);
        $this->assertSame(2, $attempt->unanswered_count);
        $this->assertSame('submitted', $attempt->status);
    }

    public function test_negative_marking_off_does_not_penalise_wrong_answers(): void
    {
        $exam = $this->exam(negative: false);
        $q1 = $this->question('B', 4, 1);
        $q2 = $this->question('B', 4, 1);
        $this->exams->addQuestions($exam, [$q1->id, $q2->id]);
        $this->exams->publish($exam->fresh());

        $attempt = $this->attempts->start($exam->fresh(), $this->student());
        $attempt = $this->attempts->submit($attempt, [$q1->id => 'B', $q2->id => 'A']);

        $this->assertSame(4, $attempt->score); // +4, and 0 (not -1) for the wrong one
    }

    public function test_one_attempt_per_student_and_no_double_submit(): void
    {
        $exam = $this->exam();
        $q = $this->question('B');
        $this->exams->addQuestions($exam, [$q->id]);
        $this->exams->publish($exam->fresh());
        $student = $this->student();

        $attempt = $this->attempts->start($exam->fresh(), $student);
        // Resuming returns the same in-progress attempt.
        $this->assertSame($attempt->id, $this->attempts->start($exam->fresh(), $student)->id);

        $this->attempts->submit($attempt, [$q->id => 'B']);

        // Starting again after submission is refused.
        $this->expectException(DomainException::class);
        $this->attempts->start($exam->fresh(), $student);
    }

    public function test_cannot_submit_twice(): void
    {
        $exam = $this->exam();
        $q = $this->question('B');
        $this->exams->addQuestions($exam, [$q->id]);
        $this->exams->publish($exam->fresh());
        $attempt = $this->attempts->start($exam->fresh(), $this->student());
        $this->attempts->submit($attempt, [$q->id => 'B']);

        $this->expectException(DomainException::class);
        $this->attempts->submit($attempt->fresh(), [$q->id => 'A']);
    }

    public function test_analytics_summary_and_question_difficulty(): void
    {
        $exam = $this->exam(negative: false);
        $q1 = $this->question('B', 4, 0);
        $q2 = $this->question('B', 4, 0);
        $this->exams->addQuestions($exam, [$q1->id, $q2->id]);
        $this->exams->publish($exam->fresh());

        // Student A: both correct (8/8 = 100% pass). Student B: one correct (4/8 = 50% pass).
        $a = $this->attempts->start($exam->fresh(), $this->student());
        $this->attempts->submit($a, [$q1->id => 'B', $q2->id => 'B']);
        $b = $this->attempts->start($exam->fresh(), $this->student());
        $this->attempts->submit($b, [$q1->id => 'B', $q2->id => 'A']);

        $summary = app(ExamAnalyticsService::class)->summary($exam->fresh());
        $this->assertSame(2, $summary['attempts']);
        $this->assertSame(6.0, $summary['average']);   // (8 + 4) / 2
        $this->assertSame(8, $summary['highest']);
        $this->assertSame(4, $summary['lowest']);
        $this->assertSame(2, $summary['pass_count']);  // both >= 40%
        $this->assertSame(100.0, $summary['pass_rate']);

        $stats = collect(app(ExamAnalyticsService::class)->questionStats($exam->fresh()))->keyBy('question_id');
        $this->assertSame(100.0, $stats[$q1->id]['correct_rate']); // both right
        $this->assertSame(50.0, $stats[$q2->id]['correct_rate']);  // one right of two
    }
}
