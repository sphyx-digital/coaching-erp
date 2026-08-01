<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Institute;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_branch_admin_only_sees_own_branch_records(): void
    {
        $institute = Institute::create(['name' => 'Acme']);
        $branchA = Branch::create(['institute_id' => $institute->id, 'name' => 'A', 'code' => 'A']);
        $branchB = Branch::create(['institute_id' => $institute->id, 'name' => 'B', 'code' => 'B']);
        $session = AcademicSession::create(['institute_id' => $institute->id, 'name' => '2026-27', 'is_active' => true]);
        $course = Course::create(['institute_id' => $institute->id, 'name' => 'JEE', 'code' => 'JEE']);

        $enrollA = $this->enrol($institute, $branchA, $session, $course);
        $enrollB = $this->enrol($institute, $branchB, $session, $course);

        // Branch Admin assigned to branch A.
        $user = User::factory()->create();
        Staff::create(['user_id' => $user->id, 'institute_id' => $institute->id, 'branch_id' => $branchA->id, 'name' => 'Admin A']);
        $user->assignRole('Branch Admin');

        $this->actingAs($user);

        $this->assertSame(1, Enrollment::count());
        $this->assertNotNull(Enrollment::find($enrollA->id));
        $this->assertNull(Enrollment::find($enrollB->id)); // branch B is invisible
    }

    public function test_institute_admin_sees_all_branches(): void
    {
        $institute = Institute::create(['name' => 'Acme']);
        $branchA = Branch::create(['institute_id' => $institute->id, 'name' => 'A', 'code' => 'A']);
        $branchB = Branch::create(['institute_id' => $institute->id, 'name' => 'B', 'code' => 'B']);
        $session = AcademicSession::create(['institute_id' => $institute->id, 'name' => '2026-27', 'is_active' => true]);
        $course = Course::create(['institute_id' => $institute->id, 'name' => 'JEE', 'code' => 'JEE']);
        $this->enrol($institute, $branchA, $session, $course);
        $this->enrol($institute, $branchB, $session, $course);

        $user = User::factory()->create();
        Staff::create(['user_id' => $user->id, 'institute_id' => $institute->id, 'name' => 'Admin']);
        $user->assignRole('Institute Admin');

        $this->actingAs($user);

        $this->assertSame(2, Enrollment::count());
    }

    private function enrol(Institute $i, Branch $b, AcademicSession $s, Course $c): Enrollment
    {
        $student = Student::create(['institute_id' => $i->id, 'branch_id' => $b->id, 'name' => 'S'.$b->code]);

        return Enrollment::create([
            'institute_id' => $i->id, 'branch_id' => $b->id, 'student_id' => $student->id,
            'course_id' => $c->id, 'academic_session_id' => $s->id, 'status' => 'active',
        ]);
    }
}
