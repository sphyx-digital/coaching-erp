<div class="stack">
    <x-page-header title="Override log" />
    <x-card>
        <p class="field__hint" style="margin-bottom: var(--space-3);">Every controlled exception — reversals, voids, cancellations, refunds, mark edits and approval decisions — with who, when and why.</p>
        <div class="toolbar">
            <input class="input" type="search" placeholder="Search action / type…" wire:model.live.debounce.300ms="search">
        </div>
        @if ($entries->isEmpty())
            <x-state title="No overrides yet">Controlled exceptions will be logged here.</x-state>
        @else
            <x-data-table :head="['When', 'Action', 'Record', 'By', 'Detail']">
                @foreach ($entries as $e)
                    <tr wire:key="ov-{{ $e->id }}">
                        <td class="num">{{ $e->created_at?->format('d-m-Y H:i') }}</td>
                        <td><x-pill variant="warning">{{ $e->action }}</x-pill></td>
                        <td>{{ class_basename($e->auditable_type) }} #{{ $e->auditable_id }}</td>
                        <td>{{ $e->user?->name ?: 'System' }}</td>
                        <td class="field__hint">{{ \Illuminate\Support\Str::limit(json_encode($e->after), 60) }}</td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>
</div>
