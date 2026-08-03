<?php

namespace Tests\Feature;

use App\Livewire\Staff\StaffManager;
use App\Models\Branch;
use App\Models\Institute;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StaffProfileTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private User $admin;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->institute = Institute::create(['name' => 'Acme']);
        $this->branch = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Main', 'code' => 'MN']);
        $this->admin = tap(User::factory()->create())->assignRole('Institute Admin');
    }

    public function test_create_staff_with_full_profile(): void
    {
        Livewire::actingAs($this->admin)->test(StaffManager::class)
            ->call('openCreate')
            ->set('data.first_name', 'Priya')
            ->set('data.last_name', 'Sharma')
            ->set('data.email', 'priya@example.com')
            ->set('data.role', 'Teacher')
            ->set('data.phone', '9876543210')
            ->set('data.designation', 'Senior Physics Faculty')
            ->set('data.employment_type', 'full_time')
            ->set('data.joining_date', '2026-04-01')
            ->set('data.qualification', 'M.Sc, B.Ed')
            ->set('data.blood_group', 'B+')
            ->set('data.branch_id', $this->branch->id)
            ->set('data.emergency_phone', '9000000001')
            ->call('save')
            ->assertHasNoErrors();

        $staff = Staff::where('email', 'priya@example.com')->first();
        $this->assertNotNull($staff);
        $this->assertSame('Priya Sharma', $staff->name);
        $this->assertSame('Senior Physics Faculty', $staff->designation);
        $this->assertSame('B+', $staff->blood_group);
        $this->assertTrue($staff->user->hasRole('Teacher'));
    }

    public function test_edit_staff_updates_name_and_role(): void
    {
        $user = tap(User::factory()->create(['email' => 'old@example.com']))->assignRole('Teacher');
        $staff = Staff::create(['user_id' => $user->id, 'institute_id' => $this->institute->id, 'name' => 'Old Name', 'email' => 'old@example.com', 'first_name' => 'Old', 'last_name' => 'Name']);

        Livewire::actingAs($this->admin)->test(StaffManager::class)
            ->call('openEdit', $staff->id)
            ->set('data.first_name', 'New')
            ->set('data.last_name', 'Name')
            ->set('data.role', 'Accountant')
            ->call('save')
            ->assertHasNoErrors();

        $staff->refresh();
        $this->assertSame('New Name', $staff->name);
        $this->assertTrue($staff->user->fresh()->hasRole('Accountant'));
    }

    public function test_staff_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        Livewire::actingAs($this->admin)->test(StaffManager::class)
            ->call('openCreate')
            ->set('data.first_name', 'X')
            ->set('data.email', 'taken@example.com')
            ->set('data.role', 'Teacher')
            ->call('save')
            ->assertHasErrors('data.email');
    }
}
