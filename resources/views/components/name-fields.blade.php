@props(['field', 'label' => 'Name', 'required' => false])

<div class="field">
    <span class="field__label">{{ $label }}@if ($required) <span aria-hidden="true" style="color:var(--danger)">*</span>@endif</span>
    <div class="name-row">
        <select class="select" wire:model="{{ $field }}_title" aria-label="Title">
            <option value="">Title</option>
            @foreach (['Mr', 'Mrs', 'Ms', 'Master', 'Miss', 'Dr'] as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
        </select>
        <input class="input" placeholder="First name" wire:model="{{ $field }}_first" aria-label="First name">
        <input class="input" placeholder="Middle" wire:model="{{ $field }}_middle" aria-label="Middle name">
        <input class="input" placeholder="Last name" wire:model="{{ $field }}_last" aria-label="Last name">
    </div>
    @error($field.'_first')<span class="field__error">{{ $message }}</span>@enderror
</div>
