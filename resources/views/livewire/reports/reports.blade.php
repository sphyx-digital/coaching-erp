<div class="stack">
    <div class="page-header">
        <h1 class="page-header__title">Reports &amp; analytics</h1>
        <div style="display:flex; gap: var(--space-2); flex-wrap:wrap;">
            <x-btn size="sm" variant="secondary" href="{{ url('/reports/export/collections') }}">Export collections</x-btn>
            <x-btn size="sm" variant="secondary" href="{{ url('/reports/export/outstanding') }}">Export outstanding</x-btn>
            <x-btn size="sm" variant="secondary" href="{{ url('/reports/export/funnel') }}">Export funnel</x-btn>
        </div>
    </div>

    {{-- Alerts --}}
    <div class="grid-cards">
        <a href="{{ url('/fees') }}" style="text-decoration:none;"><div class="kpi"><div class="kpi__label">Overdue invoices</div><div class="kpi__value">{{ $alerts['overdue_fees'] }}</div></div></a>
        <a href="{{ url('/enquiries') }}" style="text-decoration:none;"><div class="kpi"><div class="kpi__label">Follow-ups due today</div><div class="kpi__value">{{ $alerts['due_followups'] }}</div></div></a>
        <a href="{{ url('/attendance') }}" style="text-decoration:none;"><div class="kpi"><div class="kpi__label">Low-attendance batches</div><div class="kpi__value">{{ $alerts['low_batches'] }}</div></div></a>
    </div>

    {{-- KPIs --}}
    <div class="grid-kpis">
        <x-kpi label="Conversion" :value="number_format($kpis['conversion_bp']/100,1).'%'" />
        <x-kpi label="Admissions (session)" :value="number_format($kpis['admissions_session'])" />
        <x-kpi label="Billed (month)" :value="paise_to_rupees($kpis['billed_month'])" />
        <x-kpi label="Collected (month)" :value="paise_to_rupees($kpis['collected_month'])" />
        <x-kpi label="Outstanding" :value="paise_to_rupees($kpis['outstanding'])" />
        <x-kpi label="Pass rate" :value="number_format($kpis['pass_rate_bp']/100,1).'%'" />
    </div>

    <div class="grid-cards" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); align-items:start;">
        <x-card title="Collection by day (14d)"><x-bar-chart :data="$byDay" money /></x-card>
        <x-card title="Collection by mode"><x-bar-chart :data="$byMode" money /></x-card>
        <x-card title="Outstanding by ageing (days)"><x-bar-chart :data="$ageing" money /></x-card>
        <x-card title="Enquiry funnel"><x-bar-chart :data="$funnel" /></x-card>
        <x-card title="Attendance by batch"><x-bar-chart :data="$attendance" percent /></x-card>
    </div>

    <p class="field__hint">Exports include an abbreviation key. Charts show exact values (never colour alone), use tabular figures and honest axes.</p>
</div>
