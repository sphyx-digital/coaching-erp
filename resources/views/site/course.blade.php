@extends('layouts.site')

@section('title', ($course->seo_title ?: $course->name).' · '.$branding['name'])
@section('meta_description', $course->seo_description ?: $course->tagline ?: \Illuminate\Support\Str::limit(strip_tags($course->description), 150))

@section('body')
@php($hero = $course->hero_image)
<header class="mk-hero {{ $hero ? 'has-img' : '' }}" @if($hero) style="background-image:url('{{ $hero }}')" @endif>
    <div class="mk-wrap">
        @if($course->level)<span class="mk-tag" style="background:rgba(255,255,255,.18);color:#fff;">{{ $course->level }}</span>@endif
        <h1>{{ $course->name }}</h1>
        <p>{{ $course->tagline ?: \Illuminate\Support\Str::limit(strip_tags($course->description), 140) }}</p>
        <div class="mk-cta"><a class="mk-btn mk-btn--primary" href="#enquiry">Enquire about this course</a></div>
    </div>
</header>

<section class="mk-section">
    <div class="mk-wrap" style="max-width:820px;">
        <div class="mk-meta" style="justify-content:center;margin-bottom:24px;font-size:14px;">
            @if($course->duration_months)<span>Duration: {{ $course->duration_months }} months</span>@endif
            @if($course->mode)<span>Mode: {{ ucfirst($course->mode) }}</span>@endif
            @if($course->fee_from)<span>Fee from: {{ paise_to_rupees($course->fee_from) }}</span>@endif
        </div>

        @if($course->description)
            <h2>Overview</h2>
            <p class="lede" style="max-width:none;">{{ $course->description }}</p>
        @endif

        <div class="mk-grid" style="grid-template-columns:repeat(2,1fr);margin-top:24px;">
            @if(!empty($course->highlights))
            <div class="mk-card">
                <h3>What you get</h3>
                <ul class="mk-list">@foreach($course->highlights as $h)<li>{{ $h }}</li>@endforeach</ul>
            </div>
            @endif
            @if(!empty($course->eligibility))
            <div class="mk-card">
                <h3>Eligibility</h3>
                <ul class="mk-list">@foreach($course->eligibility as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
            @endif
        </div>
    </div>
</section>

@include('site._enquiry', ['branches' => $branches, 'courses' => collect([$course]), 'courseId' => $course->id])
@endsection
