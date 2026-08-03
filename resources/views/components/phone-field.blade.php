@props(['field', 'dialField' => null, 'label' => 'Phone'])
@php($dialField = $dialField ?? $field.'_dial')

<div class="field">
    <span class="field__label">{{ $label }}</span>
    <div class="phone-row">
        <select class="select" wire:model="{{ $dialField }}" aria-label="Dial code">
            @foreach (['+91', '+1', '+44', '+971', '+61', '+65', '+880', '+977'] as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach
        </select>
        <input class="input" type="tel" inputmode="numeric" pattern="[6-9][0-9]{9}" maxlength="10"
               placeholder="10-digit mobile" wire:model="{{ $field }}" aria-label="Mobile number"
               title="Enter a 10-digit Indian mobile number starting with 6-9"
               oninput="this.value=this.value.replace(/\D/g,'').replace(/^[0-5]+/,'').slice(0,10)">
    </div>
    @error($field)<span class="field__error">{{ $message }}</span>@enderror
</div>
