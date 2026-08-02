<div class="stack">
    @include('livewire.portal.partials.switcher')

    @if (! $student)
        <x-card><x-state title="No student linked">Nothing to show yet.</x-state></x-card>
    @else
        <x-card title="Outstanding">
            <div class="num" style="font-size: var(--text-3xl); font-family: var(--font-heading);">{{ paise_to_rupees($due) }}</div>
            @if ($canPay)
                <x-btn variant="primary" class="mt">Pay now</x-btn>
            @else
                <p class="field__hint" style="margin-top: var(--space-2);">Online payment opens soon. Pay at the institute desk for now.</p>
                <button class="btn btn--primary" disabled style="margin-top: var(--space-3); opacity:.5; cursor:not-allowed;">Pay now (coming soon)</button>
            @endif
        </x-card>

        <x-card title="Invoices">
            @if ($invoices->isEmpty())
                <x-state title="No invoices">Your fee invoices will appear here.</x-state>
            @else
                <x-data-table :head="['Invoice', ['label' => 'Total', 'num' => true], ['label' => 'Balance', 'num' => true], 'Status', '']">
                    @foreach ($invoices as $inv)
                        <tr>
                            <td>{{ $inv->invoice_number }}</td>
                            <td class="num">{{ paise_to_rupees($inv->total) }}</td>
                            <td class="num">{{ paise_to_rupees($inv->balance) }}</td>
                            <td>@php($v = $inv->status === 'paid' ? 'success' : ($inv->status === 'partial' ? 'warning' : 'info'))<x-pill :variant="$v">{{ ucfirst($inv->status) }}</x-pill></td>
                            <td>@if ($canPay && $inv->balance > 0)<a class="btn btn--sm btn--primary" href="{{ url('/portal/pay/'.$inv->id) }}">Pay</a>@endif</td>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>

        <x-card title="Receipts">
            @if ($payments->isEmpty())
                <x-state title="No receipts">Payment receipts will appear here.</x-state>
            @else
                <x-data-table :head="['Receipt', 'Date', ['label' => 'Amount', 'num' => true], '']">
                    @foreach ($payments as $p)
                        <tr>
                            <td>{{ $p->receipt_number }}</td>
                            <td>{{ $p->payment_date?->format('d-m-Y') }}</td>
                            <td class="num">{{ paise_to_rupees($p->amount) }}</td>
                            <td><a href="{{ url('/receipts/'.$p->id) }}" target="_blank">View</a></td>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>
    @endif
</div>
