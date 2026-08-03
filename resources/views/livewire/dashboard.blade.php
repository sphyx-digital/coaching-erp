<div class="stack" style="gap: var(--space-4);">
    <div class="page-header">
        <h1 class="page-header__title">Dashboard</h1>
        <div style="display:flex; gap: var(--space-2); align-items:center; flex-wrap:wrap;">
            <span class="field__hint">{{ $user->name }}</span>
            @foreach ($user->getRoleNames() as $r)<x-pill variant="info">{{ $r }}</x-pill>@endforeach
            <form method="POST" action="{{ route('logout') }}">@csrf<x-btn type="submit" size="sm" variant="secondary">Sign out</x-btn></form>
        </div>
    </div>

    {{-- Date-range toggle --}}
    <div style="display:flex; align-items:center; gap: var(--space-3); flex-wrap:wrap;">
        <div class="range-toggle" role="group" aria-label="Date range">
            @foreach (['today' => 'Today', '7d' => '7 days', '30d' => '30 days', 'month' => 'This month', 'quarter' => 'Quarter', 'year' => 'This year'] as $key => $label)
                <button type="button" wire:click="setRange('{{ $key }}')" aria-pressed="{{ $range === $key ? 'true' : 'false' }}">{{ $label }}</button>
            @endforeach
        </div>
        <div style="display:flex; align-items:center; gap:6px;">
            <input type="date" class="input" style="max-width:150px;" wire:model.live="from" aria-label="From date">
            <span class="field__hint">to</span>
            <input type="date" class="input" style="max-width:150px;" wire:model.live="to" aria-label="To date">
        </div>
        <span class="field__hint" style="margin-left:auto;">{{ $rangeLabel }}</span>
    </div>

    {{-- KPI strip --}}
    <div class="kpi-strip">
        @php($arrow = fn ($d) => $d > 0 ? '▲' : ($d < 0 ? '▼' : '—'))
        @php($cls = fn ($d) => $d > 0 ? 'kpi__delta--up' : ($d < 0 ? 'kpi__delta--down' : ''))
        @can('fee.view')
            <div class="kpi kpi--trend">
                <div class="kpi__label">Collected</div>
                <div class="kpi__value">{{ paise_to_rupees($kpis['collected']) }}
                    <span class="kpi__delta {{ $cls($kpis['collected_delta']) }}">{{ $arrow($kpis['collected_delta']) }} {{ abs($kpis['collected_delta']) }}%</span>
                </div>
                <div class="kpi__hint">vs previous period</div>
            </div>
            <x-kpi label="Outstanding" :value="paise_to_rupees($kpis['outstanding'])" />
        @endcan
        @can('admission.view')
            <div class="kpi kpi--trend">
                <div class="kpi__label">New admissions</div>
                <div class="kpi__value">{{ number_format($kpis['new_admissions']) }}
                    <span class="kpi__delta {{ $cls($kpis['new_admissions_delta']) }}">{{ $arrow($kpis['new_admissions_delta']) }} {{ abs($kpis['new_admissions_delta']) }}%</span>
                </div>
                <div class="kpi__hint">in range</div>
            </div>
            <x-kpi label="Active students" :value="number_format($kpis['active_students'])" />
        @endcan
        @can('enquiry.view')
            <div class="kpi kpi--trend">
                <div class="kpi__label">New enquiries</div>
                <div class="kpi__value">{{ number_format($kpis['new_enquiries']) }}
                    <span class="kpi__delta {{ $cls($kpis['new_enquiries_delta']) }}">{{ $arrow($kpis['new_enquiries_delta']) }} {{ abs($kpis['new_enquiries_delta']) }}%</span>
                </div>
                <div class="kpi__hint">in range</div>
            </div>
        @endcan
        @can('attendance.view')<x-kpi label="Attendance" :value="$kpis['attendance'].'%'" hint="overall" />@endcan
    </div>

    {{-- Charts --}}
    <div class="chart-grid">
        @can('fee.view')
            <x-chart class="span-8" wire:key="c-collections-{{ $range }}-{{ $from }}-{{ $to }}" title="Collections over time" :config="$charts['collections']" :height="300" />
            <x-chart class="span-4" wire:key="c-mode-{{ $range }}-{{ $from }}-{{ $to }}" title="Collections by mode" :config="$charts['mode']" :height="300" />
        @endcan
        @can('enquiry.view')
            <x-chart class="span-6" wire:key="c-trend-{{ $range }}-{{ $from }}-{{ $to }}" title="Enquiries vs admissions" :config="$charts['funnelTrend']" />
            <x-chart class="span-6" wire:key="c-funnel" title="Enquiry funnel" :config="$charts['funnel']" />
        @endcan
        @can('fee.view')
            <x-chart class="span-6" wire:key="c-ageing" title="Outstanding ageing" hint="by invoice age" :config="$charts['ageing']" />
        @endcan
        @can('attendance.view')
            <x-chart class="span-6" wire:key="c-att" title="Attendance by batch" :config="$charts['attendance']" />
        @endcan
    </div>

    {{-- Tables --}}
    <div class="grid-cards" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); align-items:start;">
        @can('admission.view')
            <x-card title="Recent admissions">
                @if ($recentAdmissions->isEmpty())
                    <x-state title="No admissions yet"><a href="{{ url('/admissions') }}">Admit a student</a></x-state>
                @else
                    <x-data-table :head="['Student', 'Course', 'Status']">
                        @foreach ($recentAdmissions as $e)
                            <tr class="is-clickable" tabindex="0" onclick="location.href='{{ url('/admissions?student='.$e->student_id) }}'">
                                <td>{{ $e->student?->name }}</td><td>{{ $e->course?->name }}</td>
                                <td><x-pill :variant="$e->status->pillVariant()">{{ $e->status->label() }}</x-pill></td></tr>
                        @endforeach
                    </x-data-table>
                @endif
            </x-card>
        @endcan

        @can('fee.view')
            <x-card title="Recent payments">
                @if ($recentPayments->isEmpty())
                    <x-state title="No payments yet"><a href="{{ url('/fees') }}">Record a payment</a></x-state>
                @else
                    <x-data-table :head="['Receipt', 'Student', ['label' => 'Amount', 'num' => true]]">
                        @foreach ($recentPayments as $p)
                            <tr class="is-clickable" tabindex="0" onclick="window.open('{{ route('receipts.show', $p->id) }}','_blank')">
                                <td>{{ $p->receipt_number }}</td><td>{{ $p->student?->name }}</td>
                                <td class="num">{{ paise_to_rupees($p->amount) }}</td></tr>
                        @endforeach
                    </x-data-table>
                @endif
            </x-card>
        @endcan

        @can('enquiry.view')
            <x-card title="Follow-ups due today">
                @if ($dueFollowUps->isEmpty())
                    <x-state title="Nothing due">You're all caught up.</x-state>
                @else
                    <x-data-table :head="['Enquiry', 'Name', 'Course']">
                        @foreach ($dueFollowUps as $e)
                            <tr class="is-clickable" tabindex="0" onclick="location.href='{{ url('/enquiries?enquiry='.$e->id) }}'">
                                <td>{{ $e->enquiry_number }}</td><td>{{ $e->name }}</td><td>{{ $e->course?->name ?: '—' }}</td></tr>
                        @endforeach
                    </x-data-table>
                @endif
            </x-card>
        @endcan
    </div>
</div>
