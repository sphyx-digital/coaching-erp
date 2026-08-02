<div class="container-narrow">
    <x-page-header title="Staff" />

    @if (session('ok'))<div class="alert alert--success" role="status">{{ session('ok') }}</div>@endif

    <div class="stack" style="gap: var(--space-5);">
        <x-card title="Add a staff member">
            <form wire:submit="save">
                <div class="grid-cards">
                    <x-field name="name" label="Name" wire:model="name" required />
                    <x-field name="email" label="Email" type="email" wire:model="email" required />
                    <x-select name="role" label="Role" :options="array_combine($roles, $roles)" wire:model="role" required />
                    <x-select name="branch_id" label="Branch" :options="$branches->toArray()" placeholder="No branch" wire:model="branch_id" />
                </div>
                <x-btn type="submit" variant="primary">Add staff member</x-btn>
            </form>
        </x-card>

        <x-card>
            @if ($staff->isEmpty())
                <x-state title="No staff yet">Add your first staff member above.</x-state>
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
                            <th>Name</th><th>Email</th><th>Role</th><th>Status</th><th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($staff as $member)
                        <tr wire:key="staff-{{ $member->id }}">
                            <td class="col-check"><input type="checkbox" value="{{ $member->id }}" wire:model.live="selected" aria-label="Select {{ $member->name }}"></td>
                            <td>{{ $member->name }}</td>
                            <td>{{ $member->email }}</td>
                            <td>
                                @foreach ($member->user?->getRoleNames() ?? [] as $r)
                                    <x-pill variant="info">{{ $r }}</x-pill>
                                @endforeach
                            </td>
                            <td>
                                @if ($member->is_active)
                                    <x-pill variant="success">Active</x-pill>
                                @else
                                    <x-pill variant="warning">Inactive</x-pill>
                                @endif
                            </td>
                            <td>
                                <div class="row-actions">
                                    <x-btn size="sm" variant="secondary" wire:click="view({{ $member->id }})">View</x-btn>
                                    <x-btn size="sm" variant="secondary" wire:click="toggleActive({{ $member->id }})">{{ $member->is_active ? 'Deactivate' : 'Activate' }}</x-btn>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </x-card>
    </div>

    {{-- Staff detail drawer --}}
    <x-drawer wire:model="viewing" :title="$record?->name" eyebrow="Staff member"
              :subtitle="$record?->designation ?: ($record?->user?->getRoleNames()->first() ?? null)">
        @if ($record)
            <dl class="detail-list">
                <dt>Email</dt><dd>{{ $record->email ?: '—' }}</dd>
                <dt>Phone</dt><dd>{{ $record->phone ?: '—' }}</dd>
                <dt>Employee code</dt><dd>{{ $record->employee_code ?: '—' }}</dd>
                <dt>Branch</dt><dd>{{ $record->primaryBranch?->name ?? '—' }}</dd>
                <dt>Status</dt><dd>@if($record->is_active)<x-pill variant="success">Active</x-pill>@else<x-pill variant="warning">Inactive</x-pill>@endif</dd>
            </dl>

            <div class="detail-section">
                <div class="detail-section__title">Role</div>
                <div style="display:flex; gap:4px; flex-wrap:wrap;">
                    @php($current = $record->user?->getRoleNames()->first())
                    @foreach ($roles as $r)
                        <x-btn size="sm" :variant="$current === $r ? 'primary' : 'secondary'" wire:click="changeRole({{ $record->id }}, '{{ $r }}')">{{ $r }}</x-btn>
                    @endforeach
                </div>
            </div>
        @endif

        <x-slot:footer>
            @if ($record)
                <x-btn size="sm" variant="secondary" wire:click="toggleActive({{ $record->id }})">{{ $record->is_active ? 'Deactivate' : 'Activate' }}</x-btn>
                <a class="btn btn--sm btn--secondary" href="{{ url('/staff-attendance') }}">Attendance</a>
                <a class="btn btn--sm btn--secondary" href="{{ url('/payroll') }}">Payroll</a>
            @endif
        </x-slot:footer>
    </x-drawer>
</div>
