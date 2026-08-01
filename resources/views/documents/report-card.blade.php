<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report card — {{ $student->name }}</title>
    <style>
        :root { --ink:#1a1c22; --muted:#5a6172; --line:#d7dbe3; --accent: {{ client_setting('action_color', '#4338ca') }}; --brand: {{ client_setting('brand_hue', '#6366f1') }}; }
        * { box-sizing:border-box; }
        body { margin:0; background:#f2f3f5; color:var(--ink); font-family:"DM Sans","Segoe UI",system-ui,sans-serif; padding:24px; }
        .sheet { max-width:760px; margin:0 auto; background:#fff; border:1px solid var(--line); border-radius:10px; padding:36px 40px; }
        .num { font-variant-numeric: tabular-nums lining-nums; }
        .head { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid var(--accent); padding-bottom:16px; margin-bottom:20px; }
        .inst { font-family:"Poppins",system-ui,sans-serif; font-weight:600; font-size:20px; color:var(--accent); }
        .muted { color:var(--muted); font-size:13px; }
        h1 { font-family:"Poppins",sans-serif; font-size:16px; letter-spacing:.06em; text-transform:uppercase; margin:0 0 4px; text-align:right; }
        .meta { display:grid; grid-template-columns:1fr 1fr; gap:8px 24px; margin-bottom:20px; font-size:14px; }
        .meta .k { color:var(--muted); }
        table { width:100%; border-collapse:collapse; font-size:14px; margin-bottom:16px; }
        th,td { padding:8px 10px; border-bottom:1px solid var(--line); text-align:left; }
        th { background:#f7f8fa; font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); }
        td.r, th.r { text-align:right; }
        .summary { display:flex; gap:16px; margin-top:8px; }
        .tile { flex:1; border:1px solid var(--line); border-radius:8px; padding:12px 16px; }
        .tile .k { font-size:12px; color:var(--muted); }
        .tile .v { font-family:"Poppins",sans-serif; font-weight:600; font-size:22px; }
        .grade { display:inline-block; background:var(--brand); color:#fff; border-radius:6px; padding:2px 12px; font-weight:600; }
        .foot { margin-top:28px; font-size:12px; color:var(--muted); display:flex; justify-content:space-between; }
        @media print { body { background:#fff; padding:0; } .sheet { border:0; border-radius:0; } .noprint { display:none; } }
    </style>
</head>
<body>
<div class="sheet">
    <div class="head">
        <div>
            <div class="inst">{{ $institute?->name ?? client_setting('institute_name') }}</div>
            <div class="muted">{{ $institute?->city }}</div>
        </div>
        <div>
            <h1>Report Card</h1>
            <div class="muted" style="text-align:right;">{{ $assessment->name }} · {{ ucfirst($assessment->type) }}</div>
            @if ($card)<div class="muted" style="text-align:right;">v{{ $card->version }}</div>@endif
        </div>
    </div>

    <div class="meta">
        <div><span class="k">Student:</span> <strong>{{ $student->name }}</strong></div>
        <div><span class="k">Admission #:</span> <span class="num">{{ $student->admission_number ?: '—' }}</span></div>
        <div><span class="k">Batch:</span> {{ $assessment->batch?->name }}</div>
        <div><span class="k">Date:</span> <span class="num">{{ $assessment->assessment_date?->format('d-m-Y') ?: '—' }}</span></div>
    </div>

    <table>
        <thead><tr><th>Subject</th><th class="r">Marks</th><th class="r">Max</th><th class="r">Grade</th></tr></thead>
        <tbody>
        @foreach ($data['rows'] as $row)
            <tr>
                <td>{{ $row['subject'] }}</td>
                <td class="r num">{{ $row['marks'] === null ? '—' : rtrim(rtrim(number_format($row['marks'], 2), '0'), '.') }}</td>
                <td class="r num">{{ (int) $row['max'] }}</td>
                <td class="r">{{ $row['grade'] ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="summary">
        <div class="tile"><div class="k">Total</div><div class="v num">{{ (int) $data['total'] }} / {{ (int) $data['max_total'] }}</div></div>
        <div class="tile"><div class="k">Percentage</div><div class="v num">{{ number_format($data['percent_bp'] / 100, 1) }}%</div></div>
        <div class="tile"><div class="k">Grade</div><div class="v"><span class="grade">{{ $data['grade'] ?? '—' }}</span></div></div>
        <div class="tile"><div class="k">Attendance</div><div class="v num">{{ number_format(($data['attendance_bp'] ?? 0) / 100, 0) }}%</div></div>
    </div>

    <div class="foot">
        <span>This is a computer-generated report card.</span>
        <span>For {{ $institute?->name ?? client_setting('institute_name') }}</span>
    </div>
    <div class="noprint" style="text-align:center; margin-top:20px;">
        <button onclick="window.print()" style="padding:10px 18px; border-radius:8px; border:0; background:var(--accent); color:#fff; font-size:14px; cursor:pointer;">Print</button>
    </div>
</div>
</body>
</html>
