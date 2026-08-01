@props(['name', 'label', 'type' => 'text', 'value' => '', 'autocomplete' => null, 'required' => false])
<label style="display:block; margin-bottom: var(--space-4);">
    <span style="display:block; font-size: var(--text-sm); font-weight: var(--weight-medium); margin-bottom: var(--space-2);">{{ $label }}</span>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if($required) required @endif
        style="width:100%; min-height: var(--tap-min); padding: 0 var(--space-3); border:1px solid var(--border-strong); border-radius: var(--radius-md); font-family: var(--font-body); font-size: var(--text-base); background: var(--surface);"
    >
    @error($name)
        <span style="display:block; color: var(--danger); font-size: var(--text-xs); margin-top: var(--space-1);">{{ $message }}</span>
    @enderror
</label>
