@props(['data' => [], 'money' => false, 'suffix' => '', 'percent' => false])
@php($max = collect($data)->map(fn ($v) => (float) $v)->max() ?: 1)

@if (empty($data))
    <x-state title="No data">Nothing to chart yet.</x-state>
@else
    {{-- Accessible: every bar carries its label and value; never colour alone. --}}
    <div role="table" aria-label="Chart data">
        @foreach ($data as $label => $value)
            <div role="row" style="display:grid; grid-template-columns: 150px 1fr 90px; align-items:center; gap: var(--space-3); margin-bottom: 8px;">
                <span role="rowheader" style="font-size: var(--text-sm); color: var(--text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $label }}</span>
                <span style="background: var(--surface-sunken); border-radius: var(--radius-sm); height: 18px; overflow:hidden;" aria-hidden="true">
                    <span style="display:block; height:100%; width: {{ max(2, round(((float) $value) / $max * 100)) }}%; background: var(--action);"></span>
                </span>
                <span class="num" style="font-size: var(--text-sm); font-weight: var(--weight-semibold); text-align:right;">
                    @if ($money){{ paise_to_rupees((int) $value) }}@elseif($percent){{ number_format($value / 100, 1) }}%@else{{ number_format($value) }}{{ $suffix }}@endif
                </span>
            </div>
        @endforeach
    </div>
@endif
