@props([
    'variant' => 'empty',   // empty | error | loading
    'title' => null,
    'rows' => 3,            // skeleton rows when loading
])

@if ($variant === 'loading')
    <div class="state" role="status" aria-live="polite" {{ $attributes }}>
        <span class="field__hint" style="margin-bottom: var(--space-3); display:block;">Loading…</span>
        @for ($i = 0; $i < $rows; $i++)
            <div class="skeleton skeleton-row" style="width: {{ [92, 78, 85, 70][$i % 4] }}%; margin-inline:auto;"></div>
        @endfor
    </div>
@else
    <div {{ $attributes->merge(['class' => 'state'.($variant === 'error' ? ' state--error' : '')]) }}
         @if($variant==='error') role="alert" @endif>
        @if ($title)<div class="state__title">{{ $title }}</div>@endif
        <div>{{ $slot }}</div>
        @isset($actions)
            <div class="state__actions">{{ $actions }}</div>
        @endisset
    </div>
@endif
