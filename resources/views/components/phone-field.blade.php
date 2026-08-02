@props(['field', 'dialField' => null, 'label' => 'Phone'])
@php($dialField = $dialField ?? $field.'_dial')

<div class="field">
    <span class="field__label">{{ $label }}</span>
    <div class="phone-row">
        <select class="select" wire:model="{{ $dialField }}" aria-label="Dial code">
            @foreach (['+91', '+1', '+44', '+971', '+61', '+65', '+880', '+977'] as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach
        </select>
        <input class="input" type="tel" inputmode="numeric" pattern="[0-9]{6,15}" maxlength="15"
               placeholder="Mobile number" wire:model="{{ $field }}" aria-label="Mobile number"
               oninput="this.value=this.value.replace(/[^0-9]/g,'')">
    </div>
    @error($field)<span class="field__error">{{ $message }}</span>@enderror
</div>
