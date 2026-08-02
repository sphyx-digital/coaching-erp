@props(['title' => null, 'eyebrow' => null, 'subtitle' => null, 'wide' => false])

{{-- Slide-over detail drawer. Open state is entangled to a Livewire property
     via wire:model, mirroring x-modal. Actions go in the `footer` slot. --}}
<div x-data="{ open: @entangle($attributes->wire('model')) }"
     x-show="open" x-cloak class="drawer-overlay"
     @keydown.escape.window="open = false">
    <div class="drawer {{ $wide ? 'drawer--wide' : '' }}" @click.outside="open = false" role="dialog" aria-modal="true">
        <div class="drawer__head">
            <div>
                @if ($eyebrow)<div class="drawer__eyebrow">{{ $eyebrow }}</div>@endif
                <div class="drawer__title">{{ $title }}</div>
                @if ($subtitle)<div class="drawer__sub">{{ $subtitle }}</div>@endif
            </div>
            <button type="button" class="drawer__close" @click="open = false" aria-label="Close">&times;</button>
        </div>
        <div class="drawer__body">
            {{ $slot }}
        </div>
        @isset($footer)
            <div class="drawer__foot">{{ $footer }}</div>
        @endisset
    </div>
</div>
