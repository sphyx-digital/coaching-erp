<div class="stack">
    @include('livewire.portal.partials.switcher')

    @if (! $student)
        <x-card><x-state title="No student linked">Nothing to show yet.</x-state></x-card>
    @else
        <x-card>
            <div style="display:flex; align-items:center; gap: var(--space-5); flex-wrap:wrap;">
                <div class="ring" style="--pct: {{ (int) round($summary['percent_bp'] / 100) }};">
                    <div class="ring__inner num">{{ (int) round($summary['percent_bp'] / 100) }}%</div>
                </div>
                <div>
                    <div class="field__hint">Attended</div>
                    <div class="num" style="font-size: var(--text-xl); font-family: var(--font-heading);">{{ $summary['present'] }} / {{ $summary['total'] }}</div>
                </div>
            </div>
        </x-card>

        <x-card title="History">
            @if ($history->isEmpty())
                <x-state title="No attendance yet">Marked attendance will appear here.</x-state>
            @else
                <x-data-table :head="['Date', 'Status']">
                    @foreach ($history as $r)
                        <tr>
                            <td class="num">{{ \Illuminate\Support\Carbon::parse($r->session_date)->format('d-m-Y') }}</td>
                            <td>
                                @php($s = \App\Enums\AttendanceStatus::from($r->status))
                                <x-pill :variant="$s->pillVariant()">{{ $s->label() }}</x-pill>
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>
    @endif
</div>
