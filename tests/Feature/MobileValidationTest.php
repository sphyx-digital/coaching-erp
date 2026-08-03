<?php

namespace Tests\Feature;

use App\Livewire\Enquiries\EnquiryManager;
use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Institute;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MobileValidationTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $institute = Institute::create(['name' => 'Acme']);
        AcademicSession::create(['institute_id' => $institute->id, 'name' => '2026-27', 'is_active' => true]);
        $this->branch = Branch::create(['institute_id' => $institute->id, 'name' => 'Main', 'code' => 'MN']);
        $this->admin = tap(User::factory()->create())->assignRole('Institute Admin');
    }

    public function test_rejects_mobile_not_starting_6_to_9(): void
    {
        Livewire::actingAs($this->admin)->test(EnquiryManager::class)
            ->set('name', 'Test')
            ->set('branch_id', $this->branch->id)
            ->set('phone', '1234567890') // starts with 1
            ->call('create')
            ->assertHasErrors('phone');
    }

    public function test_rejects_mobile_shorter_than_10_digits(): void
    {
        Livewire::actingAs($this->admin)->test(EnquiryManager::class)
            ->set('name', 'Test')
            ->set('branch_id', $this->branch->id)
            ->set('phone', '98765') // too short
            ->call('create')
            ->assertHasErrors('phone');
    }

    public function test_accepts_valid_indian_mobile(): void
    {
        Livewire::actingAs($this->admin)->test(EnquiryManager::class)
            ->set('name', 'Test')
            ->set('branch_id', $this->branch->id)
            ->set('phone', '9876543210')
            ->call('create')
            ->assertHasNoErrors('phone');

        $this->assertDatabaseHas('enquiries', ['phone' => '9876543210']);
    }
}
