<?php

namespace Tests\Feature;

use App\Livewire\Admissions\AdmissionsManager;
use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Institute;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdmissionsDrawerTest extends TestCase
{
    use RefreshDatabase;

    public function test_clicking_a_student_row_opens_the_profile_drawer(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $institute = Institute::create(['name' => 'Acme']);
        $branch = Branch::create(['institute_id' => $institute->id, 'name' => 'Main', 'code' => 'MN']);
        $session = AcademicSession::create(['institute_id' => $institute->id, 'name' => '2026-27', 'is_active' => true]);
        $course = Course::create(['institute_id' => $institute->id, 'name' => 'JEE', 'code' => 'JEE']);
        $admin = tap(User::factory()->create())->assignRole('Institute Admin');

        $student = Student::create(['institute_id' => $institute->id, 'branch_id' => $branch->id, 'name' => 'Aarav Sharma', 'admission_number' => 'ADM-1']);
        Enrollment::create(['institute_id' => $institute->id, 'branch_id' => $branch->id, 'academic_session_id' => $session->id, 'student_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);

        Livewire::actingAs($admin)->test(AdmissionsManager::class)
            ->assertSet('viewing', false)
            ->assertSee('Aarav Sharma')
            ->call('viewProfile', $student->id)
            ->assertSet('viewing', true)
            ->assertSet('profileId', $student->id)
            ->assertSee('ADM-1')
            ->set('viewing', false)
            ->assertSet('profileId', null);
    }
}
