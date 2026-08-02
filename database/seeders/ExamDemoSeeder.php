<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Exam;
use App\Models\Question;
use App\Services\Exams\ExamService;
use Illuminate\Database\Seeder;

/**
 * A single published demo exam with a handful of MCQs, so the exam engine and
 * the student portal have something to show. Idempotent: skips if any exam
 * already exists.
 */
class ExamDemoSeeder extends Seeder
{
    public function run(): void
    {
        $institute = current_institute();
        if (! $institute || Exam::count() > 0) {
            return;
        }

        $course = Course::where('institute_id', $institute->id)->orderBy('id')->first();

        $exam = Exam::create([
            'institute_id' => $institute->id,
            'course_id' => $course?->id,
            'academic_session_id' => active_session()?->id,
            'title' => 'Physics & Maths — Weekly Mock 1',
            'instructions' => 'Single correct answer per question. Correct: +4, wrong: -1, unanswered: 0. Best of luck!',
            'duration_minutes' => 20,
            'pass_percentage' => 40,
            'negative_marking' => true,
        ]);

        $bank = [
            ['SI unit of force?', ['Joule', 'Newton', 'Watt', 'Pascal'], 'B'],
            ['Derivative of sin(x)?', ['cos(x)', '-cos(x)', 'sin(x)', '-sin(x)'], 'A'],
            ['Acceleration due to gravity (approx, m/s^2)?', ['8.9', '9.8', '10.8', '11.2'], 'B'],
            ['Value of integral of 1/x dx?', ['x^2/2', 'ln|x| + C', 'e^x + C', '1/x^2'], 'B'],
            ['Which is a scalar quantity?', ['Velocity', 'Force', 'Speed', 'Acceleration'], 'C'],
        ];

        $ids = [];
        foreach ($bank as [$body, $opts, $correct]) {
            $options = [];
            foreach (['A', 'B', 'C', 'D'] as $k => $key) {
                $options[] = ['key' => $key, 'text' => $opts[$k]];
            }
            $ids[] = Question::create([
                'institute_id' => $institute->id,
                'course_id' => $course?->id,
                'body' => $body,
                'options' => $options,
                'correct_option' => $correct,
                'marks' => 4,
                'negative_marks' => 1,
            ])->id;
        }

        $svc = app(ExamService::class);
        $svc->addQuestions($exam, $ids);
        $svc->publish($exam->fresh('questions'));
    }
}
