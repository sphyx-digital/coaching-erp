@php($current = $students->firstWhere('id', $studentId))
@if ($students->count() > 1)
    <x-card>
        <label class="portal-switcher">
            <span class="field__hint">Viewing</span>
            <select class="select" style="max-width: 240px;" wire:change="switchStudent($event.target.value)">
                @foreach ($students as $s)
                    <option value="{{ $s->id }}" @selected($s->id === $studentId)>{{ $s->name }}</option>
                @endforeach
            </select>
        </label>
    </x-card>
@elseif ($current)
    <div style="font-family: var(--font-heading); font-weight: 600; font-size: var(--text-lg);">{{ $current->name }}</div>
@endif
