<?php

namespace Tests\Feature;

use App\Livewire\Batches\BatchManager;
use App\Livewire\Staff\StaffManager;
use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Institute;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DrawerRolloutTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->institute = Institute::create(['name' => 'Acme']);
        AcademicSession::create(['institute_id' => $this->institute->id, 'name' => '2026-27', 'is_active' => true]);
        $this->admin = tap(User::factory()->create())->assignRole('Institute Admin');
    }

    public function test_staff_row_opens_detail_drawer(): void
    {
        $member = Staff::create(['user_id' => User::factory()->create()->id, 'institute_id' => $this->institute->id, 'name' => 'Rahul Teacher', 'email' => 'rt@example.com']);

        Livewire::actingAs($this->admin)->test(StaffManager::class)
            ->assertSee('Rahul Teacher')
            ->call('view', $member->id)
            ->assertSet('viewing', true)
            ->assertSet('viewingId', $member->id);
    }

    public function test_batch_row_opens_detail_drawer(): void
    {
        $branch = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Main', 'code' => 'MN']);
        $course = Course::create(['institute_id' => $this->institute->id, 'name' => 'JEE', 'code' => 'JEE']);
        $batch = Batch::create([
            'institute_id' => $this->institute->id, 'branch_id' => $branch->id,
            'academic_session_id' => AcademicSession::first()->id, 'course_id' => $course->id,
            'name' => 'JEE Morning A', 'code' => 'JEE-A', 'capacity' => 30,
        ]);

        Livewire::actingAs($this->admin)->test(BatchManager::class)
            ->assertSee('JEE Morning A')
            ->call('view', $batch->id)
            ->assertSet('viewing', true)
            ->assertSet('viewingId', $batch->id)
            ->assertSee('JEE-A');
    }
}
