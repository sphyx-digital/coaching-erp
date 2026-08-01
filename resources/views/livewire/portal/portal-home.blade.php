<div class="stack">
    @include('livewire.portal.partials.switcher')

    @if (! $student)
        <x-card>
            <x-state title="No student linked yet">Ask your institute to link your account to a student. Once linked, fees, attendance and results appear here.</x-state>
        </x-card>
    @else
        @if ($feeDue > 0)
            <div class="due-banner">
                <div>
                    <div style="font-weight: var(--weight-semibold);">Fees due</div>
                    <div class="num" style="font-size: var(--text-xl); font-family: var(--font-heading);">{{ paise_to_rupees($feeDue) }}</div>
                </div>
                @if ($canPay)
                    <a class="btn btn--primary" href="{{ url('/portal/fees') }}">Pay now</a>
                @else
                    <a class="btn btn--secondary" href="{{ url('/portal/fees') }}">View fees</a>
                @endif
            </div>
        @endif

        <div class="grid-cards" style="grid-template-columns: 1fr 1fr;">
            <x-card>
                <div style="display:flex; flex-direction:column; align-items:center; gap: var(--space-2);">
                    <div class="ring" style="--pct: {{ (int) round($attendanceBp / 100) }};">
                        <div class="ring__inner num">{{ (int) round($attendanceBp / 100) }}%</div>
                    </div>
                    <div class="field__hint">Attendance</div>
                </div>
            </x-card>

            <x-card title="Latest result">
                @if ($latestResult)
                    <div class="num" style="font-size: var(--text-2xl); font-family: var(--font-heading);">{{ number_format($latestResult->percentage_bp / 100, 1) }}%</div>
                    <div style="margin-top: var(--space-2);"><x-pill variant="success">{{ $latestResult->overall_grade }}</x-pill></div>
                    <a href="{{ url('/portal/results') }}" style="font-size: var(--text-sm); display:inline-block; margin-top: var(--space-2);">See all results</a>
                @else
                    <x-state title="No results yet">Published report cards will appear here.</x-state>
                @endif
            </x-card>
        </div>

        <x-card title="Notices">
            @forelse ($notices as $n)
                <div style="padding: var(--space-2) 0; border-bottom: 1px solid var(--border);">
                    <div style="font-weight: var(--weight-medium);">{{ $n->title }}</div>
                    @if ($n->body)<div class="field__hint">{{ $n->body }}</div>@endif
                </div>
            @empty
                <x-state title="No notices">Messages from your institute will show here.</x-state>
            @endforelse
        </x-card>
    @endif
</div>
