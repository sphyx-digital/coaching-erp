@props([
    'variant' => 'primary',   // primary | secondary
    'size' => 'md',           // md | sm
    'type' => 'button',
    'href' => null,
])

@php
    $classes = 'btn btn--'.$variant.($size === 'sm' ? ' btn--sm' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
