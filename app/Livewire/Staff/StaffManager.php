<?php

namespace App\Livewire\Staff;

use App\Models\Branch;
use App\Models\Staff;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class StaffManager extends Component
{
    /** Roles a staff member may hold (not portal or platform roles). */
    public const STAFF_ROLES = ['Institute Admin', 'Branch Admin', 'Counsellor', 'Teacher', 'Accountant'];

    public string $name = '';

    public string $email = '';

    public string $role = 'Teacher';

    public ?int $branch_id = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('settings.update'), 403);
    }

    public function save(AuditLogger $audit): void
    {
        abort_unless(Auth::user()?->can('settings.update'), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', 'in:'.implode(',', self::STAFF_ROLES)],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        DB::transaction(function () use ($data, $audit) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(Str::password(16)),
            ]);
            $user->assignRole($data['role']);

            $staff = Staff::create([
                'user_id' => $user->id,
                'institute_id' => current_institute()?->id,
                'branch_id' => $data['branch_id'],
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            if ($data['branch_id']) {
                $staff->branches()->syncWithoutDetaching([$data['branch_id']]);
            }

            $audit->log('staff.created', $staff, after: ['role' => $data['role'], 'email' => $data['email']]);
        });

        $this->reset(['name', 'email', 'role', 'branch_id']);
        $this->role = 'Teacher';
    }

    public function changeRole(int $staffId, string $role, AuditLogger $audit): void
    {
        abort_unless(Auth::user()?->can('settings.update'), 403);
        abort_unless(in_array($role, self::STAFF_ROLES, true), 422);

        $staff = Staff::with('user')->findOrFail($staffId);
        $before = $staff->user->getRoleNames()->all();
        $staff->user->syncRoles([$role]);

        $audit->log('role.changed', $staff, before: ['roles' => $before], after: ['roles' => [$role]]);
    }

    public function toggleActive(int $staffId): void
    {
        abort_unless(Auth::user()?->can('settings.update'), 403);
        $staff = Staff::findOrFail($staffId);
        $staff->update(['is_active' => ! $staff->is_active]);
    }

    public function render()
    {
        return view('livewire.staff.staff-manager', [
            'staff' => Staff::with('user')->orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->pluck('name', 'id'),
            'roles' => self::STAFF_ROLES,
        ]);
    }
}
