@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => '',
    'hint' => null,
    'required' => false,
    'numeric' => false,   // digits-only (phone, pincode); strips other chars
    'maxlength' => null,
])

<div class="field">
    @if ($label)
        <label class="field__label" for="{{ $name }}">{{ $label }}@if($required) <span aria-hidden="true" style="color:var(--danger)">*</span>@endif</label>
    @endif

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $numeric ? 'tel' : $type }}"
        value="{{ old($name, $value) }}"
        @if($required) required @endif
        @if($numeric) inputmode="numeric" pattern="[0-9]*" oninput="this.value=this.value.replace(/[^0-9]/g,'')" @endif
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
