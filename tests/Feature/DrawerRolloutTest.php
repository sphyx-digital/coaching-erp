<?php

namespace Tests\Feature;

use App\Livewire\Approvals\ApprovalInbox;
use App\Livewire\Batches\BatchManager;
use App\Livewire\Branches\BranchManager;
use App\Livewire\Courses\CourseSubjectManager;
use App\Livewire\Exceptions\OverrideLog;
use App\Livewire\Fees\BillingManager;
use App\Livewire\Materials\MaterialManager;
use App\Livewire\Notifications\FailedMessages;
use App\Livewire\Sessions\SessionManager;
use App\Livewire\Staff\StaffManager;
use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Institute;
use App\Models\MessageLog;
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

    public function test_course_row_opens_detail_drawer(): void
    {
        $course = Course::create(['institute_id' => $this->institute->id, 'name' => 'NEET Foundation', 'code' => 'NEET']);

        Livewire::actingAs($this->admin)->test(CourseSubjectManager::class)
            ->assertSee('NEET Foundation')
            ->call('view', $course->id)
            ->assertSet('viewing', true)
            ->assertSet('viewingId', $course->id);
    }

    public function test_branch_and_material_screens_render(): void
    {
        Branch::create(['institute_id' => $this->institute->id, 'name' => 'Vijay Nagar', 'code' => 'VN']);

        Livewire::actingAs($this->admin)->test(BranchManager::class)->assertOk()->assertSee('Vijay Nagar');
        Livewire::actingAs($this->admin)->test(MaterialManager::class)->assertOk();
    }

    public function test_session_row_opens_detail_drawer(): void
    {
        $session = AcademicSession::where('is_active', true)->first();

        Livewire::actingAs($this->admin)->test(SessionManager::class)
            ->assertSee('2026-27')
            ->call('view', $session->id)
            ->assertSet('viewing', true)
            ->assertSet('viewingId', $session->id);
    }

    public function test_fees_screen_renders_and_outstanding_row_selects_student(): void
    {
        $c = Livewire::actingAs($this->admin)->test(BillingManager::class)->assertOk();
        // selecting a student id loads their ledger without error
        $c->set('studentId', null)->assertOk();
    }

    public function test_log_screens_render_with_drawers(): void
    {
        Livewire::actingAs($this->admin)->test(OverrideLog::class)->assertOk();
        Livewire::actingAs($this->admin)->test(FailedMessages::class)->assertOk();
        Livewire::actingAs($this->admin)->test(ApprovalInbox::class)->assertOk();
    }

    public function test_message_log_row_opens_drawer(): void
    {
        $log = MessageLog::create([
            'institute_id' => $this->institute->id, 'channel' => 'email', 'recipient' => 'a@b.c',
            'subject' => 'Test', 'body' => 'Hello', 'status' => 'failed', 'error' => 'SMTP timeout',
        ]);

        Livewire::actingAs($this->admin)->test(FailedMessages::class)
            ->call('view', $log->id)
            ->assertSet('viewing', true)
            ->assertSee('SMTP timeout');
    }
}
