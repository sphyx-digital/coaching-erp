<div class="container-narrow">
    <x-page-header title="Branches" />

    <div class="stack" style="grid-template-columns: 1fr; gap: var(--space-5);">
        <x-card title="{{ $editingId ? 'Edit branch' : 'Add a branch' }}">
            <form wire:submit="save">
                <div class="grid-cards">
                    <x-field name="name" label="Name" wire:model="name" required />
                    <x-field name="code" label="Code" wire:model="code" required hint="Short unique code, e.g. MN" />
                    <x-field name="city" label="City" wire:model="city" />
                </div>
                <div style="display:flex; gap: var(--space-2); margin-top: var(--space-2);">
                    <x-btn type="submit" variant="primary">{{ $editingId ? 'Update branch' : 'Add branch' }}</x-btn>
                    @if ($editingId)
                        <x-btn type="button" variant="secondary" wire:click="resetForm">Cancel</x-btn>
                    @endif
                </div>
            </form>
        </x-card>

        <x-card>
            @if ($branches->isEmpty())
                <x-state title="No branches yet">Add your first branch above to start scoping records.</x-state>
            @else
                <x-data-table :head="['Name', 'Code', 'City', 'Status', 'Actions']">
                    @foreach ($branches as $branch)
                        <tr wire:key="branch-{{ $branch->id }}">
                            <td>{{ $branch->name }}</td>
                            <td>{{ $branch->code }}</td>
                            <td>{{ $branch->city ?: '—' }}</td>
                            <td>
                                @if ($branch->is_active)
                                    <x-pill variant="success">Active</x-pill>
                                @else
                                    <x-pill variant="warning">Inactive</x-pill>
                                @endif
                            </td>
                            <td>
                                <x-btn size="sm" variant="secondary" wire:click="edit({{ $branch->id }})">Edit</x-btn>
                                <x-btn size="sm" variant="secondary" wire:click="toggleActive({{ $branch->id }})">
                                    {{ $branch->is_active ? 'Deactivate' : 'Activate' }}
                                </x-btn>
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>
    </div>
</div>
