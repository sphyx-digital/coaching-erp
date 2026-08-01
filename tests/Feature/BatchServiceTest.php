<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Institute;
use App\Models\Student;
use App\Services\Batches\BatchService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchServiceTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private Branch $branch;

    private Course $course;

    private AcademicSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institute = Institute::create(['name' => 'Acme']);
        $this->branch = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Main', 'code' => 'MN']);
        $this->course = Course::create(['institute_id' => $this->institute->id, 'name' => 'JEE', 'code' => 'JEE']);
        $this->session = AcademicSession::create(['institute_id' => $this->institute->id, 'name' => '2026-27', 'is_active' => true]);
    }

    private function service(): BatchService
    {
        return app(BatchService::class);
    }

    private function batch(int $capacity, string $code = 'B1'): Batch
    {
        return Batch::create([
            'institute_id' => $this->institute->id, 'branch_id' => $this->branch->id,
            'course_id' => $this->course->id, 'academic_session_id' => $this->session->id,
            'name' => "Batch {$code}", 'code' => $code, 'capacity' => $capacity,
        ]);
    }

    private function enrollment(string $name): Enrollment
    {
        $student = Student::create(['institute_id' => $this->institute->id, 'branch_id' => $this->branch->id, 'name' => $name]);

        return Enrollment::create([
            'institute_id' => $this->institute->id, 'branch_id' => $this->branch->id, 'student_id' => $student->id,
            'course_id' => $this->course->id, 'academic_session_id' => $this->session->id, 'status' => 'active',
        ]);
    }

    public function test_enrolling_past_capacity_is_blocked(): void
    {
        $batch = $this->batch(capacity: 2);
        $this->service()->assign($this->enrollment('A'), $batch);
        $this->service()->assign($this->enrollment('B'), $batch);

        $this->expectException(DomainException::class);
        $this->service()->assign($this->enrollment('C'), $batch);
    }

    public function test_moving_a_student_records_the_reason_in_the_audit(): void
    {
        $a = $this->batch(5, 'A');
        $b = $this->batch(5, 'B');
        $enrollment = $this->enrollment('Riya');
        $this->service()->assign($enrollment, $a);

        $this->service()->move($enrollment, $b, 'Better timing');

        $this->assertSame($b->id, $enrollment->fresh()->batch_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'batch.moved', 'auditable_id' => $enrollment->id]);
    }

    public function test_a_batch_with_live_students_cannot_be_deleted(): void
    {
        $batch = $this->batch(5);
        $this->service()->assign($this->enrollment('A'), $batch);

        $this->expectException(DomainException::class);
        $this->service()->delete($batch);
    }

    public function test_an_empty_batch_can_be_deleted(): void
    {
        $batch = $this->batch(5);
        $this->service()->delete($batch);
        $this->assertDatabaseMissing('batches', ['id' => $batch->id]);
    }
}
