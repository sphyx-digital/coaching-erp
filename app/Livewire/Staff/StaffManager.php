<?php

namespace App\Livewire\Staff;

use App\Livewire\Concerns\WithBulkSelect;
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
    use WithBulkSelect;

    /** Roles a staff member may hold (not portal or platform roles). */
    public const STAFF_ROLES = ['Institute Admin', 'Branch Admin', 'Counsellor', 'Teacher', 'Accountant'];

    public string $name = '';

    public string $email = '';

    public string $role = 'Teacher';

    public ?int $branch_id = null;

    public bool $viewing = false;

    public ?int $viewingId = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('settings.update'), 403);
    }

    public function view(int $id): void
    {
        $this->viewingId = $id;
        $this->viewing = true;
    }

    public function updatedViewing(bool $value): void
    {
        if (! $value) {
            $this->viewingId = null;
        }
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

    public function bulkSetActive(bool $active): void
    {
        abort_unless(Auth::user()?->can('settings.update'), 403);
        Staff::whereIn('id', $this->selectedIds())->update(['is_active' => $active]);
        $count = $this->selectedCount();
        $this->clearSelection();
        session()->flash('ok', ($active ? 'Activated ' : 'Deactivated ').$count.' staff.');
    }

    public function render()
    {
        $staff = Staff::with('user')->orderBy('name')->get();
        $this->pageIds = $staff->pluck('id')->all();

        return view('livewire.staff.staff-manager', [
            'staff' => $staff,
            'branches' => Branch::orderBy('name')->pluck('name', 'id'),
            'roles' => self::STAFF_ROLES,
            'record' => $this->viewingId ? Staff::with(['user', 'primaryBranch'])->find($this->viewingId) : null,
        ]);
    }
}
