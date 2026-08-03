<div class="stack">
    <x-page-header title="Staff">
        <x-slot:actions>
            <button class="btn btn--primary" wire:click="openCreate">New staff member</button>
        </x-slot:actions>
    </x-page-header>

    @if (session('ok'))<div class="alert alert--success" role="status">{{ session('ok') }}</div>@endif

    <x-card>
        @if ($staff->isEmpty())
            <x-state title="No staff yet">Add your first staff member.</x-state>
        @else
            @if ($this->selectedCount())
                <div class="bulkbar">
                    <span class="bulkbar__count">{{ $this->selectedCount() }} selected</span>
                    <x-btn size="sm" variant="secondary" wire:click="bulkSetActive(true)">Activate</x-btn>
                    <x-btn size="sm" variant="secondary" wire:click="bulkSetActive(false)">Deactivate</x-btn>
                    <span class="bulkbar__spacer"></span>
                    <x-btn size="sm" variant="secondary" wire:click="clearSelection">Clear</x-btn>
                </div>
            @endif
            <table class="table">
                <thead>
                    <tr>
                        <th class="col-check"><input type="checkbox" aria-label="Select all"
                            @change="$event.target.checked ? $wire.selectAllVisible() : $wire.clearSelection()"
                            @checked($this->selectedCount() && $this->selectedCount() === count($staff))></th>
                        <th>Name</th><th>Designation</th><th>Email</th><th>Role</th><th>Status</th><th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($staff as $member)
                    <tr wire:key="staff-{{ $member->id }}" class="is-clickable" wire:click="view({{ $member->id }})" tabindex="0" wire:keydown.enter="view({{ $member->id }})">
                        <td class="col-check" @click.stop><input type="checkbox" value="{{ $member->id }}" wire:model.live="selected" @click.stop aria-label="Select {{ $member->name }}"></td>
                        <td><b>{{ $member->name }}</b>@if($member->employee_code)<div class="field__hint">{{ $member->employee_code }}</div>@endif</td>
                        <td>{{ $member->designation ?: '—' }}</td>
                        <td>{{ $member->email }}</td>
                        <td>@foreach ($member->user?->getRoleNames() ?? [] as $r)<x-pill variant="info">{{ $r }}</x-pill>@endforeach</td>
                        <td>@if ($member->is_active)<x-pill variant="success">Active</x-pill>@else<x-pill variant="warning">Inactive</x-pill>@endif</td>
                        <td>
                            <div class="row-actions">
                                <x-btn size="sm" variant="secondary" wire:click.stop="openEdit({{ $member->id }})">Edit</x-btn>
                                <x-btn size="sm" variant="secondary" wire:click.stop="toggleActive({{ $member->id }})">{{ $member->is_active ? 'Deactivate' : 'Activate' }}</x-btn>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </x-card>

    {{-- Create / edit modal --}}
    <x-modal wire:model="showModal" title="{{ $editingId ? 'Edit staff member' : 'New staff member' }}" wide>
        <div class="form-section__title">Identity</div>
        <div class="form-grid form-grid--2">
            <x-select name="data.title" label="Title" :options="['Mr'=>'Mr','Ms'=>'Ms','Mrs'=>'Mrs','Dr'=>'Dr','Prof'=>'Prof']" placeholder="—" wire:model="data.title" />
            <x-field name="data.first_name" label="First name" wire:model="data.first_name" required />
            <x-field name="data.middle_name" label="Middle name" wire:model="data.middle_name" />
            <x-field name="data.last_name" label="Last name" wire:model="data.last_name" />
        </div>

        <div class="form-section__title">Contact</div>
        <div class="form-grid form-grid--3">
            <x-field name="data.email" label="Email" type="email" wire:model="data.email" required />
            <div class="phone-row">
                <select class="select" wire:model="data.dial_code" aria-label="Dial code">
                    @foreach (['+91','+1','+44','+971','+61','+65'] as $dc)<option value="{{ $dc }}">{{ $dc }}</option>@endforeach
                </select>
                <x-field name="data.phone" label="Mobile" wire:model="data.phone" mobile />
            </div>
            <x-field name="data.alt_phone" label="Alternate phone" wire:model="data.alt_phone" mobile />
        </div>

        <div class="form-section__title">Employment</div>
        <div class="form-grid form-grid--3">
            <x-select name="data.role" label="System role" :options="collect(\App\Livewire\Staff\StaffManager::STAFF_ROLES)->mapWithKeys(fn($r)=>[$r=>$r])->all()" wire:model="data.role" required />
            <x-field name="data.designation" label="Designation" wire:model="data.designation" hint="e.g. Senior Physics Faculty" />
            <x-field name="data.department" label="Department" wire:model="data.department" />
            <x-select name="data.employment_type" label="Employment type" :options="\App\Livewire\Staff\StaffManager::EMPLOYMENT_TYPES" wire:model="data.employment_type" />
            <x-field name="data.employee_code" label="Employee code" wire:model="data.employee_code" />
            <x-field name="data.joining_date" label="Joining date" type="date" wire:model="data.joining_date" />
            <x-field name="data.qualification" label="Qualification" wire:model="data.qualification" hint="M.Sc, B.Ed…" />
            <x-field name="data.specialization" label="Subjects / expertise" wire:model="data.specialization" />
            <x-select name="data.branch_id" label="Primary branch" :options="$branches->toArray()" placeholder="No branch" wire:model="data.branch_id" />
        </div>

        <div class="form-section__title">Personal</div>
        <div class="form-grid form-grid--3">
            <x-field name="data.dob" label="Date of birth" type="date" wire:model="data.dob" />
            <x-select name="data.gender" label="Gender" :options="collect(\App\Livewire\Staff\StaffManager::GENDERS)->mapWithKeys(fn($g)=>[$g=>$g])->all()" placeholder="—" wire:model="data.gender" />
            <x-select name="data.blood_group" label="Blood group" :options="collect(\App\Livewire\Staff\StaffManager::BLOOD_GROUPS)->mapWithKeys(fn($b)=>[$b=>$b])->all()" placeholder="—" wire:model="data.blood_group" />
            <x-field name="data.pan" label="PAN" wire:model="data.pan" />
            <x-field name="data.photo" label="Photo URL" wire:model="data.photo" />
        </div>

        <div class="form-section__title">Address &amp; emergency contact</div>
        <div class="form-grid form-grid--2">
            <x-field name="data.address" label="Address" wire:model="data.address" />
            <x-field name="data.city" label="City" wire:model="data.city" />
            <x-field name="data.state" label="State" wire:model="data.state" />
            <x-field name="data.pincode" label="Pincode" wire:model="data.pincode" numeric maxlength="6" />
            <x-field name="data.emergency_name" label="Emergency contact name" wire:model="data.emergency_name" />
            <x-field name="data.emergency_phone" label="Emergency contact phone" wire:model="data.emergency_phone" mobile />
        </div>

        <x-slot:footer>
            <button class="btn" wire:click="$set('showModal', false)">Cancel</button>
            <button class="btn btn--primary" wire:click="save">Save staff member</button>
        </x-slot:footer>
    </x-modal>

    {{-- Detail drawer --}}
    <x-drawer wire:model="viewing" :title="$record?->name" eyebrow="Staff member"
              :subtitle="$record?->designation ?: ($record?->user?->getRoleNames()->first() ?? null)" wide>
        @if ($record)
            <dl class="detail-list">
                <dt>Role</dt><dd>{{ $record->user?->getRoleNames()->first() ?? '—' }}</dd>
                <dt>Employment</dt><dd>{{ \App\Livewire\Staff\StaffManager::EMPLOYMENT_TYPES[$record->employment_type] ?? '—' }}@if($record->department) · {{ $record->department }}@endif</dd>
                <dt>Email</dt><dd>{{ $record->email ?: '—' }}</dd>
                <dt>Phone</dt><dd>{{ $record->phone ? ($record->dial_code.' '.$record->phone) : '—' }}</dd>
                <dt>Employee code</dt><dd>{{ $record->employee_code ?: '—' }}</dd>
                <dt>Branch</dt><dd>{{ $record->primaryBranch?->name ?? '—' }}</dd>
                <dt>Joined</dt><dd>{{ $record->joining_date?->format('d-m-Y') ?: '—' }}</dd>
                <dt>Qualification</dt><dd>{{ $record->qualification ?: '—' }}@if($record->specialization) · {{ $record->specialization }}@endif</dd>
                <dt>Status</dt><dd>@if($record->is_active)<x-pill variant="success">Active</x-pill>@else<x-pill variant="warning">Inactive</x-pill>@endif</dd>
            </dl>

            <div class="detail-section">
                <div class="detail-section__title">Personal</div>
                <dl class="detail-list">
                    <dt>Date of birth</dt><dd>{{ $record->dob?->format('d-m-Y') ?: '—' }}@if($record->age()) ({{ $record->age() }} yrs)@endif</dd>
                    <dt>Gender</dt><dd>{{ $record->gender ?: '—' }}</dd>
                    <dt>Blood group</dt><dd>{{ $record->blood_group ?: '—' }}</dd>
                    <dt>Address</dt><dd>{{ collect([$record->address, $record->city, $record->state, $record->pincode])->filter()->implode(', ') ?: '—' }}</dd>
                    <dt>Emergency</dt><dd>{{ $record->emergency_name ?: '—' }}@if($record->emergency_phone) · {{ $record->emergency_phone }}@endif</dd>
                </dl>
            </div>
        @endif

        <x-slot:footer>
            @if ($record)
                <x-btn size="sm" variant="primary" wire:click="openEdit({{ $record->id }})">Edit</x-btn>
                <x-btn size="sm" variant="secondary" wire:click="toggleActive({{ $record->id }})">{{ $record->is_active ? 'Deactivate' : 'Activate' }}</x-btn>
                <a class="btn btn--sm btn--secondary" href="{{ url('/payroll') }}">Payroll</a>
            @endif
        </x-slot:footer>
    </x-drawer>
</div>
