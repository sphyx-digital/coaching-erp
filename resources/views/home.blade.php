<!DOCTYPE html>
<html lang="en" data-brand="client"
      style="--brand-hue: {{ $branding['brand_hue'] }}; --action: {{ $branding['action_color'] }}; --action-hover: {{ $branding['action_color'] }};">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ $branding['action_color'] }}">
    <meta name="description" content="{{ $branding['name'] }} — coaching institute ERP: enquiries, admissions, batches, fees with GST, attendance, assessments and a parent portal.">
    <title>{{ $branding['name'] }} · Coaching Institute ERP</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background: var(--surface); }
        .mk-nav { position: sticky; top: 0; z-index: 20; display: flex; align-items: center; justify-content: space-between; padding: 14px 24px; background: color-mix(in srgb, var(--surface) 85%, transparent); backdrop-filter: saturate(1.4) blur(8px); border-bottom: 1px solid var(--border); }
        .mk-brand { display: flex; align-items: center; gap: 10px; font-family: var(--font-heading); font-weight: 700; }
        .mk-logo { width: 34px; height: 34px; border-radius: 9px; background: var(--brand-hue); color: #fff; display: grid; place-items: center; font-weight: 700; overflow: hidden; }
        .mk-logo img { width: 100%; height: 100%; object-fit: cover; }
        .mk-wrap { max-width: 1080px; margin: 0 auto; padding: 0 24px; }
        .mk-hero { text-align: center; padding: 72px 24px 56px; background: radial-gradient(1200px 400px at 50% -120px, color-mix(in srgb, var(--brand-hue) 16%, transparent), transparent); }
        .mk-hero h1 { font-family: var(--font-heading); font-weight: 800; font-size: clamp(30px, 5vw, 52px); line-height: 1.08; margin: 0 auto 16px; max-width: 16ch; letter-spacing: -0.02em; }
        .mk-hero p { color: var(--text-muted); font-size: clamp(16px, 2vw, 19px); max-width: 60ch; margin: 0 auto 28px; }
        .mk-cta { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .mk-btn { display: inline-flex; align-items: center; gap: 8px; min-height: 48px; padding: 0 22px; border-radius: 12px; font-weight: 600; text-decoration: none; font-size: 15px; }
        .mk-btn--primary { background: var(--action); color: #fff; }
        .mk-btn--ghost { background: var(--surface); color: var(--text); border: 1px solid var(--border-strong); }
        .mk-badges { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; margin-top: 26px; }
        .mk-badge { font-size: 12.5px; color: var(--text-muted); border: 1px solid var(--border); border-radius: 999px; padding: 5px 12px; background: var(--surface); }
        .mk-section { padding: 56px 0; }
        .mk-section h2 { font-family: var(--font-heading); font-weight: 700; font-size: 28px; text-align: center; margin: 0 0 8px; }
        .mk-section .lede { text-align: center; color: var(--text-muted); max-width: 56ch; margin: 0 auto 34px; }
        .mk-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        @media (max-width: 820px) { .mk-grid { grid-template-columns: 1fr; } }
        .mk-card { border: 1px solid var(--border); border-radius: 16px; padding: 22px; background: var(--surface); transition: transform .2s var(--ease-out), box-shadow .2s var(--ease-out); }
        .mk-card:hover { transform: translateY(-3px); box-shadow: var(--elev-2); }
        .mk-ic { width: 42px; height: 42px; border-radius: 11px; display: grid; place-items: center; background: color-mix(in srgb, var(--brand-hue) 14%, var(--surface)); color: var(--action); margin-bottom: 12px; }
        .mk-card h3 { font-family: var(--font-heading); font-weight: 600; font-size: 17px; margin: 0 0 6px; }
        .mk-card p { color: var(--text-muted); font-size: 14px; margin: 0; }
        .mk-strip { background: var(--surface-inverse); color: #fff; border-radius: 20px; padding: 40px; text-align: center; }
        .mk-strip h2 { color: #fff; }
        .mk-strip .lede { color: #c4ccdd; }
        .mk-pills { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-top: 20px; }
        .mk-pill { border: 1px solid rgba(255,255,255,.2); border-radius: 999px; padding: 8px 14px; font-size: 13px; }
        .mk-foot { text-align: center; color: var(--text-subtle); font-size: 13px; padding: 40px 24px; border-top: 1px solid var(--border); }
        .mk-cta-band { text-align: center; padding: 56px 24px; }
    </style>
</head>
<body>
<nav class="mk-nav">
    <div class="mk-brand">
        <span class="mk-logo">@if(!empty($branding['logo']))<img src="{{ $branding['logo'] }}" alt="">@else{{ strtoupper(mb_substr($branding['name'],0,1)) }}@endif</span>
        {{ $branding['name'] }}
    </div>
    <a class="mk-btn mk-btn--primary" style="min-height:40px;padding:0 18px;" href="{{ route('login') }}">Sign in</a>
</nav>

<header class="mk-hero">
    <div class="mk-wrap">
        <h1>Run your coaching institute, end to end.</h1>
        <p>From the first enquiry to the final report card — admissions, batches, GST-correct fees, attendance, assessments and a parent portal, in one modern, secure platform.</p>
        <div class="mk-cta">
            <a class="mk-btn mk-btn--primary" href="{{ route('login') }}"><x-icon name="dashboard" /> Sign in to your account</a>
            <a class="mk-btn mk-btn--ghost" href="#features">Explore features</a>
        </div>
        <div class="mk-badges">
            <span class="mk-badge">GST-correct invoicing</span>
            <span class="mk-badge">Double-entry ledger</span>
            <span class="mk-badge">Approvals &amp; audit trail</span>
            <span class="mk-badge">Installable PWA</span>
            <span class="mk-badge">WCAG AA accessible</span>
        </div>
    </div>
</header>

<section class="mk-section" id="features">
    <div class="mk-wrap">
        <h2>Everything a coaching institute needs</h2>
        <p class="lede">Nine modules that work together — no spreadsheets, no re-keying, no lost leads.</p>
        <div class="mk-grid">
            @php($features = [
                ['enquiry','Enquiry &amp; leads','Capture every walk-in and call, track follow-ups, and convert to admission in one click.'],
                ['admission','Admissions','Structured student &amp; guardian records, consent capture, and minor-guardian safeguards.'],
                ['batch','Batches &amp; timetable','Capacity-aware batches and a weekly timetable with automatic teacher and room conflict checks.'],
                ['fees','Fees &amp; payments','GST-correct invoices (CGST/SGST/IGST), numbered receipts, installments, discounts and refunds — on a balanced ledger.'],
                ['attendance','Attendance','Fast register marking, live percentages, and low-attendance flags for every batch.'],
                ['assessment','Assessments','Marks, configurable grades, and print-clean, branding-aware report cards with attendance.'],
                ['dashboard','Dashboards','Role-aware KPIs and drill-down tables for admissions, collections, attendance and results.'],
                ['staff','Roles &amp; access','Eight roles with branch-level scoping, a full audit trail, and an approval &amp; escalation engine.'],
                ['components','Parent portal (PWA)','An installable, offline-ready portal where parents see fees, attendance, results and notices.'],
            ])
            @foreach($features as [$icon,$title,$desc])
                <div class="mk-card">
                    <div class="mk-ic"><x-icon name="{{ $icon }}" width="22" height="22" /></div>
                    <h3>{!! $title !!}</h3>
                    <p>{!! $desc !!}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="mk-section">
    <div class="mk-wrap">
        <div class="mk-strip">
            <h2>Built right, where it matters</h2>
            <p class="lede">The parts that are hardest to get right and easiest to get wrong — done properly.</p>
            <div class="mk-pills">
                <span class="mk-pill">Money in exact paise</span>
                <span class="mk-pill">CGST / SGST / IGST split to the paisa</span>
                <span class="mk-pill">Double-entry ledger that always balances</span>
                <span class="mk-pill">Gapless, concurrency-safe numbering</span>
                <span class="mk-pill">Approvals with SLA escalation</span>
                <span class="mk-pill">Consent &amp; minor safeguards</span>
                <span class="mk-pill">Full audit trail</span>
                <span class="mk-pill">Per-client branding</span>
                <span class="mk-pill">Offline PWA</span>
            </div>
        </div>
    </div>
</section>

<section class="mk-cta-band">
    <div class="mk-wrap">
        <h2 style="font-family:var(--font-heading);font-weight:700;font-size:26px;margin:0 0 10px;">Ready when you are</h2>
        <p style="color:var(--text-muted);margin:0 0 22px;">Sign in to your {{ $branding['name'] }} account.</p>
        <a class="mk-btn mk-btn--primary" href="{{ route('login') }}">Sign in</a>
    </div>
</section>

<footer class="mk-foot">
    {{ $branding['name'] }} · Coaching Institute ERP &nbsp;·&nbsp; Powered by Sphyx Digital
</footer>
</body>
</html>
