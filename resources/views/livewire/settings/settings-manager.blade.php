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

        <x-card title="Theme & branding">
            <p class="field__hint" style="margin-bottom: var(--space-4);">Pick your institute's colours — a decorative <b>brand hue</b> and a darker <b>action colour</b> used behind white text (buttons, links). They flow through the whole app, the parent portal, invoices and ID cards. The action colour is checked for WCAG AA contrast before saving.</p>

            @php($presets = [
                ['Indigo', '#6366f1', '#4338ca'], ['Royal blue', '#3b82f6', '#1d4ed8'],
                ['Emerald', '#10b981', '#047857'], ['Teal', '#14b8a6', '#0f766e'],
                ['Violet', '#8b5cf6', '#6d28d9'], ['Rose', '#f43f5e', '#be123c'],
                ['Amber', '#f59e0b', '#b45309'], ['Slate', '#475569', '#334155'],
            ])

            <div x-data="{ brand: @entangle('brand_hue'), action: @entangle('action_color') }">
                <div class="field__label">Preset themes</div>
                <div class="theme-presets">
                    @foreach ($presets as [$name, $b, $a])
                        <button type="button" class="theme-preset" title="{{ $name }}"
                                :class="{ 'is-active': brand === '{{ $b }}' && action === '{{ $a }}' }"
                                @click="brand = '{{ $b }}'; action = '{{ $a }}'">
                            <span style="background: {{ $b }}"></span><span style="background: {{ $a }}"></span>
                        </button>
                    @endforeach
                </div>

                <div class="grid-cards" style="margin-top: var(--space-4);">
                    <div class="field">
                        <label class="field__label">Brand hue</label>
                        <div class="color-field">
                            <input type="color" x-model="brand" aria-label="Brand hue colour">
                            <input type="text" class="input" x-model="brand" maxlength="7" spellcheck="false">
                        </div>
                        @error('brand_hue')<span class="field__error">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label class="field__label">Action colour <span class="field__hint" style="display:inline;">(white-text safe)</span></label>
                        <div class="color-field">
                            <input type="color" x-model="action" aria-label="Action colour">
                            <input type="text" class="input" x-model="action" maxlength="7" spellcheck="false">
                        </div>
                        @error('action_color')<span class="field__error">{{ $message }}</span>@enderror
                    </div>
                </div>

                {{-- Live preview --}}
                <div class="field__label" style="margin-top: var(--space-4);">Live preview</div>
                <div class="theme-preview" :style="`--pv-brand:${brand}; --pv-action:${action}`">
                    <div class="theme-preview__row">
                        <button type="button" class="theme-preview__btn">Primary button</button>
                        <span class="theme-preview__pill">Active</span>
                        <a class="theme-preview__link">A link</a>
                    </div>
                    <div class="theme-preview__kpi">
                        <div class="theme-preview__kpi-label">Collected this month</div>
                        <div class="theme-preview__kpi-value">₹2,84,150</div>
                        <div class="theme-preview__bar"><span style="width:68%"></span></div>
                    </div>
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
