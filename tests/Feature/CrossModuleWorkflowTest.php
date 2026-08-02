<?php

namespace Tests\Feature;

use App\Enums\EnquiryStatus;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\AssessmentSubject;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Course;
use App\Models\GradeScale;
use App\Models\Guardian;
use App\Models\Institute;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Admissions\AdmissionService;
use App\Services\Assessments\AssessmentService;
use App\Services\Attendance\AttendanceService;
use App\Services\Enquiries\EnquiryService;
use App\Services\Fees\FeeService;
use App\Services\Fees\LedgerService;
use App\Services\Fees\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossModuleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private Branch $branch;

    private Course $course;

    private AcademicSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institute = Institute::create(['name' => 'Acme', 'state_code' => '27']);
        $this->branch = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Main', 'code' => 'MN']);
        $this->course = Course::create(['institute_id' => $this->institute->id, 'name' => 'JEE', 'code' => 'JEE']);
        $this->session = AcademicSession::create(['institute_id' => $this->institute->id, 'name' => '2026-27', 'is_active' => true]);
    }

    public function test_enquiry_to_admission_to_fee_to_receipt(): void
    {
        // Enquiry → convert → provisional enrollment
        $enquiry = app(EnquiryService::class)->create([
            'institute_id' => $this->institute->id, 'branch_id' => $this->branch->id,
            'academic_session_id' => $this->session->id, 'course_id' => $this->course->id,
            'name' => 'Riya', 'phone' => '9990001111', 'guardian_name' => 'Papa', 'guardian_phone' => '9990002222',
        ]);
        $enrollment = app(EnquiryService::class)->convert($enquiry);
        $this->assertSame(EnquiryStatus::Converted, $enquiry->fresh()->status);

        // Admission: activate the provisional enrollment (admission number assigned)
        app(AdmissionService::class)->activate($enrollment, ['data', 'communication']);
        $student = $enrollment->fresh()->student;
        $this->assertNotNull($student->fresh()->admission_number);

        // Fee: raise invoice, take payment, get receipt
        $invoice = app(FeeService::class)->createInvoice($student, $enrollment->fresh(),
            [['description' => 'Tuition', 'taxable_value' => 100000, 'rate_bp' => 1800]], false);
        $payment = app(PaymentService::class)->record($student, 118000, 'cash', null, '2026-08-01', [$invoice->id => 118000]);

        $this->assertNotEmpty($payment->receipt_number);
        $this->assertSame(0, $invoice->fresh()->balance);
        $this->assertTrue(app(LedgerService::class)->isBalanced($this->institute->id));
    }

    public function test_enrollment_to_batch_to_attendance_to_report_card(): void
    {
        $batch = Batch::create(['institute_id' => $this->institute->id, 'branch_id' => $this->branch->id, 'course_id' => $this->course->id, 'academic_session_id' => $this->session->id, 'name' => 'A', 'code' => 'A', 'capacity' => 30]);
        $g = Guardian::create(['institute_id' => $this->institute->id, 'name' => 'Papa']);
        $student = Student::create(['institute_id' => $this->institute->id, 'branch_id' => $this->branch->id, 'name' => 'Riya', 'dob' => '2010-01-01']);
        $student->guardians()->attach($g->id, ['is_primary' => true]);
        $enrollment = app(AdmissionService::class)->enroll($student, $this->course->id, $this->session->id, $this->branch->id);
        $enrollment->update(['batch_id' => $batch->id]);

        // Attendance
        $att = app(AttendanceService::class);
        $att->mark($att->openSession($batch, '2026-08-01'), [$student->id => 'present']);
        $att->mark($att->openSession($batch, '2026-08-02'), [$student->id => 'present']);
        $this->assertSame(10000, $att->studentSummary($student->id, $batch->id)['percent_bp']);

        // Assessment → report card
        $subject = Subject::create(['institute_id' => $this->institute->id, 'course_id' => $this->course->id, 'name' => 'Physics', 'code' => 'PHY']);
        $assessment = Assessment::create(['institute_id' => $this->institute->id, 'branch_id' => $this->branch->id, 'batch_id' => $batch->id, 'academic_session_id' => $this->session->id, 'name' => 'Test 1', 'type' => 'test']);
        $as = AssessmentSubject::create(['assessment_id' => $assessment->id, 'subject_id' => $subject->id, 'max_marks' => 100]);
        $scale = GradeScale::create(['institute_id' => $this->institute->id, 'name' => 'Default', 'is_active' => true]);
        $scale->bands()->create(['grade' => 'A', 'min_bp' => 8000, 'max_bp' => 10000]);
        $scale->bands()->create(['grade' => 'F', 'min_bp' => 0, 'max_bp' => 7999]);

        $svc = app(AssessmentService::class);
        $svc->enterMark($as, $student->id, 85);
        $card = $svc->generateReportCard($assessment, $student->id, $scale);

        $this->assertSame(8500, $card->percentage_bp);
        $this->assertSame('A', $card->overall_grade);
        $this->assertSame(10000, $card->attendance_bp); // carried from attendance
    }
}
