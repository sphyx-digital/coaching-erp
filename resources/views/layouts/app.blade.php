<!DOCTYPE html>
<html lang="en" data-brand="client"
      style="--brand-hue: {{ $branding['brand_hue'] }}; --action: {{ $branding['action_color'] }}; --action-hover: {{ $branding['action_color'] }};">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1"><!-- zoom never disabled -->
    <meta name="theme-color" content="{{ $branding['action_color'] }}">
    <title>@yield('title', 'Dashboard') · {{ $branding['name'] }}</title>

    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app-shell">
    @include('partials.nav-rail')

    <div class="app-main">
        <header class="topbar">
            <div class="topbar__title">@yield('title', 'Dashboard')</div>
            <div class="topbar__right">
                @yield('topbar')
                <span class="pill pill--info"><span class="pill__dot"></span> Phase 0</span>
            </div>
        </header>

        <main class="app-content" id="main">
            @hasSection('content')
                @yield('content')
            @else
                {{ $slot ?? '' }}
            @endif
        </main>
    </div>
</div>
</body>
</html>
