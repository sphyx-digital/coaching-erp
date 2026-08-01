<?php

namespace Tests\Feature;

use App\Enums\EnquiryStatus;
use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Enquiry;
use App\Models\Institute;
use App\Models\Student;
use App\Services\Enquiries\EnquiryService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnquiryServiceTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private Branch $branch;

    private AcademicSession $session;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        // Guest context: branch scoping does not apply, so the service logic is
        // tested directly (a real counsellor is scoped to their own branch).
        $this->institute = Institute::create(['name' => 'Acme']);
        $this->branch = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Main', 'code' => 'MN']);
        $this->session = AcademicSession::create(['institute_id' => $this->institute->id, 'name' => '2026-27', 'is_active' => true]);
        $this->course = Course::create(['institute_id' => $this->institute->id, 'name' => 'JEE', 'code' => 'JEE']);
    }

    private function service(): EnquiryService
    {
        return app(EnquiryService::class);
    }

    private function make(array $overrides = []): Enquiry
    {
        return $this->service()->create(array_merge([
            'institute_id' => $this->institute->id,
            'branch_id' => $this->branch->id,
            'academic_session_id' => $this->session->id,
            'course_id' => $this->course->id,
            'name' => 'Riya',
            'phone' => '9990001111',
        ], $overrides));
    }

    public function test_new_enquiry_gets_a_unique_sequential_number(): void
    {
        $a = $this->make();
        $b = $this->make(['name' => 'Arjun', 'phone' => '9990002222']);

        $this->assertSame('ENQ/0001', $a->enquiry_number);
        $this->assertSame('ENQ/0002', $b->enquiry_number);
        $this->assertSame(EnquiryStatus::New, $a->status);
    }

    public function test_status_transition_is_audited_and_logged(): void
    {
        $enquiry = $this->make();

        $this->service()->transition($enquiry, EnquiryStatus::Contacted, 'Called, interested');

        $this->assertSame(EnquiryStatus::Contacted, $enquiry->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'enquiry.status_changed', 'auditable_id' => $enquiry->id]);
        $this->assertDatabaseHas('enquiry_activities', ['enquiry_id' => $enquiry->id, 'from_status' => 'new', 'to_status' => 'contacted']);
    }

    public function test_due_follow_up_appears_on_the_correct_day(): void
    {
        $enquiry = $this->make();
        $this->service()->logActivity($enquiry, 'Call back', '2026-08-05');

        $this->assertTrue(Enquiry::dueBy('2026-08-05')->where('id', $enquiry->id)->exists());
        $this->assertTrue(Enquiry::dueBy('2026-08-06')->where('id', $enquiry->id)->exists());
        $this->assertFalse(Enquiry::dueBy('2026-08-04')->where('id', $enquiry->id)->exists());
    }

    public function test_convert_produces_a_draft_admission(): void
    {
        $enquiry = $this->make(['guardian_name' => 'Papa', 'guardian_phone' => '9998887777']);

        $enrollment = $this->service()->convert($enquiry);

        $this->assertSame('provisional', $enrollment->status->value);
        $this->assertSame(EnquiryStatus::Converted, $enquiry->fresh()->status);
        $this->assertNotNull($enquiry->fresh()->converted_student_id);

        $student = Student::find($enrollment->student_id);
        $this->assertSame('Riya', $student->name);
        $this->assertSame('Papa', $student->guardians()->first()->name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'enquiry.converted']);
    }

    public function test_converting_an_already_converted_enquiry_is_blocked(): void
    {
        $enquiry = $this->make();
        $this->service()->convert($enquiry);

        $this->expectException(DomainException::class);
        $this->service()->convert($enquiry->fresh());
    }

    public function test_duplicate_is_flagged_not_blocked(): void
    {
        $this->make(); // phone 9990001111, course JEE, this session

        $this->assertTrue($this->service()->isDuplicate($this->institute->id, '9990001111', $this->course->id, $this->session->id));
        $this->assertFalse($this->service()->isDuplicate($this->institute->id, '0000000000', $this->course->id, $this->session->id));

        // A second identical enquiry still saves (flag, not block).
        $dup = $this->make();
        $this->assertSame('ENQ/0002', $dup->enquiry_number);
    }
}
