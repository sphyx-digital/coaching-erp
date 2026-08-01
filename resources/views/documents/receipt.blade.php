<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $payment->receipt_number }}</title>
    <style>
        :root { --ink:#1a1c22; --muted:#5a6172; --line:#d7dbe3; --accent: {{ client_setting('action_color', '#4338ca') }}; }
        * { box-sizing: border-box; }
        body { margin:0; background:#f2f3f5; color:var(--ink); font-family:"DM Sans","Segoe UI",system-ui,sans-serif; padding:24px; }
        .sheet { max-width:760px; margin:0 auto; background:#fff; border:1px solid var(--line); border-radius:10px; padding:36px 40px; }
        .num { font-variant-numeric: tabular-nums lining-nums; }
        .head { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid var(--accent); padding-bottom:16px; margin-bottom:20px; }
        .inst { font-family:"Poppins",system-ui,sans-serif; font-weight:600; font-size:20px; color:var(--accent); }
        .muted { color:var(--muted); font-size:13px; }
        .doc-title { text-align:right; }
        .doc-title h1 { font-family:"Poppins",sans-serif; font-size:16px; letter-spacing:.08em; text-transform:uppercase; margin:0 0 4px; }
        .meta { display:grid; grid-template-columns:1fr 1fr; gap:8px 24px; margin-bottom:20px; font-size:14px; }
        .meta .k { color:var(--muted); }
        table { width:100%; border-collapse:collapse; font-size:14px; margin-bottom:16px; }
        th,td { padding:8px 10px; border-bottom:1px solid var(--line); text-align:left; }
        th { background:#f7f8fa; font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); }
        td.r, th.r { text-align:right; }
        .totals { width:280px; margin-left:auto; font-size:14px; }
        .totals .row { display:flex; justify-content:space-between; padding:4px 0; }
        .totals .grand { border-top:2px solid var(--ink); margin-top:6px; padding-top:8px; font-weight:600; font-size:16px; }
        .paid { display:inline-block; margin-top:16px; border:2px solid #1a7f4b; color:#1a7f4b; font-weight:600; padding:4px 12px; border-radius:6px; transform:rotate(-3deg); letter-spacing:.06em; }
        .foot { margin-top:28px; font-size:12px; color:var(--muted); display:flex; justify-content:space-between; }
        @media print { body { background:#fff; padding:0; } .sheet { border:0; border-radius:0; } .noprint { display:none; } }
    </style>
</head>
<body>
<div class="sheet">
    <div class="head">
        <div>
            <div class="inst">{{ $institute?->name ?? client_setting('institute_name') }}</div>
            <div class="muted">
                @if ($institute?->address){{ $institute->address }}, @endif{{ $institute?->city }}
                @if ($institute?->gstin)<br>GSTIN: <span class="num">{{ $institute->gstin }}</span>@endif
            </div>
        </div>
        <div class="doc-title">
            <h1>Fee Receipt</h1>
            <div class="muted">No. <strong class="num">{{ $payment->receipt_number }}</strong></div>
            <div class="muted">Date: <span class="num">{{ $payment->payment_date?->format('d-m-Y') }}</span></div>
        </div>
    </div>

    <div class="meta">
        <div><span class="k">Received from:</span> <strong>{{ $payment->student?->name }}</strong></div>
        <div><span class="k">Admission #:</span> <span class="num">{{ $payment->student?->admission_number ?: '—' }}</span></div>
        <div><span class="k">Mode:</span> {{ ucfirst($payment->mode) }}</div>
        <div><span class="k">Reference:</span> {{ $payment->reference ?: '—' }}</div>
    </div>

    @php($inv = $payment->allocations->first()?->invoice)
    @if ($inv)
        <table>
            <thead><tr><th>Description</th><th class="r">Taxable</th><th class="r">CGST</th><th class="r">SGST</th><th class="r">IGST</th><th class="r">Amount</th></tr></thead>
            <tbody>
            @foreach ($inv->lines as $line)
                <tr>
                    <td>{{ $line->description }}</td>
                    <td class="r num">{{ paise_to_rupees($line->taxable_value, false) }}</td>
                    <td class="r num">{{ paise_to_rupees($line->cgst, false) }}</td>
                    <td class="r num">{{ paise_to_rupees($line->sgst, false) }}</td>
                    <td class="r num">{{ paise_to_rupees($line->igst, false) }}</td>
                    <td class="r num">{{ paise_to_rupees($line->line_total, false) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="row"><span>Taxable value</span><span class="num">{{ paise_to_rupees($inv->subtotal) }}</span></div>
            @if ($inv->is_interstate)
                <div class="row"><span>IGST</span><span class="num">{{ paise_to_rupees($inv->igst_total) }}</span></div>
            @else
                <div class="row"><span>CGST</span><span class="num">{{ paise_to_rupees($inv->cgst_total) }}</span></div>
                <div class="row"><span>SGST</span><span class="num">{{ paise_to_rupees($inv->sgst_total) }}</span></div>
            @endif
            @if ($inv->discount_total)<div class="row"><span>Discount</span><span class="num">- {{ paise_to_rupees($inv->discount_total) }}</span></div>@endif
            <div class="row grand"><span>Invoice total</span><span class="num">{{ paise_to_rupees($inv->total) }}</span></div>
            <div class="row"><span>Amount received</span><span class="num">{{ paise_to_rupees($payment->amount) }}</span></div>
            <div class="row"><span>Balance due</span><span class="num">{{ paise_to_rupees($inv->balance) }}</span></div>
        </div>
    @else
        <div class="totals">
            <div class="row grand"><span>Amount received (advance)</span><span class="num">{{ paise_to_rupees($payment->amount) }}</span></div>
        </div>
    @endif

    @if ($inv && $inv->balance <= 0)<div class="paid">PAID IN FULL</div>@endif

    <div class="foot">
        <span>This is a computer-generated receipt.</span>
        <span>For {{ $institute?->name ?? client_setting('institute_name') }}</span>
    </div>
    <div class="noprint" style="text-align:center; margin-top:20px;">
        <button onclick="window.print()" style="padding:10px 18px; border-radius:8px; border:0; background:var(--accent); color:#fff; font-size:14px; cursor:pointer;">Print</button>
    </div>
</div>
</body>
</html>
