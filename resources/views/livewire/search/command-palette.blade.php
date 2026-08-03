<div>
    <div x-data="{
            open: false,
            active: 0,
            openPalette() { this.open = true; this.active = 0; $nextTick(() => $refs.input && $refs.input.focus()); },
            items() { return Array.from(this.$root.querySelectorAll('.cmd-item')); },
            move(d) { const n = this.items().length; if (!n) return; this.active = (this.active + d + n) % n; this.highlight(); },
            highlight() { this.items().forEach((el,i) => el.classList.toggle('is-active', i === this.active)); const el = this.items()[this.active]; if (el) el.scrollIntoView({block:'nearest'}); },
            go() { const el = this.items()[this.active]; if (el) window.location.href = el.getAttribute('href'); },
         }"
         @keydown.window.meta.k.prevent="openPalette()"
         @keydown.window.ctrl.k.prevent="openPalette()"
         @keydown.window.escape="open = false"
         x-on:cmdk-open.window="openPalette()">

        <template x-if="open">
            <div class="cmd-overlay" @click.self="open = false" x-cloak>
                <div class="cmd" role="dialog" aria-modal="true" aria-label="Search"
                     @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter.prevent="go()">
                    <div class="cmd__search">
                        <x-icon name="enquiry" />
                        <input x-ref="input" type="search" class="cmd__input" placeholder="Search students, enquiries, invoices, staff…"
                               wire:model.live.debounce.250ms="q" @input="active = 0"
                               autocomplete="off" spellcheck="false">
                        <kbd class="cmd__kbd">Esc</kbd>
                    </div>

                    <div class="cmd__results" wire:key="cmd-results">
                        @forelse ($groups as $group)
                            <div class="cmd__group-title">{{ $group['type'] }}</div>
                            @foreach ($group['items'] as $item)
                                <a href="{{ $item['url'] }}" class="cmd-item">
                                    <span class="cmd-item__icon"><x-icon name="{{ $group['icon'] }}" width="16" height="16" /></span>
                                    <span class="cmd-item__body">
                                        <span class="cmd-item__label">{{ $item['label'] }}</span>
                                        @if ($item['sub'])<span class="cmd-item__sub">{{ $item['sub'] }}</span>@endif
                                    </span>
                                    <span class="cmd-item__type">{{ $group['type'] }}</span>
                                </a>
                            @endforeach
                        @empty
                            <div class="cmd__empty">
                                @if (strlen($q) >= 2)
                                    No matches for “{{ $q }}”.
                                @else
                                    Type at least 2 characters. Search across students, enquiries, invoices, receipts, batches, staff, courses and branches.
                                @endif
                            </div>
                        @endforelse
                    </div>

                    <div class="cmd__foot">
                        <span><kbd class="cmd__kbd">↑</kbd><kbd class="cmd__kbd">↓</kbd> navigate</span>
                        <span><kbd class="cmd__kbd">↵</kbd> open</span>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
