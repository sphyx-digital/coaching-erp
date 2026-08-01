<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\AssessmentSubject;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\GradeScale;
use App\Models\Institute;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Assessments\AssessmentService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private Assessment $assessment;

    private AssessmentSubject $physics;

    private AssessmentSubject $chemistry;

    private GradeScale $scale;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institute = Institute::create(['name' => 'Acme']);
        $branch = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Main', 'code' => 'MN']);
        $course = Course::create(['institute_id' => $this->institute->id, 'name' => 'JEE', 'code' => 'JEE']);
        $session = AcademicSession::create(['institute_id' => $this->institute->id, 'name' => '2026-27', 'is_active' => true]);
        $batch = Batch::create(['institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'course_id' => $course->id, 'academic_session_id' => $session->id, 'name' => 'A', 'code' => 'A']);
        $this->student = Student::create(['institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'name' => 'Riya']);
        Enrollment::create(['institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'student_id' => $this->student->id, 'course_id' => $course->id, 'academic_session_id' => $session->id, 'batch_id' => $batch->id, 'status' => 'active']);

        $phys = Subject::create(['institute_id' => $this->institute->id, 'course_id' => $course->id, 'name' => 'Physics', 'code' => 'PHY']);
        $chem = Subject::create(['institute_id' => $this->institute->id, 'course_id' => $course->id, 'name' => 'Chemistry', 'code' => 'CHE']);
        $this->assessment = Assessment::create(['institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'batch_id' => $batch->id, 'academic_session_id' => $session->id, 'name' => 'Unit Test', 'type' => 'test']);
        $this->physics = AssessmentSubject::create(['assessment_id' => $this->assessment->id, 'subject_id' => $phys->id, 'max_marks' => 100]);
        $this->chemistry = AssessmentSubject::create(['assessment_id' => $this->assessment->id, 'subject_id' => $chem->id, 'max_marks' => 100]);

        $this->scale = GradeScale::create(['institute_id' => $this->institute->id, 'name' => 'Default', 'is_active' => true]);
        foreach ([['A+', 9000, 10000], ['A', 8000, 8999], ['B', 7000, 7999], ['C', 6000, 6999], ['D', 4000, 5999], ['F', 0, 3999]] as [$g, $min, $max]) {
            $this->scale->bands()->create(['grade' => $g, 'min_bp' => $min, 'max_bp' => $max]);
        }
    }

    private function service(): AssessmentService
    {
        return app(AssessmentService::class);
    }

    public function test_a_mark_above_the_maximum_is_rejected(): void
    {
        $this->expectException(DomainException::class);
        $this->service()->enterMark($this->physics, $this->student->id, 105); // max 100
    }

    public function test_grades_map_at_each_boundary(): void
    {
        $svc = $this->service();
        $this->assertSame('A+', $svc->gradeFor(10000, $this->scale));
        $this->assertSame('A+', $svc->gradeFor(9000, $this->scale));
        $this->assertSame('A', $svc->gradeFor(8000, $this->scale));
        $this->assertSame('B', $svc->gradeFor(7999, $this->scale));
        $this->assertSame('D', $svc->gradeFor(4000, $this->scale));
        $this->assertSame('F', $svc->gradeFor(3999, $this->scale));
    }

    public function test_total_and_percentage_are_computed(): void
    {
        $svc = $this->service();
        $svc->enterMark($this->physics, $this->student->id, 80);
        $svc->enterMark($this->chemistry, $this->student->id, 90);

        $result = $svc->computeStudent($this->assessment->fresh(), $this->student->id, $this->scale);

        $this->assertSame(170.0, $result['total']);
        $this->assertSame(200.0, $result['max_total']);
        $this->assertSame(8500, $result['percent_bp']);
        $this->assertSame('A', $result['grade']);
    }

    public function test_a_missing_mark_is_excluded_not_zero(): void
    {
        $svc = $this->service();
        $svc->enterMark($this->physics, $this->student->id, 80); // chemistry not entered

        $result = $svc->computeStudent($this->assessment->fresh(), $this->student->id, $this->scale);

        // Only physics counts; chemistry is excluded, not scored as zero.
        $this->assertSame(80.0, $result['total']);
        $this->assertSame(100.0, $result['max_total']);
        $this->assertSame(8000, $result['percent_bp']);
    }

    public function test_report_card_generation_and_republish_supersedes(): void
    {
        $svc = $this->service();
        $svc->enterMark($this->physics, $this->student->id, 80);
        $svc->enterMark($this->chemistry, $this->student->id, 90);

        $v1 = $svc->generateReportCard($this->assessment->fresh(), $this->student->id, $this->scale);
        $this->assertSame(1, $v1->version);
        $this->assertSame(8500, $v1->percentage_bp);
        $this->assertSame('published', $v1->status);

        $v2 = $svc->generateReportCard($this->assessment->fresh(), $this->student->id, $this->scale);
        $this->assertSame(2, $v2->version);
        $this->assertSame('superseded', $v1->fresh()->status); // prior kept as history
        $this->assertSame(1, ReportCard::where('student_id', $this->student->id)->where('status', 'published')->count());
    }

    public function test_editing_a_mark_after_publish_is_audited(): void
    {
        $svc = $this->service();
        $this->assessment->update(['status' => 'published']);

        $svc->enterMark($this->physics->fresh()->load('assessment'), $this->student->id, 70);

        $this->assertDatabaseHas('audit_logs', ['action' => 'mark.changed']);
    }
}
