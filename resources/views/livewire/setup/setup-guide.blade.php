<div class="stack" style="max-width:900px; margin:0 auto; width:100%;">
    <x-page-header title="Get started" />

    {{-- Progress --}}
    <x-card>
        <div style="display:flex; justify-content:space-between; align-items:baseline; gap:var(--space-3); flex-wrap:wrap;">
            <div>
                <h2 style="margin:0; font-size:var(--text-lg);">{{ $progress['complete'] ? "You're ready to run your institute 🎉" : 'Set up your institute' }}</h2>
                <p class="field__hint" style="margin:4px 0 0;">
                    @if ($progress['complete'])
                        Core setup is done. Capture your first enquiry and let the workflow do the rest.
                    @else
                        {{ $progress['done'] }} of {{ $progress['total'] }} essential steps complete — finish these to start enrolling students.
                    @endif
                </p>
            </div>
            <div style="font-family:var(--font-heading); font-weight:700; font-size:var(--text-2xl); color:var(--action);">{{ $progress['percent'] }}%</div>
        </div>
        <div class="setup-progress"><span style="width: {{ $progress['percent'] }}%"></span></div>
        @if ($progress['complete'])
            <div style="margin-top:var(--space-3); display:flex; gap:var(--space-2); flex-wrap:wrap;">
                <a class="btn btn--primary" href="{{ url('/enquiries') }}">Capture your first enquiry</a>
                <a class="btn btn--secondary" href="{{ url('/dashboard') }}">Go to dashboard</a>
            </div>
        @endif
    </x-card>

    {{-- Checklist --}}
    <div class="stack" style="gap:var(--space-2);">
        @foreach ($steps as $i => $s)
            <div class="setup-step {{ $s['done'] ? 'is-done' : '' }}">
                <div class="setup-step__mark">{!! $s['done'] ? '&#10003;' : ($i + 1) !!}</div>
                <div class="setup-step__body">
                    <div class="setup-step__title">
                        {{ $s['title'] }}
                        {!! $s['optional'] ? '<span class="setup-step__opt">Optional</span>' : '' !!}
                        @if ($s['done'])
                            <x-pill variant="success">Done</x-pill>
                        @endif
                    </div>
                    <p class="setup-step__desc">{{ $s['desc'] }}</p>
                </div>
                <div class="setup-step__action">
                    <a class="btn btn--sm {{ $s['done'] ? 'btn--secondary' : 'btn--primary' }}" href="{{ url($s['url']) }}">{{ $s['done'] ? 'Review' : $s['cta'] }}</a>
                </div>
            </div>
        @endforeach
    </div>

    {{-- How it works: the operational flow --}}
    <x-card title="How it flows, day to day">
        <p class="field__hint" style="margin:0 0 var(--space-4);">Once set up, your institute runs on one connected pipeline — click any stage to jump in.</p>
        <div class="flow-map">
            @php($flow = [
                ['Enquiry', 'enquiry', '/enquiries', 'Capture & follow up on leads'],
                ['Admission', 'admission', '/admissions', 'Convert to an enrolled student'],
                ['Batch', 'batch', '/batches', 'Assign to a class & timetable'],
                ['Attendance', 'attendance', '/attendance', 'Mark daily attendance'],
                ['Fees', 'fees', '/fees', 'Invoice & collect (GST-ready)'],
                ['Assessments', 'assessment', '/assessments', 'Marks & report cards'],
                ['Portal', 'components', '/dashboard', 'Parents & students stay informed'],
            ])
            @php($lastFlow = count($flow) - 1)
            @foreach ($flow as $idx => [$label, $icon, $url, $desc])
                <a class="flow-node" href="{{ url($url) }}">
                    <span class="flow-node__icon"><x-icon name="{{ $icon }}" width="20" height="20" /></span>
                    <span class="flow-node__label">{{ $label }}</span>
                    <span class="flow-node__desc">{{ $desc }}</span>
                </a>
                @unless ($idx === $lastFlow)
                    <span class="flow-arrow">&rsaquo;</span>
                @endunless
            @endforeach
        </div>
    </x-card>
</div>
