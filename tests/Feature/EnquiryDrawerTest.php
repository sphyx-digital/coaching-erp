<?php

namespace Tests\Feature;

use App\Livewire\Enquiries\EnquiryManager;
use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Institute;
use App\Models\User;
use App\Services\Enquiries\EnquiryService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EnquiryDrawerTest extends TestCase
{
    use RefreshDatabase;

    public function test_clicking_a_row_opens_the_detail_drawer_and_actions_work(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $institute = Institute::create(['name' => 'Acme']);
        AcademicSession::create(['institute_id' => $institute->id, 'name' => '2026-27', 'is_active' => true]);
        $branch = Branch::create(['institute_id' => $institute->id, 'name' => 'Main', 'code' => 'MN']);
        $course = Course::create(['institute_id' => $institute->id, 'name' => 'JEE', 'code' => 'JEE']);
        $admin = tap(User::factory()->create())->assignRole('Institute Admin');

        $enquiry = app(EnquiryService::class)->create([
            'institute_id' => $institute->id, 'branch_id' => $branch->id,
            'academic_session_id' => AcademicSession::first()->id, 'course_id' => $course->id,
            'name' => 'Aarav Sharma', 'phone' => '9876543210',
        ]);

        Livewire::actingAs($admin)->test(EnquiryManager::class)
            ->assertSet('viewing', false)
            ->call('view', $enquiry->id)
            ->assertSet('viewing', true)
            ->assertSet('viewingId', $enquiry->id)
            ->assertSee('Aarav Sharma')
            ->assertSee('9876543210')
            // an action from inside the drawer still works
            ->call('setStatus', $enquiry->id, 'contacted');

        $this->assertSame('contacted', $enquiry->fresh()->status->value);
    }

    public function test_closing_the_drawer_clears_state(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $institute = Institute::create(['name' => 'Acme']);
        AcademicSession::create(['institute_id' => $institute->id, 'name' => '2026-27', 'is_active' => true]);
        $branch = Branch::create(['institute_id' => $institute->id, 'name' => 'Main', 'code' => 'MN']);
        $admin = tap(User::factory()->create())->assignRole('Institute Admin');
        $enquiry = app(EnquiryService::class)->create([
            'institute_id' => $institute->id, 'branch_id' => $branch->id,
            'academic_session_id' => AcademicSession::first()->id, 'name' => 'Diya', 'phone' => '9000000000',
        ]);

        Livewire::actingAs($admin)->test(EnquiryManager::class)
            ->call('view', $enquiry->id)
            ->set('viewing', false) // triggers updatedViewing
            ->assertSet('viewingId', null);
    }
}
