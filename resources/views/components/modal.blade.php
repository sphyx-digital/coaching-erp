@props(['title' => null, 'wide' => false])

<div x-data="{ open: @entangle($attributes->wire('model')) }"
     x-show="open" x-cloak class="modal-overlay"
     @keydown.escape.window="open = false">
    <div class="modal {{ $wide ? 'modal--wide' : '' }}" @click.outside="open = false" role="dialog" aria-modal="true">
        <div class="modal__head">
            <span class="modal__title">{{ $title }}</span>
            <button type="button" class="modal__close" @click="open = false" aria-label="Close">&times;</button>
        </div>
        <div class="modal__body">
            {{ $slot }}
        </div>
        @isset($footer)
            <div class="modal__foot">{{ $footer }}</div>
        @endisset
    </div>
</div>
