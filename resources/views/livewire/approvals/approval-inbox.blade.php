<div class="stack">
    <x-page-header title="Approvals" />

    @if (session('ok'))<div class="pill pill--success"><span class="pill__dot"></span> {{ session('ok') }}</div>@endif
    @error('decide')<div class="pill pill--danger"><span class="pill__dot"></span> {{ $message }}</div>@enderror

    <x-card>
        @if ($pending->isEmpty())
            <x-state title="Nothing to approve">You're all caught up.</x-state>
        @else
            <x-data-table :head="['Request', 'Requested by', ['label' => 'Amount', 'num' => true], 'Raised', '']">
                @foreach ($pending as $a)
                    <tr wire:key="appr-{{ $a->id }}">
                        <td>
                            {{ $a->title }}
                            @if ($a->escalated_at)<x-pill variant="warning">Escalated</x-pill>@endif
                        </td>
                        <td>{{ $a->requester?->name ?: '—' }}</td>
                        <td class="num">{{ $a->amount ? paise_to_rupees($a->amount) : '—' }}</td>
                        <td>{{ $a->requested_at?->diffForHumans() }}</td>
                        <td>
                            <div class="row-actions">
                                <x-btn size="sm" variant="secondary" wire:click="view({{ $a->id }})">Review</x-btn>
                                <x-btn size="sm" variant="primary" wire:click="approve({{ $a->id }})">Approve</x-btn>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    {{-- Approval detail drawer --}}
    <x-drawer wire:model="viewing" :title="$record?->title" eyebrow="Approval request"
              :subtitle="$record?->action">
        @if ($record)
            <dl class="detail-list">
                <dt>Action</dt><dd>{{ $record->action }}</dd>
                <dt>Requested by</dt><dd>{{ $record->requester?->name ?: '—' }}</dd>
                <dt>Amount</dt><dd class="num">{{ $record->amount ? paise_to_rupees($record->amount) : '—' }}</dd>
                <dt>Raised</dt><dd>{{ $record->requested_at?->format('d-m-Y H:i') }} <span class="field__hint">({{ $record->requested_at?->diffForHumans() }})</span></dd>
                <dt>Approver role</dt><dd>{{ $record->approver_role }}</dd>
                @if ($record->due_at)<dt>SLA due</dt><dd>{{ $record->due_at->format('d-m-Y H:i') }}</dd>@endif
                @if ($record->escalated_at)<dt>Escalated</dt><dd><x-pill variant="warning">to {{ $record->escalated_to }}</x-pill></dd>@endif
            </dl>
            @if (! empty($record->meta))
                <div class="detail-section">
                    <div class="detail-section__title">Details</div>
                    <dl class="detail-list">
                        @foreach ($record->meta as $k => $v)
                            <dt>{{ ucfirst(str_replace('_', ' ', $k)) }}</dt><dd>{{ is_scalar($v) ? $v : json_encode($v) }}</dd>
                        @endforeach
                    </dl>
                </div>
            @endif

            @if ($rejectId)
                <div class="detail-section">
                    <div class="detail-section__title">Reject request</div>
                    <x-field name="reason" label="Reason" wire:model="reason" />
                    <div style="display:flex; gap: var(--space-2); margin-top: var(--space-2);">
                        <x-btn variant="primary" wire:click="confirmReject">Confirm reject</x-btn>
                        <x-btn variant="secondary" wire:click="$set('rejectId', null)">Cancel</x-btn>
                    </div>
                </div>
            @endif
        @endif
        <x-slot:footer>
            @if ($record)
                <x-btn size="sm" variant="primary" wire:click="approve({{ $record->id }})">Approve</x-btn>
                <x-btn size="sm" variant="secondary" wire:click="$set('rejectId', {{ $record->id }})">Reject</x-btn>
            @endif
        </x-slot:footer>
    </x-drawer>
</div>
