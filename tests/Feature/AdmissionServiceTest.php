<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Guardian;
use App\Models\Institute;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\Admissions\AdmissionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private Branch $branch;

    private AcademicSession $session;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institute = Institute::create(['name' => 'Acme']);
        $this->branch = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Main', 'code' => 'MN']);
        $this->session = AcademicSession::create(['institute_id' => $this->institute->id, 'name' => '2026-27', 'is_active' => true]);
        $this->course = Course::create(['institute_id' => $this->institute->id, 'name' => 'JEE', 'code' => 'JEE']);
    }

    private function service(): AdmissionService
    {
        return app(AdmissionService::class);
    }

    private function staffUser(string $role): User
    {
        $user = User::factory()->create();
        Staff::create([
            'user_id' => $user->id,
            'institute_id' => $this->institute->id,
            'branch_id' => $this->branch->id,
            'name' => $role,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function student(?string $dob = null, string $name = 'Riya'): Student
    {
        return Student::create([
            'institute_id' => $this->institute->id,
            'branch_id' => $this->branch->id,
            'name' => $name,
            'dob' => $dob,
        ]);
    }

    private function withPrimaryGuardian(Student $student): Student
    {
        $g = Guardian::create(['institute_id' => $this->institute->id, 'name' => 'Papa']);
        $student->guardians()->attach($g->id, ['is_primary' => true, 'relationship' => 'father']);

        return $student;
    }

    public function test_a_minor_without_a_primary_guardian_cannot_enroll(): void
    {
        $minor = $this->student('2015-01-01'); // ~11 years old

        $this->expectException(DomainException::class);
        $this->service()->enroll($minor, $this->course->id);
    }

    public function test_a_minor_with_a_guardian_enrolls_and_gets_an_admission_number(): void
    {
        $minor = $this->withPrimaryGuardian($this->student('2015-01-01'));

        $enrollment = $this->service()->enroll($minor, $this->course->id);

        $this->assertSame(EnrollmentStatus::Active, $enrollment->status);
        $this->assertSame('ADM/0001', $minor->fresh()->admission_number);
    }

    public function test_admission_numbers_are_unique_and_sequential(): void
    {
        $a = $this->student('2000-01-01', 'Adult A');
        $b = $this->student('2000-01-01', 'Adult B');

        $this->service()->enroll($a, $this->course->id);
        $this->service()->enroll($b, $this->course->id);

        $this->assertSame('ADM/0001', $a->fresh()->admission_number);
        $this->assertSame('ADM/0002', $b->fresh()->admission_number);
    }

    public function test_a_duplicate_live_enrollment_is_blocked(): void
    {
        $student = $this->student('2000-01-01');
        $this->service()->enroll($student, $this->course->id);

        $this->expectException(DomainException::class);
        $this->service()->enroll($student, $this->course->id);
    }

    public function test_consent_is_recorded_at_enrollment(): void
    {
        $student = $this->withPrimaryGuardian($this->student('2000-01-01'));

        $enrollment = $this->service()->enroll($student, $this->course->id, consentTypes: ['data', 'communication']);

        $this->assertDatabaseHas('consent_records', ['enrollment_id' => $enrollment->id, 'consent_type' => 'data', 'granted' => true]);
        $this->assertDatabaseHas('consent_records', ['enrollment_id' => $enrollment->id, 'consent_type' => 'communication', 'granted' => true]);
    }

    public function test_withdrawing_retains_the_student_and_history(): void
    {
        $student = $this->student('2000-01-01');
        $enrollment = $this->service()->enroll($student, $this->course->id);

        $this->service()->withdraw($enrollment, 'Relocated');

        $this->assertSame(EnrollmentStatus::Withdrawn, $enrollment->fresh()->status);
        $this->assertDatabaseHas('students', ['id' => $student->id]); // student retained
        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'withdraw_reason' => 'Relocated']);
    }

    public function test_pii_visibility_depends_on_role(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $student = $this->student('2000-01-01');

        // Both are staff in the student's branch; only the role differs.
        $counsellor = $this->staffUser('Counsellor');
        $accountant = $this->staffUser('Accountant');

        $this->assertTrue($counsellor->can('view', $student));   // admission.view + branch access
        $this->assertFalse($accountant->can('view', $student));  // in branch, but no admission.view
    }
}
