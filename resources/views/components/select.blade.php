@props([
    'name',
    'label' => null,
    'options' => [],       // [value => label] or [ ['value'=>, 'label'=>] ]
    'selected' => null,
    'placeholder' => null,
    'required' => false,
])

<div class="field">
    @if ($label)
        <label class="field__label" for="{{ $name }}">{{ $label }}@if($required) <span aria-hidden="true" style="color:var(--danger)">*</span>@endif</label>
    @endif

    <select id="{{ $name }}" name="{{ $name }}" @if($required) required @endif
        @error($name) aria-invalid="true" @enderror
        {{ $attributes->merge(['class' => 'select']) }}>
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $key => $opt)
            @php
                $val = is_array($opt) ? ($opt['value'] ?? $key) : $key;
                $text = is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? $key) : $opt;
            @endphp
            <option value="{{ $val }}" @selected(old($name, $selected) == $val)>{{ $text }}</option>
        @endforeach
    </select>

    @error($name)
        <span class="field__error">{{ $message }}</span>
    @enderror
</div>
