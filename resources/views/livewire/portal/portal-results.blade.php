<div class="stack">
    @include('livewire.portal.partials.switcher')

    @if (! $student)
        <x-card><x-state title="No student linked">Nothing to show yet.</x-state></x-card>
    @else
        <x-card title="Report cards">
            @if ($cards->isEmpty())
                <x-state title="No results yet">Published report cards will appear here.</x-state>
            @else
                <x-data-table :head="['Assessment', ['label' => '%', 'num' => true], 'Grade', '']">
                    @foreach ($cards as $card)
                        <tr>
                            <td>{{ $card->assessment?->name }}</td>
                            <td class="num">{{ number_format($card->percentage_bp / 100, 1) }}</td>
                            <td><x-pill variant="success">{{ $card->overall_grade }}</x-pill></td>
                            <td><a href="{{ url('/report-cards/'.$card->assessment_id.'/'.$student->id) }}" target="_blank">View</a></td>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>
    @endif
</div>
