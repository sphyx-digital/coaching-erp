<div class="stack" style="gap: var(--space-5);">
    <x-page-header title="Attendance" />

    @if (session('ok'))<div class="pill pill--success"><span class="pill__dot"></span> {{ session('ok') }}</div>@endif
    @error('finalize')<div class="pill pill--danger"><span class="pill__dot"></span> {{ $message }}</div>@enderror

    <x-card>
        <div class="grid-cards">
            <x-select name="batchId" label="Batch" :options="$batches->toArray()" placeholder="Select batch" wire:model.live="batchId" />
            <x-field name="date" label="Date" type="date" wire:model.live="date" />
        </div>
    </x-card>

    @if ($batchId && $students->isNotEmpty())
        <div class="grid-cards">
            <x-kpi label="Present" :value="$present.' / '.$total" />
            <x-kpi label="Attendance" :value="number_format($percentBp / 100, 1).'%'" />
        </div>

        <x-card>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: var(--space-4); flex-wrap:wrap; gap: var(--space-2);">
                <div style="display:flex; gap: var(--space-2);">
                    <x-btn size="sm" variant="secondary" wire:click="markAll('present')">All present</x-btn>
                    <x-btn size="sm" variant="secondary" wire:click="markAll('absent')">All absent</x-btn>
                </div>
                @if ($finalised)<x-pill variant="info">Finalised</x-pill>@endif
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Student</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach ($students as $student)
                        <tr wire:key="att-{{ $student->id }}">
                            <td>
                                {{ $student->name }}
                                @if (array_key_exists($student->id, $low))
                                    <x-pill variant="danger">Low {{ number_format($low[$student->id] / 100, 0) }}%</x-pill>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; gap: var(--space-1); flex-wrap:wrap;">
                                    @foreach ($statuses as $s)
                                        @php($active = ($marks[$student->id] ?? 'present') === $s->value)
                                        <x-btn size="sm" :variant="$active ? 'primary' : 'secondary'" wire:click="setMark({{ $student->id }}, '{{ $s->value }}')">{{ $s->label() }}</x-btn>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div style="display:flex; gap: var(--space-2); margin-top: var(--space-4);">
                <x-btn variant="primary" wire:click="save">Save attendance</x-btn>
                @unless ($finalised)<x-btn variant="secondary" wire:click="finalize">Finalise</x-btn>@endunless
            </div>
        </x-card>
    @elseif ($batchId)
        <x-card><x-state title="No students in this batch">Assign students to the batch first.</x-state></x-card>
    @else
        <x-card><x-state title="Select a batch">Choose a batch and date to mark attendance.</x-state></x-card>
    @endif
</div>
