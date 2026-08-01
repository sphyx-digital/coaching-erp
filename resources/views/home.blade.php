@extends('layouts.auth')

@section('title', 'Welcome')

@section('content')
    <h1 style="font-size: var(--text-xl); margin-bottom: var(--space-2);">{{ $branding['name'] }}</h1>
    <p style="color: var(--text-muted); font-size: var(--text-sm); margin-bottom: var(--space-5);">
        Sign in to manage enquiries, admissions, batches, fees, attendance and results.
    </p>

    <a class="btn btn--primary" href="{{ route('login') }}" style="width:100%;">Sign in</a>
@endsection
