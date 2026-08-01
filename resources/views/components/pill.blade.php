@props([
    'variant' => 'info',   // success | warning | danger | info
    'icon' => true,        // show the dot (status is never colour alone: dot + word)
])

<span {{ $attributes->merge(['class' => 'pill pill--'.$variant]) }}>
    @if ($icon)<span class="pill__dot" aria-hidden="true"></span>@endif
    {{ $slot }}
</span>
