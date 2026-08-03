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
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class StaffManager extends Component
{
    use WithBulkSelect;

    /** Roles a staff member may hold (not portal or platform roles). */
    public const STAFF_ROLES = ['Institute Admin', 'Branch Admin', 'Counsellor', 'Teacher', 'Accountant'];

    public const EMPLOYMENT_TYPES = ['full_time' => 'Full-time', 'part_time' => 'Part-time', 'visiting' => 'Visiting', 'contract' => 'Contract'];

    public const GENDERS = ['Male', 'Female', 'Other'];

    public const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];

    // Create / edit modal
    public bool $showModal = false;

    public ?int $editingId = null;

    /** @var array<string,mixed> */
    public array $data = [];

    // Detail drawer
    public bool $viewing = false;

    public ?int $viewingId = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('settings.update'), 403);
        if (($id = (int) request()->integer('view')) && Staff::whereKey($id)->exists()) {
            $this->view($id);
        }
    }

    private function blank(): array
    {
        return [
            'title' => '', 'first_name' => '', 'middle_name' => '', 'last_name' => '',
            'email' => '', 'dial_code' => '+91', 'phone' => '', 'alt_phone' => '',
            'role' => 'Teacher', 'employee_code' => '', 'designation' => '', 'department' => '',
            'employment_type' => 'full_time', 'joining_date' => null, 'qualification' => '', 'specialization' => '',
            'dob' => null, 'gender' => '', 'blood_group' => '', 'photo' => '',
            'address' => '', 'city' => '', 'state' => '', 'pincode' => '',
            'emergency_name' => '', 'emergency_phone' => '', 'pan' => '',
            'branch_id' => null, 'is_active' => true,
        ];
    }

    public function openCreate(): void
    {
        abort_unless(Auth::user()?->can('settings.update'), 403);
        $this->resetValidation();
        $this->editingId = null;
        $this->data = $this->blank();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(Auth::user()?->can('settings.update'), 403);
        $s = Staff::with('user')->findOrFail($id);
        $this->editingId = $id;
        $this->data = array_merge($this->blank(), collect($s->only(array_keys($this->blank())))->all());
        $this->data['role'] = $s->user?->getRoleNames()->first() ?? 'Teacher';
        $this->data['joining_date'] = optional($s->joining_date)->toDateString();
        $this->data['dob'] = optional($s->dob)->toDateString();
        $this->viewing = false;
        $this->showModal = true;
    }

    public function save(AuditLogger $audit): void
    {
        abort_unless(Auth::user()?->can('settings.update'), 403);

        $userId = $this->editingId ? Staff::find($this->editingId)?->user_id : null;

        $rules = [
            'data.first_name' => ['required', 'string', 'max:120'],
            'data.last_name' => ['nullable', 'string', 'max:120'],
            'data.email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'data.role' => ['required', 'in:'.implode(',', self::STAFF_ROLES)],
            'data.phone' => ['nullable', 'regex:/^[6-9][0-9]{9}$/'],
            'data.alt_phone' => ['nullable', 'regex:/^[6-9][0-9]{9}$/'],
            'data.emergency_phone' => ['nullable', 'regex:/^[6-9][0-9]{9}$/'],
            'data.employment_type' => ['nullable', 'in:'.implode(',', array_keys(self::EMPLOYMENT_TYPES))],
            'data.dob' => ['nullable', 'date', 'before:today'],
            'data.joining_date' => ['nullable', 'date'],
            'data.branch_id' => ['nullable', 'exists:branches,id'],
            'data.pincode' => ['nullable', 'regex:/^[0-9]{6}$/'],
        ];
        $this->validate($rules);

        $d = $this->data;
        $name = trim(collect([$d['first_name'], $d['middle_name'], $d['last_name']])->filter()->implode(' '));
        $fields = collect($d)->except(['role'])->merge(['name' => $name])->all();
        $fields['joining_date'] = $d['joining_date'] ?: null;
        $fields['dob'] = $d['dob'] ?: null;
        $fields['branch_id'] = $d['branch_id'] ?: null;

        DB::transaction(function () use ($fields, $d, $name, $audit) {
            if ($this->editingId) {
                $staff = Staff::findOrFail($this->editingId);
                $staff->update($fields);
                if ($staff->user) {
                    $staff->user->update(['name' => $name, 'email' => $d['email']]);
                    $staff->user->syncRoles([$d['role']]);
                }
                $audit->log('staff.updated', $staff, after: ['role' => $d['role']]);
            } else {
                $user = User::create(['name' => $name, 'email' => $d['email'], 'password' => Hash::make(Str::password(16))]);
                $user->assignRole($d['role']);
                $staff = Staff::create($fields + ['user_id' => $user->id, 'institute_id' => current_institute()?->id]);
                if ($d['branch_id']) {
                    $staff->branches()->syncWithoutDetaching([$d['branch_id']]);
                }
                $audit->log('staff.created', $staff, after: ['role' => $d['role'], 'email' => $d['email']]);
            }
        });

        $this->showModal = false;
        session()->flash('ok', 'Staff member saved.');
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
