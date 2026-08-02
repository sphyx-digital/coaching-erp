<div class="stack">
    <x-page-header title="Import & cutover">
        <x-slot:actions>
            <x-btn size="sm" variant="secondary" href="{{ url('/import/template/students') }}">Download template</x-btn>
        </x-slot:actions>
    </x-page-header>

    @if (session('ok'))<div class="pill pill--success"><span class="pill__dot"></span> {{ session('ok') }}</div>@endif
    @error('import')<div class="pill pill--danger"><span class="pill__dot"></span> {{ $message }}</div>@enderror

    <x-card title="Import students">
        <p class="field__hint" style="margin-bottom: var(--space-3);">Paste CSV (with a header row: {{ implode(', ', \App\Services\Import\ImportService::STUDENT_COLUMNS) }}). Validate to preview, then commit — no partial imports.</p>
        <x-field name="label" label="Batch label" wire:model="label" />
        <label class="field"><span class="field__label">CSV data</span>
            <textarea class="textarea" style="min-height:160px; font-family:monospace;" wire:model="csv" placeholder="name,phone,email,guardian_name,guardian_phone,opening_balance&#10;Riya Sharma,9990001111,riya@example.com,Papa,9990002222,5000"></textarea>
        </label>
        <div style="display:flex; gap: var(--space-2);">
            <x-btn variant="secondary" wire:click="analyse">Validate &amp; preview</x-btn>
            @if (!empty($preview) && empty($preview['errors']) && ($preview['total'] ?? 0) > 0)
                <x-btn variant="primary" wire:click="commit">Commit {{ $preview['total'] }} rows</x-btn>
            @endif
        </div>

        @if (!empty($preview))
            <div style="margin-top: var(--space-3);">
                <x-pill variant="info">{{ count($preview['valid']) }} valid</x-pill>
                @if (!empty($preview['errors']))<x-pill variant="danger">{{ count($preview['errors']) }} with errors</x-pill>@endif
            </div>
            @if (!empty($preview['errors']))
                <x-data-table :head="['Row', 'Error']">
                    @foreach ($preview['errors'] as $line => $msg)
                        <tr><td class="num">{{ $line }}</td><td>{{ $msg }}</td></tr>
                    @endforeach
                </x-data-table>
            @endif
        @endif
    </x-card>

    <x-card title="Import batches">
        @if ($batches->isEmpty())
            <x-state title="No imports yet">Import your first batch above.</x-state>
        @else
            <x-data-table :head="['Label', 'Type', ['label' => 'Rows', 'num' => true], ['label' => 'Imported', 'num' => true], 'Status', '']">
                @foreach ($batches as $b)
                    <tr wire:key="batch-{{ $b->id }}">
                        <td>{{ $b->label }}</td>
                        <td>{{ $b->type }}</td>
                        <td class="num">{{ $b->total_rows }}</td>
                        <td class="num">{{ $b->imported_count }}</td>
                        <td>@if ($b->status === 'committed')<x-pill variant="success">Committed</x-pill>@else<x-pill variant="warning">Rolled back</x-pill>@endif</td>
                        <td>@if ($b->status === 'committed')<x-btn size="sm" variant="secondary" wire:click="rollback({{ $b->id }})">Rollback</x-btn>@endif</td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>
</div>
