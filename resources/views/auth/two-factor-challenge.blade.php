@extends('layouts.auth')
@section('title', 'Two-factor authentication')

@section('content')
<h1 style="font-size: var(--text-xl); margin-bottom: var(--space-2);">Two-factor authentication</h1>
<p style="color: var(--text-muted); font-size: var(--text-sm); margin-bottom: var(--space-5);">Enter the 6-digit code from your authenticator app, or a recovery code.</p>

<form method="POST" action="{{ route('two-factor.challenge.store') }}">
    @csrf
    <label style="display:block; margin-bottom: var(--space-4);">
        <span style="display:block; font-size: var(--text-sm); font-weight: var(--weight-medium); margin-bottom: var(--space-2);">Authentication code</span>
        <input type="text" name="code" required autofocus autocomplete="one-time-code" inputmode="numeric"
               style="width:100%; min-height: var(--tap-min); padding: 0 var(--space-3); border:1px solid var(--border-strong); border-radius: var(--radius-md); font-size: var(--text-lg); letter-spacing:.2em;">
        @error('code')<span style="display:block; color: var(--danger); font-size: var(--text-xs); margin-top: var(--space-1);">{{ $message }}</span>@enderror
    </label>

    <button type="submit" class="btn btn--primary" style="width:100%;">Verify</button>
</form>

<p style="text-align:center; margin-top: var(--space-4); font-size: var(--text-sm);">
    <a href="{{ route('login') }}">Back to sign in</a>
</p>
@endsection
