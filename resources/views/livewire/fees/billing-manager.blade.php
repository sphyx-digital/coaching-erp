<div class="stack" style="gap: var(--space-5);">
    <x-page-header title="Fees and payments" />

    @if (session('ok'))<div class="pill pill--success"><span class="pill__dot"></span> {{ session('ok') }}</div>@endif
    @error('pay')<div class="pill pill--danger"><span class="pill__dot"></span> {{ $message }}</div>@enderror
    @error('refund')<div class="pill pill--danger"><span class="pill__dot"></span> {{ $message }}</div>@enderror

    <div class="grid-cards">
        <x-kpi label="Total outstanding" :value="paise_to_rupees($kpiOutstanding)" />
        <x-kpi label="Collected today" :value="paise_to_rupees($kpiToday)" />
    </div>

    <x-card title="Student">
        <x-select name="studentId" label="Select a student" :options="$students->toArray()" placeholder="Choose student" wire:model.live="studentId" />
    </x-card>

    @if ($student)
        <x-card title="Raise an invoice">
            <div class="grid-cards">
                <x-select name="planId" label="Fee plan" :options="$plans->toArray()" placeholder="Select plan" wire:model="planId" />
                <label style="display:flex; align-items:center; gap: var(--space-2); align-self:end; min-height: var(--tap-min);">
                    <input type="checkbox" wire:model="interstate"> Out-of-state (IGST)
                </label>
            </div>
            <x-btn variant="primary" wire:click="raiseInvoice">Raise invoice</x-btn>
        </x-card>

        <x-card title="Invoices">
            @if ($invoices->isEmpty())
                <x-state title="No invoices">Raise an invoice from a fee plan above.</x-state>
            @else
                <x-data-table :head="['Invoice', ['label' => 'Total', 'num' => true], ['label' => 'Paid', 'num' => true], ['label' => 'Balance', 'num' => true], 'Status', 'Actions']">
                    @foreach ($invoices as $inv)
                        <tr wire:key="inv-{{ $inv->id }}">
                            <td>{{ $inv->invoice_number }}</td>
                            <td class="num">{{ paise_to_rupees($inv->total) }}</td>
                            <td class="num">{{ paise_to_rupees($inv->amount_paid) }}</td>
                            <td class="num">{{ paise_to_rupees($inv->balance) }}</td>
                            <td>
                                @php($v = $inv->status === 'paid' ? 'success' : ($inv->status === 'partial' ? 'warning' : 'info'))
                                <x-pill :variant="$v">{{ ucfirst($inv->status) }}</x-pill>
                            </td>
                            <td>
                                @if ($inv->balance > 0 && $inv->status !== 'cancelled')
                                    <x-btn size="sm" variant="primary" wire:click="$set('payInvoiceId', {{ $inv->id }})">Pay</x-btn>
                                    <x-btn size="sm" variant="secondary" wire:click="$set('discInvoiceId', {{ $inv->id }})">Discount</x-btn>
                                @endif
                                @if ($inv->amount_paid == 0 && $inv->status !== 'cancelled')
                                    <x-btn size="sm" variant="secondary" wire:click="cancelInvoice({{ $inv->id }})">Cancel</x-btn>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>

        @if ($payInvoiceId)
            <x-card title="Record payment">
                <div class="grid-cards">
                    <x-field name="payAmount" label="Amount (₹)" type="number" step="0.01" wire:model="payAmount" required />
                    <x-select name="payMode" label="Mode" :options="['cash' => 'Cash', 'cheque' => 'Cheque', 'upi' => 'UPI', 'bank' => 'Bank transfer']" wire:model="payMode" />
                    <x-field name="payReference" label="Reference" wire:model="payReference" hint="cheque no, UPI ref…" />
                </div>
                <div style="display:flex; gap: var(--space-2);">
                    <x-btn variant="primary" wire:click="pay">Record payment</x-btn>
                    <x-btn variant="secondary" wire:click="$set('payInvoiceId', null)">Cancel</x-btn>
                </div>
            </x-card>
        @endif

        @if ($discInvoiceId)
            <x-card title="Apply discount">
                <x-field name="discAmount" label="Discount (₹)" type="number" step="0.01" wire:model="discAmount" required />
                <div style="display:flex; gap: var(--space-2);">
                    <x-btn variant="primary" wire:click="applyDiscount">Apply</x-btn>
                    <x-btn variant="secondary" wire:click="$set('discInvoiceId', null)">Cancel</x-btn>
                </div>
            </x-card>
        @endif

        <x-card title="Receipts">
            @if ($payments->isEmpty())
                <x-state title="No receipts">Payments appear here with a printable receipt.</x-state>
            @else
                <x-data-table :head="['Receipt', 'Date', 'Mode', ['label' => 'Amount', 'num' => true], 'Actions']">
                    @foreach ($payments as $p)
                        <tr wire:key="pay-{{ $p->id }}">
                            <td>{{ $p->receipt_number }}</td>
                            <td>{{ $p->payment_date?->format('d-m-Y') }}</td>
                            <td>{{ ucfirst($p->mode) }}</td>
                            <td class="num">{{ paise_to_rupees($p->amount) }}</td>
                            <td>
                                <a href="{{ url('/receipts/'.$p->id) }}" target="_blank">Receipt</a>
                                @if ($p->status === 'completed')
                                    <x-btn size="sm" variant="secondary" wire:click="$set('refundPaymentId', {{ $p->id }})">Refund</x-btn>
                                    <x-btn size="sm" variant="secondary" wire:click="reversePayment({{ $p->id }})">Reverse</x-btn>
                                @else
                                    <x-pill variant="danger">Reversed</x-pill>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>

        @if ($refundPaymentId)
            <x-card title="Process refund">
                <x-field name="refundAmount" label="Refund amount (₹)" type="number" step="0.01" wire:model="refundAmount" required />
                <div style="display:flex; gap: var(--space-2);">
                    <x-btn variant="primary" wire:click="refund">Refund</x-btn>
                    <x-btn variant="secondary" wire:click="$set('refundPaymentId', null)">Cancel</x-btn>
                </div>
            </x-card>
        @endif
    @endif

    <x-card title="Outstanding (reconciliation)">
        @if ($topOutstanding->isEmpty())
            <x-state title="All clear">No outstanding balances.</x-state>
        @else
            <x-data-table :head="['Invoice', 'Student', ['label' => 'Balance', 'num' => true], '']">
                @foreach ($topOutstanding as $inv)
                    <tr wire:key="out-{{ $inv->id }}" class="is-clickable" wire:click="$set('studentId', {{ $inv->student_id }})" tabindex="0" wire:keydown.enter="$set('studentId', {{ $inv->student_id }})">
                        <td>{{ $inv->invoice_number }}</td>
                        <td>{{ $inv->student?->name }}</td>
                        <td class="num">{{ paise_to_rupees($inv->balance) }}</td>
                        <td class="row-chevron" style="text-align:right;">&rsaquo;</td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>
</div>
