<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payslip — {{ $payslip->staff?->name }} — {{ \Illuminate\Support\Carbon::parse($payslip->month)->format('F Y') }}</title>
    <style>
        :root { --brand: {{ client_setting('brand_hue', '#6366f1') }}; --accent: {{ client_setting('action_color', '#4338ca') }}; --ink:#15181f; --muted:#5a6172; --line:#d7dbe3; }
        * { box-sizing: border-box; }
        body { margin:0; background:#eceef1; font-family:"DM Sans","Segoe UI",system-ui,sans-serif; color:var(--ink); padding:24px; }
        .sheet { max-width:720px; margin:0 auto; background:#fff; border:1px solid var(--line); border-radius:12px; overflow:hidden; }
        .head { background:linear-gradient(135deg,var(--brand),var(--accent)); color:#fff; padding:22px 28px; display:flex; justify-content:space-between; align-items:center; }
        .head h1 { font-family:"Poppins",sans-serif; font-size:20px; margin:0; }
        .head .m { font-size:13px; opacity:.9; }
        .body { padding:24px 28px; }
        .meta { display:flex; justify-content:space-between; gap:16px; margin-bottom:20px; font-size:14px; }
        .meta .k { color:var(--muted); font-size:12px; }
        table { width:100%; border-collapse:collapse; font-size:14px; }
        th, td { text-align:left; padding:9px 4px; border-bottom:1px solid var(--line); }
        td.num, th.num { text-align:right; font-variant-numeric: tabular-nums; }
        .cols { display:flex; gap:24px; }
        .cols > div { flex:1; }
        .cols h3 { font-family:"Poppins",sans-serif; font-size:13px; text-transform:uppercase; letter-spacing:.5px; color:var(--accent); margin:0 0 6px; }
        .net { margin-top:20px; background:color-mix(in srgb, var(--brand) 10%, #fff); border-radius:10px; padding:16px 20px; display:flex; justify-content:space-between; align-items:center; }
        .net .lbl { font-family:"Poppins",sans-serif; font-weight:600; }
        .net .amt { font-family:"Poppins",sans-serif; font-weight:700; font-size:22px; }
        .foot { padding:14px 28px 22px; color:var(--muted); font-size:11px; }
        .noprint { max-width:720px; margin:0 auto 12px; text-align:right; }
        .btn { padding:9px 16px; border-radius:8px; border:0; background:var(--accent); color:#fff; font-size:14px; cursor:pointer; }
        @media print { body { background:#fff; padding:0; } .noprint { display:none; } .sheet { border:0; } }
    </style>
</head>
<body>
<div class="noprint"><button class="btn" onclick="window.print()">Print / Save PDF</button></div>
<div class="sheet">
    <div class="head">
        <div>
            <h1>{{ $institute?->name ?? client_setting('institute_name') }}</h1>
            <div class="m">Payslip · {{ \Illuminate\Support\Carbon::parse($payslip->month)->format('F Y') }}</div>
        </div>
        <div style="text-align:right;">
            <div class="m">Status</div>
            <div style="font-weight:700;text-transform:capitalize;">{{ $payslip->status }}</div>
        </div>
    </div>
    <div class="body">
        <div class="meta">
            <div>
                <div class="k">Employee</div>
                <div><b>{{ $payslip->staff?->name }}</b></div>
                @if($payslip->staff?->designation)<div class="k">{{ $payslip->staff->designation }}</div>@endif
                @if($payslip->staff?->employee_code)<div class="k">Code: {{ $payslip->staff->employee_code }}</div>@endif
            </div>
            <div style="text-align:right;">
                <div class="k">Branch</div>
                <div>{{ $payslip->staff?->primaryBranch?->name ?? '—' }}</div>
                <div class="k" style="margin-top:6px;">Days: {{ $payslip->days_in_month }} · Unpaid: {{ $payslip->unpaid_days }}</div>
            </div>
        </div>

        <div class="cols">
            <div>
                <h3>Earnings</h3>
                <table>
                    @foreach ($payslip->earnings ?? [] as $e)
                        <tr><td>{{ $e['name'] }}</td><td class="num">{{ paise_to_rupees($e['amount']) }}</td></tr>
                    @endforeach
                    <tr><td><b>Gross</b></td><td class="num"><b>{{ paise_to_rupees($payslip->gross) }}</b></td></tr>
                </table>
            </div>
            <div>
                <h3>Deductions</h3>
                <table>
                    @if ($payslip->lop_amount > 0)
                        <tr><td>Loss of pay ({{ $payslip->unpaid_days }} days)</td><td class="num">{{ paise_to_rupees($payslip->lop_amount) }}</td></tr>
                    @endif
                    @foreach ($payslip->deductions ?? [] as $d)
                        <tr><td>{{ $d['name'] }}</td><td class="num">{{ paise_to_rupees($d['amount']) }}</td></tr>
                    @endforeach
                    <tr><td><b>Total</b></td><td class="num"><b>{{ paise_to_rupees($payslip->lop_amount + $payslip->fixed_deductions) }}</b></td></tr>
                </table>
            </div>
        </div>

        <div class="net">
            <span class="lbl">Net payable</span>
            <span class="amt">{{ paise_to_rupees($payslip->net) }}</span>
        </div>
    </div>
    <div class="foot">
        This is a system-generated payslip and does not require a signature. Generated on {{ optional($payslip->generated_at)->format('d-m-Y') }}.
    </div>
</div>
</body>
</html>
