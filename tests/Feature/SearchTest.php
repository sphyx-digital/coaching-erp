<?php

namespace Tests\Feature;

use App\Livewire\Search\CommandPalette;
use App\Models\Branch;
use App\Models\Institute;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\Search\SearchService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->institute = Institute::create(['name' => 'Acme']);
        $this->admin = tap(User::factory()->create())->assignRole('Institute Admin');
    }

    public function test_search_finds_students_with_deep_link(): void
    {
        $branch = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Main', 'code' => 'MN']);
        $student = Student::create(['institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'name' => 'Ravi Kumar', 'admission_number' => 'ADM-77']);

        $groups = app(SearchService::class)->search($this->admin, 'Ravi');
        $students = collect($groups)->firstWhere('type', 'Students');

        $this->assertNotNull($students);
        $this->assertSame('Ravi Kumar', $students['items'][0]['label']);
        $this->assertStringContainsString('/admissions?student='.$student->id, $students['items'][0]['url']);
    }

    public function test_short_terms_return_nothing(): void
    {
        $this->assertSame([], app(SearchService::class)->search($this->admin, 'R'));
    }

    public function test_command_palette_renders_matches(): void
    {
        $branch = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Main', 'code' => 'MN']);
        Student::create(['institute_id' => $this->institute->id, 'branch_id' => $branch->id, 'name' => 'Meera Nair', 'admission_number' => 'ADM-9']);

        Livewire::actingAs($this->admin)->test(CommandPalette::class)
            ->set('q', 'Meera')
            ->assertSee('Meera Nair')
            ->assertSee('ADM-9');
    }

    public function test_staff_deep_link_opens_drawer(): void
    {
        $member = Staff::create(['user_id' => User::factory()->create()->id, 'institute_id' => $this->institute->id, 'name' => 'Anita Rao', 'email' => 'anita@example.com']);

        $this->actingAs($this->admin)->get('/staff?view='.$member->id)
            ->assertOk()->assertSee('Anita Rao');
    }
}
