{{-- App shell navigation rail. Modules light up phase by phase. --}}
@php($u = auth()->user())
<nav class="nav-rail" aria-label="Main">
    <div class="nav-rail__brand">
        <span class="nav-rail__logo" aria-hidden="true">
            @if (! empty($branding['logo']))
                <img src="{{ $branding['logo'] }}" alt="">
            @else
                {{ strtoupper(mb_substr($branding['name'], 0, 1)) }}
            @endif
        </span>
        <span class="nav-rail__name">{{ $branding['name'] }}</span>
    </div>

    <a class="nav-rail__link" href="{{ auth()->check() ? url('/dashboard') : url('/') }}" @if(request()->is('dashboard') || request()->is('/')) aria-current="page" @endif>Dashboard</a>

    @if ($u?->can('enquiry.view') || $u?->can('admission.view'))
        <div class="nav-rail__section">Admissions funnel</div>
        @if ($u?->can('enquiry.view'))
            <a class="nav-rail__link" href="{{ url('/enquiries') }}" @if(request()->is('enquiries')) aria-current="page" @endif>Enquiries</a>
        @endif
        @if ($u?->can('admission.view'))
            <a class="nav-rail__link" href="{{ url('/admissions') }}" @if(request()->is('admissions')) aria-current="page" @endif>Admissions</a>
        @endif
    @endif

    <div class="nav-rail__section">Coming online by phase</div>
    <a class="nav-rail__link" href="#" aria-disabled="true">Batches</a>
    <a class="nav-rail__link" href="#" aria-disabled="true">Fees</a>
    <a class="nav-rail__link" href="#" aria-disabled="true">Attendance</a>
    <a class="nav-rail__link" href="#" aria-disabled="true">Assessments</a>

    @if ($u?->hasAllBranchAccess())
        <div class="nav-rail__section">Organisation</div>
        <a class="nav-rail__link" href="{{ url('/branches') }}" @if(request()->is('branches')) aria-current="page" @endif>Branches</a>
        <a class="nav-rail__link" href="{{ url('/courses') }}" @if(request()->is('courses')) aria-current="page" @endif>Courses</a>
        <a class="nav-rail__link" href="{{ url('/sessions') }}" @if(request()->is('sessions')) aria-current="page" @endif>Sessions</a>
        <a class="nav-rail__link" href="{{ url('/staff') }}" @if(request()->is('staff')) aria-current="page" @endif>Staff</a>
    @endif

    @if ($u?->can('settings.view'))
        <div class="nav-rail__section">System</div>
        <a class="nav-rail__link" href="{{ url('/settings') }}" @if(request()->is('settings')) aria-current="page" @endif>Settings</a>
        <a class="nav-rail__link" href="{{ url('/ui') }}" @if(request()->is('ui')) aria-current="page" @endif>Components</a>
    @endif
</nav>
