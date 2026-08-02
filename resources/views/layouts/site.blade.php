<!DOCTYPE html>
<html lang="en" data-brand="client"
      style="--brand-hue: {{ $branding['brand_hue'] }}; --action: {{ $branding['action_color'] }}; --action-hover: {{ $branding['action_color'] }};">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ $branding['action_color'] }}">
    <title>@yield('title', client_setting('site_seo_title') ?: $branding['name'])</title>
    <meta name="description" content="@yield('meta_description', client_setting('site_seo_description') ?: ($branding['name'].' — admissions, courses and centres.'))">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background: var(--surface); }
        .mk-nav { position: sticky; top: 0; z-index: 20; display: flex; align-items: center; justify-content: space-between; padding: 14px 24px; background: color-mix(in srgb, var(--surface) 85%, transparent); backdrop-filter: saturate(1.4) blur(8px); border-bottom: 1px solid var(--border); }
        .mk-brand { display: flex; align-items: center; gap: 10px; font-family: var(--font-heading); font-weight: 700; text-decoration: none; color: var(--text); }
        .mk-logo { width: 34px; height: 34px; border-radius: 9px; background: var(--brand-hue); color: #fff; display: grid; place-items: center; font-weight: 700; overflow: hidden; }
        .mk-logo img { width: 100%; height: 100%; object-fit: cover; }
        .mk-nav-links { display: flex; align-items: center; gap: 18px; }
        .mk-nav-links a { text-decoration: none; color: var(--text-muted); font-size: 14px; font-weight: 500; }
        .mk-nav-links a:hover { color: var(--text); }
        .mk-wrap { max-width: 1080px; margin: 0 auto; padding: 0 24px; }
        .mk-hero { text-align: center; padding: 72px 24px 56px; background: radial-gradient(1200px 400px at 50% -120px, color-mix(in srgb, var(--brand-hue) 16%, transparent), transparent); }
        .mk-hero.has-img { background-size: cover; background-position: center; color: #fff; position: relative; }
        .mk-hero.has-img::before { content:''; position:absolute; inset:0; background: linear-gradient(180deg, rgba(15,18,26,.55), rgba(15,18,26,.75)); }
        .mk-hero.has-img .mk-wrap { position: relative; z-index: 1; }
        .mk-hero.has-img h1, .mk-hero.has-img p { color: #fff; }
        .mk-hero h1 { font-family: var(--font-heading); font-weight: 800; font-size: clamp(30px, 5vw, 52px); line-height: 1.08; margin: 0 auto 16px; max-width: 18ch; letter-spacing: -0.02em; }
        .mk-hero p { color: var(--text-muted); font-size: clamp(16px, 2vw, 19px); max-width: 62ch; margin: 0 auto 28px; }
        .mk-cta { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .mk-btn { display: inline-flex; align-items: center; gap: 8px; min-height: 48px; padding: 0 22px; border-radius: 12px; font-weight: 600; text-decoration: none; font-size: 15px; cursor: pointer; border: 0; }
        .mk-btn--primary { background: var(--action); color: #fff; }
        .mk-btn--ghost { background: var(--surface); color: var(--text); border: 1px solid var(--border-strong); }
        .mk-section { padding: 56px 0; }
        .mk-section h2 { font-family: var(--font-heading); font-weight: 700; font-size: 28px; text-align: center; margin: 0 0 8px; }
        .mk-section .lede { text-align: center; color: var(--text-muted); max-width: 56ch; margin: 0 auto 34px; }
        .mk-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        @media (max-width: 820px) { .mk-grid { grid-template-columns: 1fr; } .mk-nav-links a:not(.mk-btn) { display: none; } }
        .mk-card { border: 1px solid var(--border); border-radius: 16px; padding: 22px; background: var(--surface); transition: transform .2s var(--ease-out), box-shadow .2s var(--ease-out); text-decoration: none; color: var(--text); display: block; }
        .mk-card:hover { transform: translateY(-3px); box-shadow: var(--elev-2); }
        .mk-card h3 { font-family: var(--font-heading); font-weight: 600; font-size: 18px; margin: 0 0 6px; }
        .mk-card p { color: var(--text-muted); font-size: 14px; margin: 0 0 8px; }
        .mk-tag { display:inline-block; font-size:12px; font-weight:600; color: var(--action); background: color-mix(in srgb, var(--brand-hue) 12%, var(--surface)); border-radius: 999px; padding: 3px 10px; margin-bottom: 10px; }
        .mk-meta { font-size: 13px; color: var(--text-subtle); display:flex; gap:14px; flex-wrap:wrap; }
        .mk-list { list-style: none; padding: 0; margin: 12px 0 0; }
        .mk-list li { padding-left: 24px; position: relative; margin-bottom: 8px; color: var(--text-muted); font-size: 14px; }
        .mk-list li::before { content:'\2713'; position:absolute; left:0; color: var(--action); font-weight:700; }
        .mk-form { max-width: 560px; margin: 0 auto; background: var(--surface); border: 1px solid var(--border); border-radius: 18px; padding: 28px; }
        .mk-form .field { margin-bottom: 14px; }
        .mk-foot { color: var(--text-subtle); font-size: 13px; padding: 40px 24px; border-top: 1px solid var(--border); }
        .mk-foot-inner { max-width:1080px; margin:0 auto; display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; }
        .mk-social a { color: var(--text-muted); text-decoration:none; margin-left: 14px; font-size:13px; }
        .alert { border-radius: 12px; padding: 14px 16px; margin-bottom: 16px; font-size: 14px; }
        .alert--success { background: color-mix(in srgb, var(--success) 14%, var(--surface)); color: var(--success); border: 1px solid color-mix(in srgb, var(--success) 30%, transparent); }
    </style>
    @stack('head')
</head>
<body>
<nav class="mk-nav">
    <a class="mk-brand" href="{{ url('/site') }}">
        <span class="mk-logo">@if(!empty($branding['logo']))<img src="{{ $branding['logo'] }}" alt="">@else{{ strtoupper(mb_substr($branding['name'],0,1)) }}@endif</span>
        {{ $branding['name'] }}
    </a>
    <div class="mk-nav-links">
        <a href="{{ url('/site') }}#courses">Courses</a>
        <a href="{{ url('/site') }}#centres">Centres</a>
        <a href="{{ url('/site') }}#enquiry" class="mk-btn mk-btn--primary" style="min-height:40px;padding:0 18px;">{{ client_setting('site_cta_label') ?: 'Enquire now' }}</a>
    </div>
</nav>

@yield('body')

<footer class="mk-foot">
    <div class="mk-foot-inner">
        <div>{{ $branding['name'] }} &nbsp;·&nbsp; @if(client_setting('site_phone')){{ client_setting('site_phone') }} &nbsp;·&nbsp; @endif@if(client_setting('site_email')){{ client_setting('site_email') }}@endif</div>
        <div class="mk-social">
            @if(client_setting('social_facebook'))<a href="{{ client_setting('social_facebook') }}" rel="noopener">Facebook</a>@endif
            @if(client_setting('social_instagram'))<a href="{{ client_setting('social_instagram') }}" rel="noopener">Instagram</a>@endif
            @if(client_setting('social_youtube'))<a href="{{ client_setting('social_youtube') }}" rel="noopener">YouTube</a>@endif
        </div>
    </div>
</footer>
</body>
</html>
