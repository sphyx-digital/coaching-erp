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
            <x-data-table :head="['When', 'Action', 'Record', 'By', 'Detail', '']">
                @foreach ($entries as $e)
                    <tr wire:key="ov-{{ $e->id }}">
                        <td class="num">{{ $e->created_at?->format('d-m-Y H:i') }}</td>
                        <td><x-pill variant="warning">{{ $e->action }}</x-pill></td>
                        <td>{{ class_basename($e->auditable_type) }} #{{ $e->auditable_id }}</td>
                        <td>{{ $e->user?->name ?: 'System' }}</td>
                        <td class="field__hint">{{ \Illuminate\Support\Str::limit(json_encode($e->after), 50) }}</td>
                        <td><div class="row-actions"><x-btn size="sm" variant="secondary" wire:click="view({{ $e->id }})">View</x-btn></div></td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    {{-- Override / audit detail drawer --}}
    <x-drawer wire:model="viewing" :title="$record?->action" eyebrow="Override / audit entry"
              :subtitle="$record ? class_basename($record->auditable_type).' #'.$record->auditable_id : null" wide>
        @if ($record)
            <dl class="detail-list">
                <dt>When</dt><dd>{{ $record->created_at?->format('d-m-Y H:i:s') }}</dd>
                <dt>Action</dt><dd><x-pill variant="warning">{{ $record->action }}</x-pill></dd>
                <dt>Record</dt><dd>{{ class_basename($record->auditable_type) }} #{{ $record->auditable_id }}</dd>
                <dt>By</dt><dd>{{ $record->user?->name ?: 'System' }}</dd>
            </dl>
            @if (! empty($record->before))
                <div class="detail-section">
                    <div class="detail-section__title">Before</div>
                    <pre style="margin:0;font-size:12px;white-space:pre-wrap;word-break:break-word;background:var(--surface-sunken);padding:10px 12px;border-radius:8px;">{{ json_encode($record->before, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
            @if (! empty($record->after))
                <div class="detail-section">
                    <div class="detail-section__title">After</div>
                    <pre style="margin:0;font-size:12px;white-space:pre-wrap;word-break:break-word;background:var(--surface-sunken);padding:10px 12px;border-radius:8px;">{{ json_encode($record->after, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        @endif
    </x-drawer>
</div>
