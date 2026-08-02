@extends('layouts.site')

@section('body')
@php($hero = client_setting('site_hero_image'))
<header class="mk-hero {{ $hero ? 'has-img' : '' }}" @if($hero) style="background-image:url('{{ $hero }}')" @endif>
    <div class="mk-wrap">
        <h1>{{ client_setting('site_headline') ?: 'Learning that changes outcomes' }}</h1>
        <p>{{ client_setting('site_subhead') ?: 'Expert faculty, small batches and a proven system — across '.max($branches->count(),1).' centre'.($branches->count()===1?'':'s').'.' }}</p>
        <div class="mk-cta">
            <a class="mk-btn mk-btn--primary" href="#enquiry">{{ client_setting('site_cta_label') ?: 'Enquire now' }}</a>
            <a class="mk-btn mk-btn--ghost" href="#courses">Explore courses</a>
        </div>
    </div>
</header>

@if (client_setting('site_about'))
<section class="mk-section">
    <div class="mk-wrap" style="max-width:760px;text-align:center;">
        <h2>About us</h2>
        <p class="lede" style="max-width:none;">{{ client_setting('site_about') }}</p>
    </div>
</section>
@endif

<section class="mk-section" id="courses" style="background: color-mix(in srgb, var(--brand-hue) 4%, var(--surface));">
    <div class="mk-wrap">
        <h2>Our courses</h2>
        <p class="lede">Programmes designed for every stage and goal.</p>
        @if ($courses->isEmpty())
            <p style="text-align:center;color:var(--text-subtle);">Courses will be listed here soon.</p>
        @else
            <div class="mk-grid">
                @foreach ($courses as $c)
                    <a class="mk-card" href="{{ url('/site/courses/'.$c->slug) }}">
                        @if($c->level)<span class="mk-tag">{{ $c->level }}</span>@endif
                        <h3>{{ $c->name }}</h3>
                        <p>{{ $c->tagline ?: \Illuminate\Support\Str::limit(strip_tags($c->description), 90) }}</p>
                        <div class="mk-meta">
                            @if($c->duration_months)<span>{{ $c->duration_months }} months</span>@endif
                            @if($c->fee_from)<span>From {{ paise_to_rupees($c->fee_from) }}</span>@endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>

<section class="mk-section" id="centres">
    <div class="mk-wrap">
        <h2>Our centres</h2>
        <p class="lede">Visit us at a centre near you.</p>
        @if ($branches->isEmpty())
            <p style="text-align:center;color:var(--text-subtle);">Centre details coming soon.</p>
        @else
            <div class="mk-grid">
                @foreach ($branches as $b)
                    <a class="mk-card" href="{{ url('/site/branches/'.$b->slug) }}">
                        <h3>{{ $b->name }}</h3>
                        <p>{{ $b->tagline ?: $b->shortAddress() }}</p>
                        <div class="mk-meta">
                            @if($b->city)<span>{{ $b->city }}</span>@endif
                            @if($b->phone)<span>{{ $b->phone }}</span>@endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>

@include('site._enquiry', ['branches' => $branches, 'courses' => $courses])
@endsection
