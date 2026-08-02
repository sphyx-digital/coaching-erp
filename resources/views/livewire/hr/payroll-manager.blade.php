<div class="stack">
    <x-page-header title="Payroll" />

    @if (session('payroll_saved'))<div class="alert alert--success" role="status">Salary structure saved.</div>@endif
    @error('payroll')<div class="alert alert--danger" role="alert">{{ $message }}</div>@enderror

    <x-card>
        <div class="toolbar" style="margin-bottom:var(--space-3);align-items:flex-end;gap:var(--space-3);">
            <div class="field" style="max-width:200px;">
                <label class="field__label" for="m">Pay month</label>
                <input id="m" type="month" class="input" wire:model.live="month">
            </div>
            <div class="field__hint">Loss of pay is prorated per day; {{ (int) client_setting('payroll_paid_leaves', 2) }} paid leaves/month.</div>
        </div>

        <x-data-table :head="['Staff', 'Gross / month', 'Unpaid days', 'Net payable', 'Status', '']">
            @foreach ($rows as $r)
                @php($s = $r['staff'])
                @php($ps = $r['payslip'])
                <tr wire:key="pr-{{ $s->id }}">
                    <td><b>{{ $s->name }}</b>@if($s->designation)<div class="field__hint">{{ $s->designation }}</div>@endif</td>
                    <td class="num">
                        @if ($r['structure']){{ paise_to_rupees($r['structure']->monthly_gross) }}@else<span class="field__hint">Not set</span>@endif
                    </td>
                    <td class="num">{{ $r['summary']['unpaid_days'] }}</td>
                    <td class="num">@if ($ps){{ paise_to_rupees($ps->net) }}@else—@endif</td>
                    <td>
                        @if (! $ps)<span class="field__hint">—</span>
                        @else
                            @php($v = ['draft'=>'muted','finalized'=>'info','paid'=>'success'][$ps->status] ?? 'muted')
                            <x-pill variant="{{ $v }}">{{ ucfirst($ps->status) }}</x-pill>
                        @endif
                    </td>
                    <td style="text-align:right;white-space:nowrap;">
                        <button class="btn btn--sm" wire:click="editStructure({{ $s->id }})">Salary</button>
                        @if ($r['structure'])
                            @if (! $ps || $ps->status === 'draft')
                                <button class="btn btn--sm btn--primary" wire:click="generate({{ $s->id }})">{{ $ps ? 'Regenerate' : 'Generate' }}</button>
                            @endif
                            @if ($ps)
                                <a class="btn btn--sm" href="{{ route('payslips.show', $ps->id) }}" target="_blank" rel="noopener">Payslip</a>
                                @if ($ps->status === 'draft')<button class="btn btn--sm" wire:click="finalize({{ $ps->id }})">Finalize</button>@endif
                                @if ($ps->status === 'finalized')<button class="btn btn--sm" wire:click="markPaid({{ $ps->id }})">Mark paid</button>@endif
                            @endif
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-data-table>
    </x-card>

    {{-- Salary structure modal --}}
    <x-modal wire:model="showStructure" title="Salary structure">
        <x-field name="grossRupees" label="Monthly gross (₹)" wire:model="grossRupees" inputmode="decimal" required />

        <div style="margin-top:var(--space-3);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <label class="field__label" style="margin:0;">Fixed monthly deductions</label>
                <button class="btn btn--sm" wire:click="addDeduction">Add deduction</button>
            </div>
            @forelse ($deductionRows as $i => $row)
                <div wire:key="ded-{{ $i }}" style="display:flex;gap:8px;margin-bottom:8px;">
                    <input class="input" placeholder="e.g. PF, Professional tax" wire:model="deductionRows.{{ $i }}.name">
                    <input class="input" style="max-width:140px;" placeholder="₹" inputmode="decimal" wire:model="deductionRows.{{ $i }}.amount">
                    <button class="btn btn--sm" wire:click="removeDeduction({{ $i }})">&times;</button>
                </div>
            @empty
                <p class="field__hint">No fixed deductions. Add PF, professional tax, etc. if applicable.</p>
            @endforelse
        </div>

        <x-slot:footer>
            <button class="btn" wire:click="$set('showStructure', false)">Cancel</button>
            <button class="btn btn--primary" wire:click="saveStructure">Save structure</button>
        </x-slot:footer>
    </x-modal>
</div>
