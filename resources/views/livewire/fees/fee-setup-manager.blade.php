<div class="container-narrow stack" style="gap: var(--space-5);">
    <x-page-header title="Fee setup" />

    <div class="grid-cards" style="grid-template-columns: 1fr 1fr;">
        <x-card title="Add a tax rate">
            <form wire:submit="addTaxRate">
                <x-field name="taxName" label="Name" wire:model="taxName" hint="e.g. GST 18%" required />
                <x-field name="taxPercent" label="Rate (%)" type="number" step="0.01" wire:model="taxPercent" required />
                <x-btn type="submit" variant="primary">Add tax rate</x-btn>
            </form>
            @if ($taxRates->isNotEmpty())
                <div style="margin-top: var(--space-3);">
                    @foreach ($taxRates as $t)
                        <x-pill variant="info">{{ $t->name }} — {{ number_format($t->rate_bp / 100, 2) }}%</x-pill>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card title="Add a fee plan">
            <form wire:submit="addPlan">
                <x-field name="planName" label="Name" wire:model="planName" required />
                <x-select name="planCourseId" label="Course" :options="$courses->toArray()" placeholder="Any course" wire:model="planCourseId" />
                <x-btn type="submit" variant="primary">Add plan</x-btn>
            </form>
        </x-card>
    </div>

    <x-card title="Add a component to a plan">
        <form wire:submit="addComponent">
            <div class="grid-cards">
                <x-select name="componentPlanId" label="Fee plan" :options="$planOptions->toArray()" placeholder="Select plan" wire:model="componentPlanId" required />
                <x-field name="componentName" label="Component" wire:model="componentName" hint="Tuition, Registration…" required />
                <x-field name="componentAmount" label="Amount (₹)" type="number" step="0.01" wire:model="componentAmount" required />
                <x-select name="componentTaxRateId" label="Tax rate" :options="$taxOptions" placeholder="Exempt" wire:model="componentTaxRateId" />
            </div>
            <label style="display:flex; align-items:center; gap: var(--space-2); min-height: var(--tap-min);">
                <input type="checkbox" wire:model="componentTaxable"> Taxable
            </label>
            <x-btn type="submit" variant="primary">Add component</x-btn>
        </form>
    </x-card>

    <x-card title="Fee plans">
        @forelse ($plans as $plan)
            <div style="margin-bottom: var(--space-4);">
                <strong>{{ $plan->name }}</strong> <span class="field__hint">{{ $plan->course?->name }}</span>
                <x-data-table :head="['Component', 'Tax', ['label' => 'Amount', 'num' => true]]">
                    @foreach ($plan->components as $c)
                        <tr wire:key="comp-{{ $c->id }}">
                            <td>{{ $c->name }}</td>
                            <td>{{ $c->is_taxable && $c->taxRate ? number_format($c->taxRate->rate_bp / 100, 2).'%' : 'Exempt' }}</td>
                            <td class="num">{{ paise_to_rupees($c->amount) }}</td>
                        </tr>
                    @endforeach
                </x-data-table>
            </div>
        @empty
            <x-state title="No fee plans yet">Add a plan and its components to raise invoices.</x-state>
        @endforelse
    </x-card>
</div>
