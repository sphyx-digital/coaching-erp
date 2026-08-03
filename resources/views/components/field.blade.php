@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => '',
    'hint' => null,
    'required' => false,
    'numeric' => false,   // digits-only (pincode); strips other chars
    'mobile' => false,    // Indian mobile: 10 digits, first digit 6-9
    'maxlength' => null,
])
@php($maxlength = $mobile ? 10 : $maxlength)

<div class="field">
    @if ($label)
        <label class="field__label" for="{{ $name }}">{{ $label }}@if($required) <span aria-hidden="true" style="color:var(--danger)">*</span>@endif</label>
    @endif

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ ($numeric || $mobile) ? 'tel' : $type }}"
        value="{{ old($name, $value) }}"
        @if($required) required @endif
        @if($mobile) inputmode="numeric" pattern="[6-9][0-9]{9}" title="Enter a 10-digit Indian mobile number starting with 6-9" oninput="this.value=this.value.replace(/\D/g,'').replace(/^[0-5]+/,'').slice(0,10)" @elseif($numeric) inputmode="numeric" pattern="[0-9]*" oninput="this.value=this.value.replace(/[^0-9]/g,'')" @endif
        @if($maxlength) maxlength="{{ $maxlength }}" @endif
        @error($name) aria-invalid="true" @enderror
        {{ $attributes->merge(['class' => 'input']) }}
    >

    @if ($hint)
        <span class="field__hint">{{ $hint }}</span>
    @endif
    @error($name)
        <span class="field__error">{{ $message }}</span>
    @enderror
</div>
