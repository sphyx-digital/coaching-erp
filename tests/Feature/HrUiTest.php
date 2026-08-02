<?php

namespace Tests\Feature;

use App\Livewire\Hr\PayrollManager;
use App\Livewire\Hr\StaffAttendanceRegister;
use App\Models\Institute;
use App\Models\Payslip;
use App\Models\SalaryStructure;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HrUiTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private User $admin;

    private Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->institute = Institute::create(['name' => 'Acme']);
        $this->admin = tap(User::factory()->create())->assignRole('Institute Admin');
        $this->staff = Staff::create(['user_id' => User::factory()->create()->id, 'institute_id' => $this->institute->id, 'name' => 'Teacher A', 'is_active' => true]);
    }

    public function test_admin_can_mark_staff_attendance(): void
    {
        Livewire::actingAs($this->admin)->test(StaffAttendanceRegister::class)
            ->set('date', '2026-04-10')
            ->call('mark', $this->staff->id, 'absent');

        $this->assertDatabaseHas('staff_attendance', [
            'staff_id' => $this->staff->id, 'date' => '2026-04-10', 'status' => 'absent',
        ]);
    }

    public function test_admin_can_set_salary_and_generate_a_payslip(): void
    {
        Livewire::actingAs($this->admin)->test(PayrollManager::class)
            ->set('month', '2026-04-01')
            ->call('editStructure', $this->staff->id)
            ->set('grossRupees', '30000')
            ->call('saveStructure')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('salary_structures', ['staff_id' => $this->staff->id, 'monthly_gross' => 3000000, 'is_active' => true]);

        Livewire::actingAs($this->admin)->test(PayrollManager::class)
            ->set('month', '2026-04-01')
            ->call('generate', $this->staff->id);

        $payslip = Payslip::where('staff_id', $this->staff->id)->first();
        $this->assertNotNull($payslip);
        $this->assertSame(3000000, $payslip->net); // no unpaid days => full gross
    }

    public function test_payslip_document_is_visible_to_admin_and_owner_only(): void
    {
        SalaryStructure::create(['institute_id' => $this->institute->id, 'staff_id' => $this->staff->id, 'effective_from' => '2026-04-01', 'monthly_gross' => 3000000, 'earnings' => [['name' => 'Basic', 'amount' => 3000000]], 'is_active' => true]);
        $payslip = Payslip::create(['institute_id' => $this->institute->id, 'staff_id' => $this->staff->id, 'month' => '2026-04-01', 'days_in_month' => 30, 'gross' => 3000000, 'net' => 3000000, 'earnings' => [['name' => 'Basic', 'amount' => 3000000]], 'status' => 'finalized']);

        // Admin can view.
        $this->actingAs($this->admin)->get(route('payslips.show', $payslip->id))->assertOk()->assertSee('Net payable');

        // The staff member themselves can view.
        $this->actingAs($this->staff->user)->get(route('payslips.show', $payslip->id))->assertOk();

        // An unrelated user cannot.
        $stranger = User::factory()->create();
        $this->actingAs($stranger)->get(route('payslips.show', $payslip->id))->assertForbidden();
    }

    public function test_hr_screens_require_admin(): void
    {
        $nobody = User::factory()->create();
        Livewire::actingAs($nobody)->test(StaffAttendanceRegister::class)->assertForbidden();
        Livewire::actingAs($nobody)->test(PayrollManager::class)->assertForbidden();
    }
}
