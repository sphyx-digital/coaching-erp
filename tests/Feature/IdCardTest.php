<?php

namespace Tests\Feature;

use App\Livewire\IdCards\IdCardManager;
use App\Models\Branch;
use App\Models\Institute;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IdCardTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $institute = Institute::create(['name' => 'Acme']);
        $branch = Branch::create(['institute_id' => $institute->id, 'name' => 'Main', 'code' => 'MN']);
        Student::create(['institute_id' => $institute->id, 'branch_id' => $branch->id, 'name' => 'Aarav Sharma', 'admission_number' => 'ADM-001', 'is_active' => true]);
        Student::create(['institute_id' => $institute->id, 'branch_id' => $branch->id, 'name' => 'Diya Patel', 'admission_number' => 'ADM-002', 'is_active' => false]);

        // Institute Admin: sees all branches (bypasses branch scope) and can view admissions.
        $this->staff = tap(User::factory()->create())->assignRole('Institute Admin');
    }

    public function test_manager_counts_only_active_students(): void
    {
        Livewire::actingAs($this->staff)->test(IdCardManager::class)
            ->assertViewHas('count', 1);
    }

    public function test_sheet_renders_active_student_cards_for_staff(): void
    {
        $this->actingAs($this->staff)->get('/id-cards/sheet')
            ->assertOk()
            ->assertSee('Aarav Sharma')
            ->assertSee('ADM-001')
            ->assertDontSee('Diya Patel');
    }

    public function test_sheet_is_forbidden_without_permission(): void
    {
        $nobody = User::factory()->create();
        $this->actingAs($nobody)->get('/id-cards/sheet')->assertForbidden();
    }
}
