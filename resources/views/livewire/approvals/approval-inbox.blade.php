<div class="stack">
    <x-page-header title="Approvals" />

    @if (session('ok'))<div class="pill pill--success"><span class="pill__dot"></span> {{ session('ok') }}</div>@endif
    @error('decide')<div class="pill pill--danger"><span class="pill__dot"></span> {{ $message }}</div>@enderror

    <x-card>
        @if ($pending->isEmpty())
            <x-state title="Nothing to approve">You're all caught up.</x-state>
        @else
            <x-data-table :head="['Request', 'Requested by', ['label' => 'Amount', 'num' => true], 'Raised', 'Actions']">
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
                            <x-btn size="sm" variant="primary" wire:click="approve({{ $a->id }})">Approve</x-btn>
                            <x-btn size="sm" variant="secondary" wire:click="$set('rejectId', {{ $a->id }})">Reject</x-btn>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    @if ($rejectId)
        <x-card title="Reject request">
            <x-field name="reason" label="Reason" wire:model="reason" />
            <div style="display:flex; gap: var(--space-2);">
                <x-btn variant="primary" wire:click="confirmReject">Confirm reject</x-btn>
                <x-btn variant="secondary" wire:click="$set('rejectId', null)">Cancel</x-btn>
            </div>
        </x-card>
    @endif
</div>
