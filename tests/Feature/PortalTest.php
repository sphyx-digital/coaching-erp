<?php

namespace Tests\Feature;

use App\Livewire\Portal\PortalFees;
use App\Livewire\Portal\PortalHome;
use App\Models\Branch;
use App\Models\Guardian;
use App\Models\Institute;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\User;
use App\Support\Portal\PortalAccess;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    private Student $mine;

    private Student $other;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $institute = Institute::create(['name' => 'Acme']);
        $branch = Branch::create(['institute_id' => $institute->id, 'name' => 'Main', 'code' => 'MN']);
        $this->mine = Student::create(['institute_id' => $institute->id, 'branch_id' => $branch->id, 'name' => 'My Child']);
        $this->other = Student::create(['institute_id' => $institute->id, 'branch_id' => $branch->id, 'name' => 'Other Child']);

        $this->parent = tap(User::factory()->create())->assignRole('Parent');
        $guardian = Guardian::create(['institute_id' => $institute->id, 'user_id' => $this->parent->id, 'name' => 'Parent']);
        $guardian->students()->attach($this->mine->id, ['is_primary' => true]);
    }

    public function test_parent_can_only_access_linked_students(): void
    {
        $access = app(PortalAccess::class);

        $this->assertTrue($access->students($this->parent)->contains('id', $this->mine->id));
        $this->assertFalse($access->students($this->parent)->contains('id', $this->other->id));

        $this->expectException(HttpException::class);
        $access->authorize($this->parent, $this->other->id); // 403 for an unlinked student
    }

    public function test_portal_home_defaults_to_linked_student(): void
    {
        Livewire::actingAs($this->parent)->test(PortalHome::class)
            ->assertSet('studentId', $this->mine->id)
            ->assertSee('My Child');
    }

    public function test_switching_to_an_unlinked_student_is_ignored(): void
    {
        Livewire::actingAs($this->parent)->test(PortalHome::class)
            ->call('switchStudent', $this->other->id)
            ->assertSet('studentId', $this->mine->id); // stays on the linked child
    }

    public function test_a_non_portal_user_cannot_open_the_portal(): void
    {
        $teacher = tap(User::factory()->create())->assignRole('Teacher');

        $this->actingAs($teacher)->get('/portal')->assertForbidden();
    }

    public function test_fees_view_is_read_only_for_the_owner(): void
    {
        Invoice::withoutGlobalScopes()->create([
            'institute_id' => $this->mine->institute_id, 'branch_id' => $this->mine->branch_id,
            'student_id' => $this->mine->id, 'invoice_number' => 'INV/0001', 'invoice_date' => '2026-08-01',
            'total' => 500000, 'balance' => 500000, 'status' => 'issued',
        ]);

        Livewire::actingAs($this->parent)->test(PortalFees::class)
            ->assertSee('INV/0001')
            ->assertSee('coming soon'); // pay-now disabled until the gateway (Phase 14)

        // The component exposes no mutating actions.
        $this->assertFalse(method_exists(PortalFees::class, 'pay'));
    }

    public function test_pwa_manifest_and_service_worker_exist_and_are_valid(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));

        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertArrayHasKey('icons', $manifest);
        $this->assertSame('/', $manifest['start_url']);
    }
}
