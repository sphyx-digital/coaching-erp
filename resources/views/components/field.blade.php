@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => '',
    'hint' => null,
    'required' => false,
])

<div class="field">
    @if ($label)
        <label class="field__label" for="{{ $name }}">{{ $label }}@if($required) <span aria-hidden="true" style="color:var(--danger)">*</span>@endif</label>
    @endif

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        @if($required) required @endif
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
