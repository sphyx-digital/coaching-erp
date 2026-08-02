<div class="stack">
    <x-page-header title="Message delivery" />
    @if (session('ok'))<div class="pill pill--success"><span class="pill__dot"></span> {{ session('ok') }}</div>@endif
    <x-card>
        <p class="field__hint" style="margin-bottom: var(--space-3);">Queued, skipped and failed messages across channels. Retry a failed message once the cause is resolved.</p>
        @if ($messages->isEmpty())
            <x-state title="All clear">No pending or failed messages.</x-state>
        @else
            <x-data-table :head="['When', 'Channel', 'To', 'Status', 'Reason', '']">
                @foreach ($messages as $m)
                    <tr wire:key="msg-{{ $m->id }}">
                        <td class="num">{{ $m->created_at?->format('d-m-Y H:i') }}</td>
                        <td>{{ ucfirst($m->channel) }}</td>
                        <td>{{ $m->recipient ?: '—' }}</td>
                        <td>
                            @php($v = $m->status === 'failed' ? 'danger' : ($m->status === 'skipped' ? 'warning' : 'info'))
                            <x-pill :variant="$v">{{ ucfirst($m->status) }}</x-pill>
                        </td>
                        <td class="field__hint">{{ $m->error ?: '—' }}</td>
                        <td>@if ($m->status === 'failed')<x-btn size="sm" variant="secondary" wire:click="retry({{ $m->id }})">Retry</x-btn>@endif</td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>
</div>
