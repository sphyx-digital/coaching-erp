<div class="stack">
    @include('livewire.portal.partials.switcher')

    @if (! $student)
        <x-card><x-state title="No student linked">Nothing to show yet.</x-state></x-card>
    @elseif ($slots->isEmpty())
        <x-card><x-state title="No timetable yet">Your batch timetable will appear here.</x-state></x-card>
    @else
        @foreach ($days as $num => $label)
            @if (isset($slots[$num]))
                <x-card :title="$label">
                    @foreach ($slots[$num] as $slot)
                        <div style="display:flex; justify-content:space-between; padding: var(--space-2) 0; border-bottom: 1px solid var(--border);">
                            <div>
                                <strong>{{ $slot->subject?->name ?: 'Class' }}</strong>
                                @if ($slot->teacher)<div class="field__hint">{{ $slot->teacher->name }}</div>@endif
                            </div>
                            <div class="num field__hint">{{ \Illuminate\Support\Str::of($slot->start_time)->substr(0, 5) }}–{{ \Illuminate\Support\Str::of($slot->end_time)->substr(0, 5) }}</div>
                        </div>
                    @endforeach
                </x-card>
            @endif
        @endforeach
    @endif
</div>
