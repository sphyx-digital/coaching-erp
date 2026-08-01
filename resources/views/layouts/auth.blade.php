<!DOCTYPE html>
<html lang="en" data-brand="client"
      style="--brand-hue: {{ $branding['brand_hue'] }}; --action: {{ $branding['action_color'] }}; --action-hover: {{ $branding['action_color'] }};">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ $branding['action_color'] }}">
    <title>@yield('title', 'Sign in') · {{ $branding['name'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="display:grid; place-items:center; min-height:100vh; padding: var(--space-5);">
    <main style="width:100%; max-width: 400px;">
        <div style="display:flex; align-items:center; gap: var(--space-3); justify-content:center; margin-bottom: var(--space-5);">
            <span class="nav-rail__logo" aria-hidden="true">{{ strtoupper(mb_substr($branding['name'], 0, 1)) }}</span>
            <span style="font-family: var(--font-heading); font-weight:600; font-size: var(--text-lg);">{{ $branding['name'] }}</span>
        </div>

        <div class="card">
            @yield('content')
        </div>

        <p style="text-align:center; color: var(--text-subtle); font-size: var(--text-xs); margin-top: var(--space-5);">
            Coaching Institute ERP by Sphyx Digital
        </p>
    </main>
</body>
</html>
