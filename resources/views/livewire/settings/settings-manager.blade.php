<div class="container-narrow">
    <x-page-header title="Settings">
        <x-slot:actions>
            <x-pill variant="info">Institute Admin</x-pill>
        </x-slot:actions>
    </x-page-header>

    @if ($saved)
        <div class="pill pill--success" style="margin-bottom: var(--space-4);"><span class="pill__dot"></span> Settings saved</div>
    @endif

    <form wire:submit="save" class="stack">
        <x-card title="Institute identity">
            <x-field name="institute_name" label="Institute name" wire:model="institute_name" required />
            <x-field name="gstin" label="GSTIN" wire:model="gstin" hint="15 characters; printed on fee receipts" />
        </x-card>

        <x-card title="Branding">
            <p class="field__hint" style="margin-bottom: var(--space-4);">Two colours: a decorative brand hue, and a darker action colour used behind white text. The action colour is checked for WCAG AA contrast before saving.</p>
            <div class="grid-cards">
                <div>
                    <x-field name="brand_hue" label="Brand hue (decorative)" type="text" wire:model="brand_hue" />
                    <span class="skeleton" style="display:block; height:28px; border-radius: var(--radius-md); background: {{ $brand_hue }}; animation:none;"></span>
                </div>
                <div>
                    <x-field name="action_color" label="Action colour (AA on white)" type="text" wire:model="action_color" />
                    <span style="display:block; height:28px; border-radius: var(--radius-md); background: {{ $action_color }};"></span>
                </div>
            </div>
        </x-card>

        <x-card title="Features">
            @foreach ($features as $flag => $enabled)
                <label style="display:flex; align-items:center; gap: var(--space-3); min-height: var(--tap-min);">
                    <input type="checkbox" wire:model="features.{{ $flag }}">
                    <span>{{ ucwords(str_replace('_', ' ', $flag)) }}</span>
                </label>
            @endforeach
        </x-card>

        <div>
            <x-btn type="submit" variant="primary">Save settings</x-btn>
        </div>
    </form>
</div>
