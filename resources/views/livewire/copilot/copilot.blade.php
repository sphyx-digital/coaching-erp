<div class="stack" style="max-width:820px; margin:0 auto; width:100%;">
    <x-page-header title="AI Copilot">
        <x-slot:actions>
            @if (count($messages))<button class="btn btn--sm btn--secondary" wire:click="clearChat">New chat</button>@endif
        </x-slot:actions>
    </x-page-header>

    @unless ($configured)
        <div class="alert" style="background:var(--warning-tint); color:var(--warning); border:1px solid color-mix(in srgb, var(--warning) 30%, transparent); border-radius:var(--radius-md); padding:var(--space-3) var(--space-4);">
            The AI copilot isn't configured yet. Add <code>ANTHROPIC_API_KEY</code> (and optionally <code>ANTHROPIC_MODEL</code>) to the environment to enable live answers. You can still explore the interface below.
        </div>
    @endunless

    <x-card>
        <div class="copilot" wire:key="copilot-thread">
            @forelse ($messages as $m)
                <div class="copilot-msg copilot-msg--{{ $m['role'] }}">
                    <div class="copilot-msg__who">{{ $m['role'] === 'user' ? 'You' : 'Copilot' }}</div>
                    <div class="copilot-msg__body">{!! nl2br(e($m['content'])) !!}</div>
                </div>
            @empty
                <div class="copilot-empty">
                    <div class="copilot-empty__title">Ask about your institute</div>
                    <p class="field__hint">Grounded in your live data — fees, enquiries, admissions, attendance and collections. Try one:</p>
                    <div class="copilot-suggestions">
                        @foreach ($suggestions as $s)
                            <button type="button" class="chip" wire:click="suggest(@js($s))">{{ $s }}</button>
                        @endforeach
                    </div>
                </div>
            @endforelse

            <div wire:loading wire:target="ask,suggest" class="copilot-msg copilot-msg--assistant">
                <div class="copilot-msg__who">Copilot</div>
                <div class="copilot-msg__body copilot-thinking"><span></span><span></span><span></span></div>
            </div>
        </div>

        <form wire:submit="ask" class="copilot-input">
            <input class="input" wire:model="question" placeholder="Ask a question about your institute…"
                   autocomplete="off" @disabled(! $configured) wire:loading.attr="disabled" wire:target="ask,suggest">
            <x-btn type="submit" variant="primary" wire:loading.attr="disabled" wire:target="ask,suggest">Ask</x-btn>
        </form>
        <p class="field__hint" style="margin-top:var(--space-2);">The copilot only sees data you're permitted to view. It can be wrong — verify important figures.</p>
    </x-card>
</div>
