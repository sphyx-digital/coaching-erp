<div class="stack">
    <div class="page-header">
        <h1 class="page-header__title">Dashboard</h1>
        <div style="display:flex; gap: var(--space-2); align-items:center;">
            <span class="field__hint">{{ $user->name }}</span>
            @foreach ($user->getRoleNames() as $r)<x-pill variant="info">{{ $r }}</x-pill>@endforeach
            <form method="POST" action="{{ route('logout') }}">@csrf<x-btn size="sm" variant="secondary">Sign out</x-btn></form>
        </div>
    </div>

    <div class="grid-kpis">
        @can('admission.view')<x-kpi label="Active students" :value="number_format($kpis['students'])" />@endcan
        @can('fee.view')
            <x-kpi label="Collected (month)" :value="paise_to_rupees($kpis['collected'])" :hint="'Today '.paise_to_rupees($kpis['collected_today'])" />
            <x-kpi label="Outstanding" :value="paise_to_rupees($kpis['outstanding'])" />
        @endcan
        @can('attendance.view')<x-kpi label="Attendance" :value="$kpis['attendance'].'%'" />@endcan
        @can('enquiry.view')
            <x-kpi label="Open enquiries" :value="number_format($kpis['open_enquiries'])" :hint="'Due today '.$kpis['due_today']" />
            <x-kpi label="Conversion" :value="$kpis['conversion'].'%'" />
        @endcan
    </div>

    <div class="grid-cards" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); align-items:start;">
        @can('admission.view')
            <x-card title="Recent admissions">
                @if ($recentAdmissions->isEmpty())
                    <x-state title="No admissions yet"><a href="{{ url('/admissions') }}">Admit a student</a></x-state>
                @else
                    <x-data-table :head="['Student', 'Course', 'Status']">
                        @foreach ($recentAdmissions as $e)
                            <tr><td>{{ $e->student?->name }}</td><td>{{ $e->course?->name }}</td>
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
                            <tr><td>{{ $p->receipt_number }}</td><td>{{ $p->student?->name }}</td>
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
                            <tr><td>{{ $e->enquiry_number }}</td><td>{{ $e->name }}</td><td>{{ $e->course?->name ?: '—' }}</td></tr>
                        @endforeach
                    </x-data-table>
                @endif
            </x-card>
        @endcan
    </div>
</div>
