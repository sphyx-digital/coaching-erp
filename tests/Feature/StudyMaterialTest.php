<?php

namespace Tests\Feature;

use App\Livewire\Materials\MaterialManager;
use App\Livewire\Portal\PortalMaterials;
use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Institute;
use App\Models\Student;
use App\Models\StudyMaterial;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudyMaterialTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private Course $course;

    private Branch $branch;

    private AcademicSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->institute = Institute::create(['name' => 'Acme']);
        $this->branch = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Main', 'code' => 'MN']);
        $this->session = AcademicSession::create(['institute_id' => $this->institute->id, 'name' => '2026-27', 'is_active' => true]);
        $this->course = Course::create(['institute_id' => $this->institute->id, 'name' => 'JEE', 'code' => 'JEE']);
    }

    public function test_admin_can_create_a_material(): void
    {
        $admin = tap(User::factory()->create())->assignRole('Institute Admin');

        Livewire::actingAs($admin)->test(MaterialManager::class)
            ->call('openCreate')
            ->set('data.title', 'Kinematics notes')
            ->set('data.type', 'document')
            ->set('data.url', 'https://example.com/kinematics.pdf')
            ->set('data.course_id', $this->course->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('study_materials', ['title' => 'Kinematics notes', 'is_published' => true]);
    }

    public function test_material_requires_a_valid_url(): void
    {
        $admin = tap(User::factory()->create())->assignRole('Institute Admin');

        Livewire::actingAs($admin)->test(MaterialManager::class)
            ->call('openCreate')
            ->set('data.title', 'Bad link')
            ->set('data.url', 'not-a-url')
            ->call('save')
            ->assertHasErrors('data.url');
    }

    public function test_portal_shows_only_published_relevant_materials(): void
    {
        $studentUser = tap(User::factory()->create())->assignRole('Student');
        $student = Student::create(['institute_id' => $this->institute->id, 'user_id' => $studentUser->id, 'name' => 'Learner']);
        Enrollment::create(['institute_id' => $this->institute->id, 'branch_id' => $this->branch->id, 'academic_session_id' => $this->session->id, 'student_id' => $student->id, 'course_id' => $this->course->id]);

        $otherCourse = Course::create(['institute_id' => $this->institute->id, 'name' => 'NEET', 'code' => 'NEET']);

        $mine = StudyMaterial::create(['institute_id' => $this->institute->id, 'course_id' => $this->course->id, 'title' => 'My Course Notes', 'type' => 'document', 'url' => 'https://e.com/a', 'is_published' => true]);
        $wide = StudyMaterial::create(['institute_id' => $this->institute->id, 'title' => 'Institute Handbook', 'type' => 'note', 'url' => 'https://e.com/b', 'is_published' => true]);
        $draft = StudyMaterial::create(['institute_id' => $this->institute->id, 'course_id' => $this->course->id, 'title' => 'Hidden Draft', 'type' => 'document', 'url' => 'https://e.com/c', 'is_published' => false]);
        $other = StudyMaterial::create(['institute_id' => $this->institute->id, 'course_id' => $otherCourse->id, 'title' => 'Other Course Only', 'type' => 'document', 'url' => 'https://e.com/d', 'is_published' => true]);

        Livewire::actingAs($studentUser)->test(PortalMaterials::class)
            ->assertSee('My Course Notes')
            ->assertSee('Institute Handbook')
            ->assertDontSee('Hidden Draft')
            ->assertDontSee('Other Course Only');
    }

    public function test_back_office_requires_permission(): void
    {
        $nobody = User::factory()->create();
        Livewire::actingAs($nobody)->test(MaterialManager::class)->assertForbidden();
    }
}
