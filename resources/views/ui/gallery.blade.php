@extends('layouts.app')
@section('title', 'Component library')

@section('content')
<div class="container-narrow stack" style="gap: var(--space-6);">
    <x-page-header title="Component library">
        <x-slot:actions><x-pill variant="info">Design system</x-pill></x-slot:actions>
    </x-page-header>

    <p class="field__hint">Every module builds screens from these primitives, never ad hoc. All are token driven (no hardcoded colour), keyboard operable, and carry visible focus. Status is never colour alone: a dot plus a word.</p>

    <x-card title="Buttons">
        <div style="display:flex; gap: var(--space-3); flex-wrap: wrap; align-items:center;">
            <x-btn variant="primary">Primary</x-btn>
            <x-btn variant="secondary">Secondary</x-btn>
            <x-btn variant="secondary" size="sm">Small</x-btn>
        </div>
    </x-card>

    <x-card title="Fields and selects">
        <div class="grid-cards">
            <x-field name="demo_text" label="Text field" value="Riya Sharma" hint="A short hint" />
            <x-select name="demo_select" label="Select" :options="['jee' => 'JEE Foundation', 'neet' => 'NEET']" selected="jee" />
        </div>
    </x-card>

    <x-card title="Status pills (dot + word, survives grayscale)">
        <div style="display:flex; gap: var(--space-2); flex-wrap: wrap;">
            <x-pill variant="success">Paid</x-pill>
            <x-pill variant="warning">Due</x-pill>
            <x-pill variant="danger">Overdue</x-pill>
            <x-pill variant="info">Provisional</x-pill>
        </div>
    </x-card>

    <div>
        <h2 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">KPI cards</h2>
        <div class="grid-cards">
            <x-kpi label="Collected this month" value="₹4,85,000" hint="vs ₹5,20,000 billed" />
            <x-kpi label="Active students" value="1,284" hint="3 branches" />
            <x-kpi label="Attendance" value="92%" hint="last 30 days" />
        </div>
    </div>

    <x-card title="Data table">
        <x-data-table :head="['Student', 'Batch', ['label' => 'Outstanding', 'num' => true], 'Status']">
            <tr>
                <td>Riya Sharma</td><td>JEE-A</td><td class="num">₹12,000</td><td><x-pill variant="warning">Due</x-pill></td>
            </tr>
            <tr>
                <td>Arjun Verma</td><td>NEET-B</td><td class="num">₹0</td><td><x-pill variant="success">Paid</x-pill></td>
            </tr>
        </x-data-table>
    </x-card>

    <div class="grid-cards">
        <x-card title="Empty state">
            <x-state title="No enquiries yet">Capture your first lead to start the pipeline.
                <x-slot:actions><x-btn size="sm" variant="primary">Add enquiry</x-btn></x-slot:actions>
            </x-state>
        </x-card>
        <x-card title="Loading state">
            <x-state variant="loading" :rows="4" />
        </x-card>
        <x-card title="Error state">
            <x-state variant="error" title="Could not load">Something went wrong. Try again.</x-state>
        </x-card>
    </div>
</div>
@endsection
