@props(['options' => [], 'placeholder' => 'Search…', 'label' => null])

<div class="field">
    @if ($label)<span class="field__label">{{ $label }}</span>@endif
    <div class="combobox" x-data="{
            open: false,
            q: '',
            options: {{ \Illuminate\Support\Js::from($options) }},
            value: @entangle($attributes->wire('model')),
            get selectedLabel() { return this.options[this.value] ?? ''; },
            get filtered() {
                const q = this.q.toLowerCase();
                return Object.entries(this.options).filter(([k, v]) => String(v).toLowerCase().includes(q));
            },
            pick(k) { this.value = k; this.open = false; this.q = ''; },
        }" @click.outside="open = false">
        <input type="text" class="input"
               :value="open ? q : selectedLabel"
               @focus="open = true; q = ''"
               @input="q = $event.target.value; open = true"
               @keydown.escape="open = false"
               placeholder="{{ $placeholder }}"
               autocomplete="off">
        <ul class="combobox__list" x-show="open" x-cloak>
            <template x-for="[k, v] in filtered" :key="k">
                <li class="combobox__opt" @click="pick(k)" x-text="v"></li>
            </template>
            <li class="combobox__empty" x-show="filtered.length === 0">No matches</li>
        </ul>
    </div>
</div>
