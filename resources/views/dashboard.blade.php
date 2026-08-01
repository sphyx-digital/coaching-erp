@extends('layouts.app')
@section('title', 'Dashboard')

@section('topbar')
    <span style="font-size: var(--text-sm); color: var(--text-muted);">{{ auth()->user()->name }}</span>
    <form method="POST" action="{{ route('logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn--sm btn--secondary">Sign out</button>
    </form>
@endsection

@section('content')
<div class="container-narrow">
    <div class="card">
        <h1 style="font-size: var(--text-2xl); margin-bottom: var(--space-2);">Welcome, {{ auth()->user()->name }}</h1>
        <p style="color: var(--text-muted);">You are signed in to {{ $branding['name'] }}.</p>
        <div style="margin-top: var(--space-4); display:flex; gap: var(--space-2); flex-wrap: wrap;">
            @foreach (auth()->user()->getRoleNames() as $role)
                <span class="pill pill--info"><span class="pill__dot"></span> {{ $role }}</span>
            @endforeach
        </div>
    </div>
</div>
@endsection
