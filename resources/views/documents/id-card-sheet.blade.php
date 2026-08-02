<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ID cards — {{ $context }}</title>
    <style>
        :root { --brand: {{ client_setting('brand_hue', '#6366f1') }}; --accent: {{ client_setting('action_color', '#4338ca') }}; --ink:#15181f; --muted:#5a6172; --line:#d7dbe3; }
        * { box-sizing: border-box; }
        body { margin:0; background:#eceef1; font-family:"DM Sans","Segoe UI",system-ui,sans-serif; color:var(--ink); padding:16px; }
        .bar { max-width:920px; margin:0 auto 14px; display:flex; justify-content:space-between; align-items:center; }
        .bar h1 { font-family:"Poppins",sans-serif; font-size:18px; margin:0; }
        .sheet { max-width:920px; margin:0 auto; display:grid; grid-template-columns:repeat(2, 1fr); gap:14px; }
        .card { background:#fff; border:1px solid var(--line); border-radius:12px; overflow:hidden; height:220px; display:flex; flex-direction:column; }
        .card__head { background:linear-gradient(135deg, var(--brand), var(--accent)); color:#fff; padding:10px 14px; display:flex; align-items:center; gap:8px; }
        .card__logo { width:26px; height:26px; border-radius:6px; background:rgba(255,255,255,.2); display:grid; place-items:center; font-family:"Poppins",sans-serif; font-weight:700; overflow:hidden; }
        .card__logo img { width:100%; height:100%; object-fit:cover; }
        .card__inst { font-family:"Poppins",sans-serif; font-weight:600; font-size:13px; }
        .card__body { display:flex; gap:12px; padding:12px 14px; flex:1; }
        .photo { width:66px; height:80px; border-radius:8px; background:var(--brand); color:#fff; display:grid; place-items:center; font-family:"Poppins",sans-serif; font-weight:700; font-size:26px; overflow:hidden; flex-shrink:0; }
        .photo img { width:100%; height:100%; object-fit:cover; }
        .info { font-size:12.5px; line-height:1.5; }
        .info .name { font-family:"Poppins",sans-serif; font-weight:600; font-size:15px; margin-bottom:2px; }
        .info .k { color:var(--muted); }
        .num { font-variant-numeric: tabular-nums lining-nums; }
        .card__foot { border-top:1px solid var(--line); padding:6px 14px; font-size:10.5px; color:var(--muted); display:flex; justify-content:space-between; }
        .noprint { max-width:920px; margin:0 auto 14px; text-align:right; }
        .btn { padding:9px 16px; border-radius:8px; border:0; background:var(--accent); color:#fff; font-size:14px; cursor:pointer; }
        @media print {
            body { background:#fff; padding:0; }
            .noprint, .bar { display:none; }
            .sheet { max-width:none; gap:8px; }
            .card { break-inside:avoid; }
        }
    </style>
</head>
<body>
<div class="noprint"><button class="btn" onclick="window.print()">Print</button></div>
<div class="bar"><h1>ID cards — {{ $context }}</h1><span class="num">{{ $students->count() }} cards</span></div>

<div class="sheet">
    @foreach ($students as $s)
        <div class="card">
            <div class="card__head">
                <span class="card__logo">@if($institute?->logo)<img src="{{ $institute->logo }}" alt="">@else{{ strtoupper(mb_substr($institute?->name ?? 'C',0,1)) }}@endif</span>
                <span class="card__inst">{{ $institute?->name ?? client_setting('institute_name') }}</span>
            </div>
            <div class="card__body">
                <div class="photo">@if($s->photo)<img src="{{ $s->photo }}" alt="">@else{{ strtoupper(mb_substr($s->name,0,1)) }}@endif</div>
                <div class="info">
                    <div class="name">{{ $s->name }}</div>
                    <div><span class="k">Adm #</span> <span class="num">{{ $s->admission_number ?: '—' }}</span></div>
                    <div><span class="k">Course</span> {{ optional($s->enrollments->first())->course?->name ?? '—' }}</div>
                    <div><span class="k">Branch</span> {{ $s->branch?->name ?? '—' }}</div>
                    @if($s->phone)<div><span class="k">Phone</span> <span class="num">{{ $s->phone }}</span></div>@endif
                </div>
            </div>
            <div class="card__foot"><span>{{ $session?->name ?? '' }}</span><span>Valid for the session</span></div>
        </div>
    @endforeach
</div>
</body>
</html>
