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
                    <tr wire:key="msg-{{ $m->id }}" class="is-clickable" wire:click="view({{ $m->id }})" tabindex="0" wire:keydown.enter="view({{ $m->id }})">
                        <td class="num">{{ $m->created_at?->format('d-m-Y H:i') }}</td>
                        <td>{{ ucfirst($m->channel) }}</td>
                        <td>{{ $m->recipient ?: '—' }}</td>
                        <td>
                            @php($v = $m->status === 'failed' ? 'danger' : ($m->status === 'skipped' ? 'warning' : 'info'))
                            <x-pill :variant="$v">{{ ucfirst($m->status) }}</x-pill>
                        </td>
                        <td class="field__hint">{{ \Illuminate\Support\Str::limit($m->error, 40) ?: '—' }}</td>
                        <td>
                            <div class="row-actions">
                                @if ($m->status === 'failed')<x-btn size="sm" variant="secondary" wire:click.stop="retry({{ $m->id }})">Retry</x-btn>@else<span class="field__hint">—</span>@endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    {{-- Message detail drawer --}}
    <x-drawer wire:model="viewing" :title="$record ? ucfirst($record->channel).' message' : null" eyebrow="Message delivery"
              :subtitle="$record?->recipient">
        @if ($record)
            <dl class="detail-list">
                <dt>When</dt><dd>{{ $record->created_at?->format('d-m-Y H:i') }}</dd>
                <dt>Channel</dt><dd>{{ ucfirst($record->channel) }}</dd>
                <dt>Recipient</dt><dd>{{ $record->recipient ?: '—' }}</dd>
                <dt>Template</dt><dd>{{ $record->template_key ?: '—' }}</dd>
                <dt>Status</dt><dd>
                    @php($v = $record->status === 'failed' ? 'danger' : ($record->status === 'skipped' ? 'warning' : 'info'))
                    <x-pill :variant="$v">{{ ucfirst($record->status) }}</x-pill>
                </dd>
                @if ($record->sent_at)<dt>Sent at</dt><dd>{{ $record->sent_at->format('d-m-Y H:i') }}</dd>@endif
                @if ($record->provider_ref)<dt>Provider ref</dt><dd>{{ $record->provider_ref }}</dd>@endif
            </dl>
            @if ($record->subject)
                <div class="detail-section"><div class="detail-section__title">Subject</div><p style="margin:0;font-size:var(--text-sm);">{{ $record->subject }}</p></div>
            @endif
            @if ($record->body)
                <div class="detail-section"><div class="detail-section__title">Body</div><p style="margin:0;font-size:var(--text-sm);white-space:pre-wrap;">{{ $record->body }}</p></div>
            @endif
            @if ($record->error)
                <div class="detail-section"><div class="detail-section__title">Error</div><p style="margin:0;font-size:var(--text-sm);color:var(--danger);white-space:pre-wrap;">{{ $record->error }}</p></div>
            @endif
        @endif
        <x-slot:footer>
            @if ($record && $record->status === 'failed')
                <x-btn size="sm" variant="primary" wire:click="retry({{ $record->id }})">Retry delivery</x-btn>
            @endif
        </x-slot:footer>
    </x-drawer>
</div>
