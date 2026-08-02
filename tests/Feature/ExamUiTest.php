<?php

namespace Tests\Feature;

use App\Livewire\Exams\ExamManager;
use App\Livewire\Portal\PortalExamAttempt;
use App\Livewire\Portal\PortalExams;
use App\Models\Exam;
use App\Models\Guardian;
use App\Models\Institute;
use App\Models\Question;
use App\Models\Student;
use App\Models\User;
use App\Services\Exams\ExamService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExamUiTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->institute = Institute::create(['name' => 'Acme']);
        $this->admin = tap(User::factory()->create())->assignRole('Institute Admin');
    }

    public function test_admin_can_build_and_publish_an_exam(): void
    {
        Livewire::actingAs($this->admin)->test(ExamManager::class)
            ->call('openCreate')
            ->set('exam.title', 'Physics Unit Test')
            ->set('exam.duration_minutes', 30)
            ->set('exam.pass_percentage', 40)
            ->call('saveExam')
            ->assertHasNoErrors();

        $exam = Exam::first();
        $this->assertNotNull($exam);

        Livewire::actingAs($this->admin)->test(ExamManager::class)
            ->call('openBuilder', $exam->id)
            ->set('newQ.body', 'What is the SI unit of force?')
            ->set('newQ.A', 'Joule')->set('newQ.B', 'Newton')
            ->set('newQ.C', 'Watt')->set('newQ.D', 'Pascal')
            ->set('newQ.correct', 'B')->set('newQ.marks', 4)->set('newQ.negative_marks', 1)
            ->call('addQuestion')
            ->assertHasNoErrors();

        $this->assertSame(1, $exam->fresh()->questions()->count());
        $this->assertSame(4, $exam->fresh()->total_marks);

        Livewire::actingAs($this->admin)->test(ExamManager::class)->call('publish', $exam->id);
        $this->assertSame('published', $exam->fresh()->status);
    }

    public function test_student_can_attempt_and_score_a_published_exam(): void
    {
        // Build & publish an exam with two questions.
        $exam = Exam::create(['institute_id' => $this->institute->id, 'title' => 'Mock', 'duration_minutes' => 60, 'pass_percentage' => 40, 'negative_marking' => true, 'status' => 'draft']);
        $mk = fn ($c) => Question::create(['institute_id' => $this->institute->id, 'body' => 'Q', 'options' => [['key' => 'A', 'text' => '1'], ['key' => 'B', 'text' => '2']], 'correct_option' => $c, 'marks' => 4, 'negative_marks' => 1]);
        $q1 = $mk('A');
        $q2 = $mk('B');
        app(ExamService::class)->addQuestions($exam, [$q1->id, $q2->id]);
        app(ExamService::class)->publish($exam->fresh());
        $exam->refresh(); // reflect published status on the instance we pass in

        // A student portal user.
        $studentUser = tap(User::factory()->create())->assignRole('Student');
        $student = Student::create(['institute_id' => $this->institute->id, 'user_id' => $studentUser->id, 'name' => 'Learner']);

        // The exam shows as available in the portal.
        Livewire::actingAs($studentUser)->test(PortalExams::class)->assertSee('Mock');

        // Attempt it: one right (+4), one wrong (-1) => 3.
        Livewire::actingAs($studentUser)->test(PortalExamAttempt::class, ['exam' => $exam])
            ->set("answers.{$q1->id}", 'A')
            ->set("answers.{$q2->id}", 'A')
            ->call('submitExam')
            ->assertSet('submitted', true);

        $attempt = $exam->attempts()->where('student_id', $student->id)->first();
        $this->assertSame(3, $attempt->score);
        $this->assertSame('submitted', $attempt->status);
    }

    public function test_a_parent_cannot_sit_an_exam(): void
    {
        $exam = Exam::create(['institute_id' => $this->institute->id, 'title' => 'Mock', 'duration_minutes' => 60, 'status' => 'published', 'published_at' => now()]);
        $q = Question::create(['institute_id' => $this->institute->id, 'body' => 'Q', 'options' => [['key' => 'A', 'text' => '1']], 'correct_option' => 'A', 'marks' => 1]);
        app(ExamService::class)->addQuestions($exam, [$q->id]);

        $parentUser = tap(User::factory()->create())->assignRole('Parent');
        $guardian = Guardian::create(['institute_id' => $this->institute->id, 'user_id' => $parentUser->id, 'name' => 'Parent']);
        $child = Student::create(['institute_id' => $this->institute->id, 'name' => 'Child']);
        $guardian->students()->attach($child->id, ['is_primary' => true]);

        Livewire::actingAs($parentUser)->test(PortalExamAttempt::class, ['exam' => $exam])->assertForbidden();
    }
}
