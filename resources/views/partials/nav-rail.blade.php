{{-- App shell navigation rail. Modules light up phase by phase. --}}
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

    <a class="nav-rail__link" href="{{ url('/') }}" aria-current="page">Dashboard</a>

    <div class="nav-rail__section">Coming online by phase</div>
    <a class="nav-rail__link" href="#" aria-disabled="true">Enquiries</a>
    <a class="nav-rail__link" href="#" aria-disabled="true">Admissions</a>
    <a class="nav-rail__link" href="#" aria-disabled="true">Batches</a>
    <a class="nav-rail__link" href="#" aria-disabled="true">Fees</a>
    <a class="nav-rail__link" href="#" aria-disabled="true">Attendance</a>
    <a class="nav-rail__link" href="#" aria-disabled="true">Assessments</a>

    <div class="nav-rail__section">System</div>
    <a class="nav-rail__link" href="#" aria-disabled="true">Settings</a>
</nav>
