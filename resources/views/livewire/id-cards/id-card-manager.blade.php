<div class="stack">
    <x-page-header title="ID cards" />
    <x-card>
        <p class="field__hint" style="margin-bottom: var(--space-3);">Generate print-ready student ID cards in your institute branding. Filter by branch and batch, then open the print sheet — cards are laid out for a standard A4 page.</p>

        <div class="toolbar" style="gap: var(--space-3); flex-wrap: wrap;">
            <div class="field" style="min-width: 200px;">
                <label class="field__label" for="branch">Branch</label>
                <select id="branch" class="select" wire:model.live="branchId">
                    <option value="">All branches</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="min-width: 220px;">
                <label class="field__label" for="batch">Batch</label>
                <select id="batch" class="select" wire:model.live="batchId">
                    <option value="">All active students</option>
                    @foreach ($batches as $bt)
                        <option value="{{ $bt->id }}">{{ $bt->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap: var(--space-3); margin-top: var(--space-4);">
            <a class="btn btn--primary" href="{{ $this->sheetUrl() }}" target="_blank" rel="noopener"
               @disabled($count === 0)>
                Open print sheet ({{ $count }} {{ \Illuminate\Support\Str::plural('card', $count) }})
            </a>
            <span class="field__hint">Opens in a new tab. Use your browser's Print (Ctrl/Cmd+P) to print or save as PDF.</span>
        </div>

        @if ($count === 0)
            <div style="margin-top: var(--space-4);">
                <x-state title="No students match">Pick a branch or batch with active students to generate cards.</x-state>
            </div>
        @endif
    </x-card>
</div>
