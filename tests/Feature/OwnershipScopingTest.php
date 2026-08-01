<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Guardian;
use App\Models\Institute;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnershipScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_parent_sees_only_linked_students(): void
    {
        $institute = Institute::create(['name' => 'Acme']);
        $branch = Branch::create(['institute_id' => $institute->id, 'name' => 'A', 'code' => 'A']);

        $mine = Student::create(['institute_id' => $institute->id, 'branch_id' => $branch->id, 'name' => 'Mine']);
        $other = Student::create(['institute_id' => $institute->id, 'branch_id' => $branch->id, 'name' => 'Other']);

        $parentUser = User::factory()->create();
        $guardian = Guardian::create(['institute_id' => $institute->id, 'user_id' => $parentUser->id, 'name' => 'Parent']);
        $guardian->students()->attach($mine->id, ['is_primary' => true]);
        $parentUser->assignRole('Parent');

        $this->actingAs($parentUser);

        $this->assertTrue($parentUser->can('view', $mine));
        $this->assertFalse($parentUser->can('view', $other));
    }

    public function test_student_sees_own_record_only_and_no_staff(): void
    {
        $institute = Institute::create(['name' => 'Acme']);
        $branch = Branch::create(['institute_id' => $institute->id, 'name' => 'A', 'code' => 'A']);

        $studentUser = User::factory()->create();
        $me = Student::create(['institute_id' => $institute->id, 'branch_id' => $branch->id, 'user_id' => $studentUser->id, 'name' => 'Me']);
        $classmate = Student::create(['institute_id' => $institute->id, 'branch_id' => $branch->id, 'name' => 'Classmate']);
        $studentUser->assignRole('Student');

        // A staff member exists.
        $staffUser = User::factory()->create();
        $staff = Staff::create(['user_id' => $staffUser->id, 'institute_id' => $institute->id, 'branch_id' => $branch->id, 'name' => 'Teacher']);

        $this->actingAs($studentUser);

        $this->assertTrue($studentUser->can('view', $me));
        $this->assertFalse($studentUser->can('view', $classmate));
        $this->assertFalse($studentUser->can('view', $staff)); // cannot read staff PII
    }
}
