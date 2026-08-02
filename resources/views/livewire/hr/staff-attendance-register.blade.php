<div class="stack">
    <x-page-header title="Staff attendance" />

    <x-card>
        <div class="toolbar" style="margin-bottom:var(--space-3);align-items:flex-end;gap:var(--space-3);">
            <div class="field" style="max-width:200px;">
                <label class="field__label" for="d">Date</label>
                <input id="d" type="date" class="input" wire:model.live="date">
            </div>
        </div>

        @php($labels = ['present'=>'Present','absent'=>'Absent','half_day'=>'Half day','leave'=>'Leave','holiday'=>'Holiday','week_off'=>'Week off'])
        @php($variant = ['present'=>'success','absent'=>'danger','half_day'=>'warning','leave'=>'info','holiday'=>'muted','week_off'=>'muted'])

        @if ($staff->isEmpty())
            <x-state title="No staff yet">Add staff members to mark their attendance.</x-state>
        @else
            <x-data-table :head="['Staff', 'Designation', 'Status', 'Mark']">
                @foreach ($staff as $s)
                    <tr wire:key="sa-{{ $s->id }}">
                        <td><b>{{ $s->name }}</b>@if($s->employee_code)<div class="field__hint">{{ $s->employee_code }}</div>@endif</td>
                        <td>{{ $s->designation ?: '—' }}</td>
                        <td>
                            @if ($marks[$s->id])
                                <x-pill variant="{{ $variant[$marks[$s->id]] ?? 'muted' }}">{{ $labels[$marks[$s->id]] ?? $marks[$s->id] }}</x-pill>
                            @else
                                <span class="field__hint">Not marked</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                @foreach ($statuses as $st)
                                    <button class="btn btn--sm {{ $marks[$s->id] === $st ? 'btn--primary' : '' }}"
                                            wire:click="mark({{ $s->id }}, '{{ $st }}')">{{ $labels[$st] }}</button>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>
</div>
