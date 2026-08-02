<!DOCTYPE html>
<html lang="en" data-brand="client"
      style="--brand-hue: {{ $branding['brand_hue'] }}; --action: {{ $branding['action_color'] }}; --action-hover: {{ $branding['action_color'] }};">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1"><!-- zoom never disabled -->
    <meta name="theme-color" content="{{ $branding['action_color'] }}">
    <title>@yield('title', 'Home') · {{ $branding['name'] }}</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal-body">
<div class="offline-banner" id="offlineBanner" role="status" hidden>
    <span class="pill__dot"></span> You are offline — showing the last saved data.
</div>

<header class="portal-topbar">
    <span class="nav-rail__logo" aria-hidden="true">{{ strtoupper(mb_substr($branding['name'], 0, 1)) }}</span>
    <span class="portal-topbar__name">{{ $branding['name'] }}</span>
    <form method="POST" action="{{ route('logout') }}" style="margin-left:auto;">
        @csrf
        <button type="submit" class="btn btn--sm btn--secondary">Sign out</button>
    </form>
</header>

<main class="portal-main" id="main">
    @hasSection('content')
        @yield('content')
    @else
        {{ $slot ?? '' }}
    @endif
</main>

<nav class="portal-tabs" aria-label="Portal">
    @php($tab = fn ($path) => request()->is($path) ? 'aria-current="page"' : '')
    <a class="portal-tab" href="{{ url('/portal') }}" {!! $tab('portal') !!}><span>Home</span></a>
    <a class="portal-tab" href="{{ url('/portal/fees') }}" {!! $tab('portal/fees') !!}><span>Fees</span></a>
    <a class="portal-tab" href="{{ url('/portal/attendance') }}" {!! $tab('portal/attendance') !!}><span>Attendance</span></a>
    <a class="portal-tab" href="{{ url('/portal/results') }}" {!! $tab('portal/results') !!}><span>Results</span></a>
    <a class="portal-tab" href="{{ url('/portal/exams') }}" {!! $tab('portal/exams*') !!}><span>Exams</span></a>
    <a class="portal-tab" href="{{ url('/portal/timetable') }}" {!! $tab('portal/timetable') !!}><span>Timetable</span></a>
</nav>

<script>
    (function () {
        var banner = document.getElementById('offlineBanner');
        function sync() { banner.hidden = navigator.onLine; }
        window.addEventListener('online', sync);
        window.addEventListener('offline', sync);
        sync();
    })();
</script>
</body>
</html>
