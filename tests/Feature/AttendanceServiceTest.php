<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Institute;
use App\Models\Student;
use App\Services\Attendance\AttendanceService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private Batch $batch;

    private Student $s1;

    private Student $s2;

    private Student $outsider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institute = Institute::create(['name' => 'Acme']);
        $branch = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Main', 'code' => 'MN']);
        $course = Course::create(['institute_id' => $this->institute->id, 'name' => 'JEE', 'code' => 'JEE']);
        $session = AcademicSession::create(['institute_id' => $this->institute->id, 'name' => '2026-27', 'is_active' => true]);
        $this->batch = Batch::create(['institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'course_id' => $course->id, 'academic_session_id' => $session->id, 'name' => 'A', 'code' => 'A']);

        $this->s1 = $this->enrol('S1', $branch, $course, $session, $this->batch->id);
        $this->s2 = $this->enrol('S2', $branch, $course, $session, $this->batch->id);
        $this->outsider = $this->enrol('Out', $branch, $course, $session, null);
    }

    private function enrol(string $name, Branch $b, Course $c, AcademicSession $s, ?int $batchId): Student
    {
        $student = Student::create(['institute_id' => $this->institute->id, 'branch_id' => $b->id, 'name' => $name]);
        Enrollment::create(['institute_id' => $this->institute->id, 'branch_id' => $b->id, 'student_id' => $student->id, 'course_id' => $c->id, 'academic_session_id' => $s->id, 'batch_id' => $batchId, 'status' => 'active']);

        return $student;
    }

    private function service(): AttendanceService
    {
        return app(AttendanceService::class);
    }

    public function test_marking_a_roster_creates_records(): void
    {
        $session = $this->service()->openSession($this->batch, '2026-08-01');
        $this->service()->mark($session, [$this->s1->id => 'present', $this->s2->id => 'absent']);

        $this->assertDatabaseHas('attendance_records', ['attendance_session_id' => $session->id, 'student_id' => $this->s1->id, 'status' => 'present']);
        $this->assertDatabaseHas('attendance_records', ['attendance_session_id' => $session->id, 'student_id' => $this->s2->id, 'status' => 'absent']);
    }

    public function test_opening_the_same_session_twice_does_not_duplicate(): void
    {
        $a = $this->service()->openSession($this->batch, '2026-08-01');
        $b = $this->service()->openSession($this->batch, '2026-08-01');

        $this->assertSame($a->id, $b->id);
        $this->assertDatabaseCount('attendance_sessions', 1);
    }

    public function test_a_student_not_in_the_batch_cannot_be_marked(): void
    {
        $session = $this->service()->openSession($this->batch, '2026-08-01');

        $this->expectException(DomainException::class);
        $this->service()->mark($session, [$this->outsider->id => 'present']);
    }

    public function test_remarking_updates_rather_than_duplicates(): void
    {
        $session = $this->service()->openSession($this->batch, '2026-08-01');
        $this->service()->mark($session, [$this->s1->id => 'absent']);
        $this->service()->mark($session, [$this->s1->id => 'present']);

        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertDatabaseHas('attendance_records', ['student_id' => $this->s1->id, 'status' => 'present']);
    }

    public function test_summary_percentage_is_correct(): void
    {
        $svc = $this->service();
        $svc->mark($svc->openSession($this->batch, '2026-08-01'), [$this->s1->id => 'present', $this->s2->id => 'absent']);
        $svc->mark($svc->openSession($this->batch, '2026-08-02'), [$this->s1->id => 'late', $this->s2->id => 'present']);

        // s1: present + late = 2 of 2 => 100%
        $this->assertSame(10000, $svc->studentSummary($this->s1->id, $this->batch->id)['percent_bp']);
        // s2: absent + present = 1 of 2 => 50%
        $this->assertSame(5000, $svc->studentSummary($this->s2->id, $this->batch->id)['percent_bp']);
    }

    public function test_editing_a_finalised_session_is_audited(): void
    {
        $svc = $this->service();
        $session = $svc->openSession($this->batch, '2026-08-01');
        $svc->mark($session, [$this->s1->id => 'absent']);
        $svc->finalize($session);

        $svc->mark($session->fresh(), [$this->s1->id => 'present']); // edit after finalise

        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance.edited', 'auditable_id' => $session->id]);
    }

    public function test_a_future_session_cannot_be_finalised(): void
    {
        $session = $this->service()->openSession($this->batch, now()->addYear()->toDateString());

        $this->expectException(DomainException::class);
        $this->service()->finalize($session);
    }

    public function test_low_attendance_flags_students_below_threshold(): void
    {
        $svc = $this->service();
        // 4 sessions; s2 present once => 25%
        foreach (['2026-08-01', '2026-08-02', '2026-08-03', '2026-08-04'] as $i => $date) {
            $svc->mark($svc->openSession($this->batch, $date), [
                $this->s1->id => 'present',
                $this->s2->id => $i === 0 ? 'present' : 'absent',
            ]);
        }

        $low = $svc->lowAttendance($this->batch->id, 7500);
        $this->assertArrayHasKey($this->s2->id, $low);
        $this->assertArrayNotHasKey($this->s1->id, $low);
    }
}
