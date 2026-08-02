@extends('layouts.site')

@section('title', ($branch->seo_title ?: $branch->name).' · '.$branding['name'])
@section('meta_description', $branch->seo_description ?: $branch->tagline ?: $branch->shortAddress())

@section('body')
@php($hero = $branch->hero_image)
<header class="mk-hero {{ $hero ? 'has-img' : '' }}" @if($hero) style="background-image:url('{{ $hero }}')" @endif>
    <div class="mk-wrap">
        <h1>{{ $branch->name }}</h1>
        <p>{{ $branch->tagline ?: $branch->shortAddress() }}</p>
        <div class="mk-cta">
            <a class="mk-btn mk-btn--primary" href="#enquiry">Enquire about this centre</a>
            @if($branch->google_maps_url)<a class="mk-btn mk-btn--ghost" href="{{ $branch->google_maps_url }}" rel="noopener" target="_blank">Get directions</a>@endif
        </div>
    </div>
</header>

<section class="mk-section">
    <div class="mk-wrap" style="max-width:820px;">
        @if($branch->about || $branch->description)
            <h2>About this centre</h2>
            <p class="lede" style="max-width:none;">{{ $branch->about ?: $branch->description }}</p>
        @endif

        <div class="mk-grid" style="grid-template-columns:repeat(2,1fr);margin-top:24px;">
            <div class="mk-card">
                <h3>Reach us</h3>
                <p>{{ collect([$branch->address, $branch->address_line2, $branch->locality, $branch->city, $branch->state, $branch->pincode])->filter()->implode(', ') }}</p>
                <div class="mk-meta" style="margin-top:8px;">
                    @if($branch->phone)<span>Phone: {{ $branch->phone }}</span>@endif
                    @if($branch->email)<span>Email: {{ $branch->email }}</span>@endif
                </div>
            </div>
            @if(!empty($branch->amenities))
            <div class="mk-card">
                <h3>Facilities</h3>
                <ul class="mk-list">
                    @foreach($branch->amenities as $a)<li>{{ $a }}</li>@endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>
</section>

@include('site._enquiry', ['branches' => collect([$branch]), 'courses' => $courses, 'branchId' => $branch->id])
@endsection
