<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        return tap(User::factory()->create())->assignRole($role);
    }

    public function test_accountant_cannot_write_assessments_but_can_write_fees(): void
    {
        $accountant = $this->userWithRole('Accountant');

        $this->assertTrue($accountant->can('fee.create'));
        $this->assertFalse($accountant->can('assessment.create'));
        $this->assertFalse($accountant->can('assessment.update'));
    }

    public function test_teacher_cannot_touch_fees_but_can_mark_attendance(): void
    {
        $teacher = $this->userWithRole('Teacher');

        $this->assertTrue($teacher->can('attendance.create'));
        $this->assertTrue($teacher->can('assessment.update'));
        $this->assertFalse($teacher->can('fee.create'));
        $this->assertFalse($teacher->can('fee.view'));
    }

    public function test_counsellor_scope_is_enquiry_and_admission(): void
    {
        $counsellor = $this->userWithRole('Counsellor');

        $this->assertTrue($counsellor->can('enquiry.create'));
        $this->assertTrue($counsellor->can('admission.create'));
        $this->assertFalse($counsellor->can('fee.create'));
        $this->assertFalse($counsellor->can('attendance.create'));
    }

    public function test_platform_admin_passes_every_gate(): void
    {
        $admin = $this->userWithRole('Platform Admin');

        $this->assertTrue($admin->can('fee.approve'));
        $this->assertTrue($admin->can('settings.update'));
        $this->assertTrue($admin->can('any.undefined.ability')); // super-admin via Gate::before
    }

    public function test_institute_admin_has_all_module_permissions(): void
    {
        $admin = $this->userWithRole('Institute Admin');

        $this->assertTrue($admin->can('settings.update'));
        $this->assertTrue($admin->can('assessment.approve'));
        $this->assertTrue($admin->can('fee.delete'));
    }

    public function test_branch_admin_cannot_change_settings(): void
    {
        $branchAdmin = $this->userWithRole('Branch Admin');

        $this->assertTrue($branchAdmin->can('settings.view'));
        $this->assertFalse($branchAdmin->can('settings.update'));
        $this->assertTrue($branchAdmin->can('fee.create'));
    }
}
