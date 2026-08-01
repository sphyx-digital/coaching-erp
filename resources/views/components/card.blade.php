@props(['title' => null])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title)
        <h2 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">{{ $title }}</h2>
    @endif
    {{ $slot }}
</div>
