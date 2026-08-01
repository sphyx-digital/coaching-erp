@props([
    'label',
    'value',
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'kpi']) }}>
    <div class="kpi__label">{{ $label }}</div>
    <div class="kpi__value">{{ $value }}</div>
    @if ($hint)<div class="kpi__hint">{{ $hint }}</div>@endif
</div>
