<div class="container-narrow">
    <x-page-header title="Staff" />

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
                <x-data-table :head="['Name', 'Email', 'Role', 'Status', 'Actions']">
                    @foreach ($staff as $member)
                        <tr wire:key="staff-{{ $member->id }}">
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
                                <x-btn size="sm" variant="secondary" wire:click="toggleActive({{ $member->id }})">
                                    {{ $member->is_active ? 'Deactivate' : 'Activate' }}
                                </x-btn>
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>
    </div>
</div>
