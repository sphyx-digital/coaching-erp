<?php

namespace Tests\Feature;

use App\Livewire\Setup\SetupGuide;
use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Course;
use App\Models\FeePlan;
use App\Models\Institute;
use App\Models\Staff;
use App\Models\User;
use App\Services\Setup\SetupChecklist;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SetupChecklistTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Institute $institute;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->institute = Institute::create(['name' => 'Acme']);
        $this->admin = tap(User::factory()->create())->assignRole('Institute Admin');
    }

    public function test_fresh_instance_is_incomplete_and_nudges(): void
    {
        $checklist = app(SetupChecklist::class);
        $progress = $checklist->progress($this->admin);

        $this->assertFalse($progress['complete']);
        $this->assertSame(0, $progress['done']);
        $this->assertTrue($checklist->shouldNudge($this->admin));
    }

    public function test_completing_all_required_steps_marks_setup_complete(): void
    {
        $this->institute->update(['gstin' => '27ABACS6251R1ZS']);
        $branch = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Main', 'code' => 'MN']);
        AcademicSession::create(['institute_id' => $this->institute->id, 'name' => '2026-27', 'is_active' => true]);
        $course = Course::create(['institute_id' => $this->institute->id, 'name' => 'JEE', 'code' => 'JEE']);
        FeePlan::create(['institute_id' => $this->institute->id, 'name' => 'JEE Annual', 'course_id' => $course->id]);
        // Owner + one more staff => team step done.
        Staff::create(['user_id' => User::factory()->create()->id, 'institute_id' => $this->institute->id, 'name' => 'A']);
        Staff::create(['user_id' => User::factory()->create()->id, 'institute_id' => $this->institute->id, 'name' => 'B']);

        $progress = app(SetupChecklist::class)->progress($this->admin);
        $this->assertTrue($progress['complete']);
        $this->assertFalse(app(SetupChecklist::class)->shouldNudge($this->admin));
    }

    public function test_guide_renders_for_admin_and_is_forbidden_for_others(): void
    {
        Livewire::actingAs($this->admin)->test(SetupGuide::class)
            ->assertOk()
            ->assertSee('Get started')
            ->assertSee('Add your first centre');

        $portal = tap(User::factory()->create())->assignRole('Student');
        Livewire::actingAs($portal)->test(SetupGuide::class)->assertForbidden();
    }
}
