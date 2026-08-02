<div class="stack">
    @include('livewire.portal.partials.switcher')

    @if (! $student)
        <x-card><x-state title="No student linked">Nothing to show yet.</x-state></x-card>
    @else
        <x-card title="Study materials">
            @if ($materials->isEmpty())
                <x-state title="No materials yet">Notes, videos and documents shared by your teachers will appear here.</x-state>
            @else
                <x-data-table :head="['Title', 'Type', 'Subject', '']">
                    @foreach ($materials as $m)
                        <tr wire:key="pm-{{ $m->id }}">
                            <td><b>{{ $m->title }}</b>@if($m->description)<div class="field__hint">{{ \Illuminate\Support\Str::limit($m->description, 80) }}</div>@endif</td>
                            <td><x-pill variant="info">{{ $m->typeLabel() }}</x-pill></td>
                            <td>{{ $m->subject?->name ?? '—' }}</td>
                            <td style="text-align:right;"><a class="btn btn--sm btn--primary" href="{{ $m->url }}" target="_blank" rel="noopener">Open</a></td>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>
    @endif
</div>
