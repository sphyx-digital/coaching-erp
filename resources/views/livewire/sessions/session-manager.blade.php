<div class="container-narrow">
    <x-page-header title="Academic sessions" />

    <div class="stack" style="gap: var(--space-5);">
        <x-card title="Add a session">
            <form wire:submit="save">
                <div class="grid-cards">
                    <x-field name="name" label="Name" wire:model="name" required hint="e.g. 2026-27" />
                    <x-field name="starts_on" label="Starts on" type="date" wire:model="starts_on" />
                    <x-field name="ends_on" label="Ends on" type="date" wire:model="ends_on" />
                </div>
                <x-btn type="submit" variant="primary">Add session</x-btn>
            </form>
        </x-card>

        <x-card>
            @if ($sessions->isEmpty())
                <x-state title="No sessions yet">Add a session to scope batches, fees and results.</x-state>
            @else
                <x-data-table :head="['Name', 'Starts', 'Ends', 'Status', '']">
                    @foreach ($sessions as $session)
                        <tr wire:key="session-{{ $session->id }}" class="is-clickable" wire:click="view({{ $session->id }})" tabindex="0" wire:keydown.enter="view({{ $session->id }})">
                            <td>{{ $session->name }}</td>
                            <td>{{ $session->starts_on?->format('d-m-Y') ?: '—' }}</td>
                            <td>{{ $session->ends_on?->format('d-m-Y') ?: '—' }}</td>
                            <td>
                                @if ($session->is_active)
                                    <x-pill variant="success">Active</x-pill>
                                @else
                                    <x-pill variant="info">Inactive</x-pill>
                                @endif
                            </td>
                            <td class="row-chevron" style="text-align:right;">&rsaquo;</td>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>
    </div>

    {{-- Session detail drawer --}}
    <x-drawer wire:model="viewing" :title="$record?->name" eyebrow="Academic session"
              :subtitle="$record?->is_active ? 'Active session' : 'Inactive'">
        @if ($record)
            <dl class="detail-list">
                <dt>Starts</dt><dd>{{ $record->starts_on?->format('d-m-Y') ?: '—' }}</dd>
                <dt>Ends</dt><dd>{{ $record->ends_on?->format('d-m-Y') ?: '—' }}</dd>
                <dt>Status</dt><dd>@if($record->is_active)<x-pill variant="success">Active</x-pill>@else<x-pill variant="info">Inactive</x-pill>@endif</dd>
            </dl>
        @endif
        <x-slot:footer>
            @if ($record && ! $record->is_active)
                <x-btn size="sm" variant="primary" wire:click="markActive({{ $record->id }})">Set as active session</x-btn>
            @elseif ($record)
                <span class="field__hint">This is the active session.</span>
            @endif
        </x-slot:footer>
    </x-drawer>
</div>
