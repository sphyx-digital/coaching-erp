{{-- App shell navigation rail: icon-first, collapsible. --}}
@php($u = auth()->user())
@php($link = fn ($path) => request()->is($path) ? 'aria-current="page"' : '')
<nav class="nav-rail" aria-label="Main">
    <div class="nav-rail__brand">
        <span class="nav-rail__logo" aria-hidden="true">
            @if (! empty($branding['logo']))<img src="{{ $branding['logo'] }}" alt="">@else{{ strtoupper(mb_substr($branding['name'], 0, 1)) }}@endif
        </span>
        <span class="nav-rail__name">{{ $branding['name'] }}</span>
        <button type="button" class="nav-rail__toggle" @click="collapsed = !collapsed" :aria-pressed="collapsed" aria-label="Collapse menu" title="Collapse menu">
            <x-icon name="chevron" />
        </button>
    </div>

    <a class="nav-rail__link" href="{{ url('/dashboard') }}" {!! $link('dashboard') !!} title="Dashboard"><x-icon name="dashboard" /><span class="nav-rail__label">Dashboard</span></a>

    @if ($u && ! $u->isPortalUser())
        <a class="nav-rail__link" href="{{ url('/approvals') }}" {!! $link('approvals') !!} title="Approvals"><x-icon name="attendance" /><span class="nav-rail__label">Approvals</span></a>
    @endif
    @if ($u?->can('report.view'))
        <a class="nav-rail__link" href="{{ url('/reports') }}" {!! $link('reports') !!} title="Reports"><x-icon name="dashboard" /><span class="nav-rail__label">Reports</span></a>
    @endif

    @if ($u?->can('enquiry.view') || $u?->can('admission.view'))
        <div class="nav-rail__section">Admissions</div>
        @if ($u?->can('enquiry.view'))<a class="nav-rail__link" href="{{ url('/enquiries') }}" {!! $link('enquiries') !!} title="Enquiries"><x-icon name="enquiry" /><span class="nav-rail__label">Enquiries</span></a>@endif
        @if ($u?->can('admission.view'))<a class="nav-rail__link" href="{{ url('/admissions') }}" {!! $link('admissions') !!} title="Admissions"><x-icon name="admission" /><span class="nav-rail__label">Admissions</span></a>@endif
    @endif

    @if ($u?->can('batch.view'))
        <div class="nav-rail__section">Academics</div>
        <a class="nav-rail__link" href="{{ url('/batches') }}" {!! $link('batches') !!} title="Batches"><x-icon name="batch" /><span class="nav-rail__label">Batches</span></a>
        <a class="nav-rail__link" href="{{ url('/timetable') }}" {!! $link('timetable') !!} title="Timetable"><x-icon name="timetable" /><span class="nav-rail__label">Timetable</span></a>
    @endif

    @if ($u?->can('attendance.view'))
        <a class="nav-rail__link" href="{{ url('/attendance') }}" {!! $link('attendance') !!} title="Attendance"><x-icon name="attendance" /><span class="nav-rail__label">Attendance</span></a>
    @endif

    @if ($u?->can('assessment.view'))
        <a class="nav-rail__link" href="{{ url('/assessments') }}" {!! $link('assessments') !!} title="Assessments"><x-icon name="assessment" /><span class="nav-rail__label">Assessments</span></a>
    @endif

    @if ($u?->can('fee.view'))
        <div class="nav-rail__section">Finance</div>
        <a class="nav-rail__link" href="{{ url('/fees') }}" {!! $link('fees') !!} title="Fees & payments"><x-icon name="fees" /><span class="nav-rail__label">Fees & payments</span></a>
        <a class="nav-rail__link" href="{{ url('/fees/setup') }}" {!! $link('fees/setup') !!} title="Fee setup"><x-icon name="course" /><span class="nav-rail__label">Fee setup</span></a>
        <a class="nav-rail__link" href="{{ url('/overrides') }}" {!! $link('overrides') !!} title="Override log"><x-icon name="assessment" /><span class="nav-rail__label">Override log</span></a>
    @endif

    @if ($u?->hasAllBranchAccess())
        <div class="nav-rail__section">Organisation</div>
        <a class="nav-rail__link" href="{{ url('/branches') }}" {!! $link('branches') !!} title="Branches"><x-icon name="branch" /><span class="nav-rail__label">Branches</span></a>
        <a class="nav-rail__link" href="{{ url('/courses') }}" {!! $link('courses') !!} title="Courses"><x-icon name="course" /><span class="nav-rail__label">Courses</span></a>
        <a class="nav-rail__link" href="{{ url('/sessions') }}" {!! $link('sessions') !!} title="Sessions"><x-icon name="session" /><span class="nav-rail__label">Sessions</span></a>
        <a class="nav-rail__link" href="{{ url('/staff') }}" {!! $link('staff') !!} title="Staff"><x-icon name="staff" /><span class="nav-rail__label">Staff</span></a>
        <a class="nav-rail__link" href="{{ url('/import') }}" {!! $link('import') !!} title="Import & cutover"><x-icon name="course" /><span class="nav-rail__label">Import</span></a>
    @endif

    @if ($u?->can('settings.view'))
        <div class="nav-rail__section">System</div>
        <a class="nav-rail__link" href="{{ url('/settings') }}" {!! $link('settings') !!} title="Settings"><x-icon name="settings" /><span class="nav-rail__label">Settings</span></a>
        <a class="nav-rail__link" href="{{ url('/messages') }}" {!! $link('messages') !!} title="Message delivery"><x-icon name="enquiry" /><span class="nav-rail__label">Messages</span></a>
        <a class="nav-rail__link" href="{{ url('/ui') }}" {!! $link('ui') !!} title="Components"><x-icon name="components" /><span class="nav-rail__label">Components</span></a>
    @endif
</nav>
