@php
    // Sibling screens grouped under one section. When the current route belongs
    // to a group, its tabs render so you can switch without the sidebar.
    $u = auth()->user();
    $groups = [
        'admissions' => [
            ['label' => 'Enquiries', 'path' => 'enquiries', 'url' => '/enquiries', 'can' => 'enquiry.view'],
            ['label' => 'Admissions', 'path' => 'admissions', 'url' => '/admissions', 'can' => 'admission.view'],
            ['label' => 'ID cards', 'path' => 'id-cards', 'url' => '/id-cards', 'can' => 'admission.view'],
        ],
        'finance' => [
            ['label' => 'Payments', 'path' => 'fees', 'url' => '/fees', 'can' => 'fee.view'],
            ['label' => 'Fee setup', 'path' => 'fees/setup', 'url' => '/fees/setup', 'can' => 'fee.view'],
            ['label' => 'Overrides', 'path' => 'overrides', 'url' => '/overrides', 'can' => 'fee.view'],
        ],
        'people' => [
            ['label' => 'Directory', 'path' => 'staff', 'url' => '/staff', 'admin' => true],
            ['label' => 'Attendance', 'path' => 'staff-attendance', 'url' => '/staff-attendance', 'admin' => true],
            ['label' => 'Payroll', 'path' => 'payroll', 'url' => '/payroll', 'admin' => true],
        ],
        'teaching' => [
            ['label' => 'Assessments', 'path' => 'assessments', 'url' => '/assessments', 'can' => 'assessment.view'],
            ['label' => 'Online exams', 'path' => 'exams', 'url' => '/exams', 'can' => 'assessment.view'],
            ['label' => 'Study materials', 'path' => 'materials', 'url' => '/materials', 'can' => 'assessment.view'],
        ],
        'org' => [
            ['label' => 'Branches', 'path' => 'branches', 'url' => '/branches', 'admin' => true],
            ['label' => 'Courses', 'path' => 'courses', 'url' => '/courses', 'admin' => true],
            ['label' => 'Sessions', 'path' => 'sessions', 'url' => '/sessions', 'admin' => true],
            ['label' => 'Website', 'path' => 'website', 'url' => '/website', 'admin' => true],
            ['label' => 'Import', 'path' => 'import', 'url' => '/import', 'admin' => true],
        ],
    ];

    $activeTabs = null;
    foreach ($groups as $tabs) {
        foreach ($tabs as $t) {
            if (request()->is($t['path'])) { $activeTabs = $tabs; break 2; }
        }
    }

    $can = function ($t) use ($u) {
        if (! empty($t['admin'])) return $u?->hasAllBranchAccess();
        if (! empty($t['can'])) return $u?->can($t['can']);
        return true;
    };
    $visible = $activeTabs ? collect($activeTabs)->filter($can)->all() : [];
@endphp

@if (count($visible) > 1)
    <nav class="page-tabs" aria-label="Section">
        @foreach ($visible as $t)
            <a href="{{ url($t['url']) }}" class="page-tabs__tab" @if(request()->is($t['path'])) aria-current="page" @endif>{{ $t['label'] }}</a>
        @endforeach
    </nav>
@endif
