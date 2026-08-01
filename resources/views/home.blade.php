@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-narrow">
    <div class="card" style="margin-bottom: var(--space-5);">
        <h1 style="font-size: var(--text-2xl); margin-bottom: var(--space-2);">
            {{ $branding['name'] }}
        </h1>
        <p style="color: var(--text-muted); max-width: 60ch;">
            Coaching Institute ERP by Sphyx Digital. The foundation is live: design tokens,
            the app shell, per-client branding, the boot-time contrast guard, feature flags,
            the client extension loader, and the installable PWA shell. Business modules come
            online phase by phase.
        </p>
        <div style="display:flex; gap: var(--space-3); margin-top: var(--space-5); flex-wrap: wrap;">
            <button class="btn btn--primary" type="button">Primary action</button>
            <button class="btn btn--secondary" type="button">Secondary</button>
            <button class="btn btn--sm btn--secondary" type="button">Small</button>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-4);">
        <div class="card">
            <div style="color: var(--text-muted); font-size: var(--text-sm);">Brand hue (decorative)</div>
            <div style="display:flex; align-items:center; gap: var(--space-3); margin-top: var(--space-2);">
                <span style="width:28px; height:28px; border-radius: var(--radius-md); background: var(--brand-hue);"></span>
                <code class="num">{{ $branding['brand_hue'] }}</code>
            </div>
        </div>
        <div class="card">
            <div style="color: var(--text-muted); font-size: var(--text-sm);">Action colour (AA on white)</div>
            <div style="display:flex; align-items:center; gap: var(--space-3); margin-top: var(--space-2);">
                <span style="width:28px; height:28px; border-radius: var(--radius-md); background: var(--action);"></span>
                <code class="num">{{ $branding['action_color'] }}</code>
            </div>
        </div>
        <div class="card">
            <div style="color: var(--text-muted); font-size: var(--text-sm);">Status pills</div>
            <div style="display:flex; gap: var(--space-2); margin-top: var(--space-3); flex-wrap: wrap;">
                <span class="pill pill--success"><span class="pill__dot"></span> Paid</span>
                <span class="pill pill--warning"><span class="pill__dot"></span> Due</span>
                <span class="pill pill--danger"><span class="pill__dot"></span> Overdue</span>
            </div>
        </div>
    </div>
</div>
@endsection
