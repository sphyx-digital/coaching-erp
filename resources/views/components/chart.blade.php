@props(['title' => null, 'config' => [], 'height' => 280, 'hint' => null])

{{-- Interactive Chart.js canvas. Pass a full Chart.js config from PHP; add a
     changing wire:key when the data depends on Livewire state so it re-mounts. --}}
<div {{ $attributes->merge(['class' => 'chart-card']) }}>
    @if ($title)
        <div class="chart-card__head">
            <span class="chart-card__title">{{ $title }}</span>
            @if ($hint)<span class="field__hint">{{ $hint }}</span>@endif
        </div>
    @endif
    <div class="chart-card__body" style="height: {{ $height }}px;" x-data="chart(@js($config))">
        <canvas x-ref="canvas"></canvas>
    </div>
</div>
