@extends('layouts.auth')
@section('title', 'Set new password')

@section('content')
<h1 style="font-size: var(--text-xl); margin-bottom: var(--space-5);">Set a new password</h1>

<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <label style="display:block; margin-bottom: var(--space-4);">
        <span style="display:block; font-size: var(--text-sm); font-weight: var(--weight-medium); margin-bottom: var(--space-2);">Email</span>
        <input type="email" name="email" value="{{ old('email', $request->email) }}" required autocomplete="username"
               style="width:100%; min-height: var(--tap-min); padding: 0 var(--space-3); border:1px solid var(--border-strong); border-radius: var(--radius-md); font-size: var(--text-base);">
        @error('email')<span style="display:block; color: var(--danger); font-size: var(--text-xs); margin-top: var(--space-1);">{{ $message }}</span>@enderror
    </label>

    <label style="display:block; margin-bottom: var(--space-4);">
        <span style="display:block; font-size: var(--text-sm); font-weight: var(--weight-medium); margin-bottom: var(--space-2);">New password</span>
        <input type="password" name="password" required autocomplete="new-password"
               style="width:100%; min-height: var(--tap-min); padding: 0 var(--space-3); border:1px solid var(--border-strong); border-radius: var(--radius-md); font-size: var(--text-base);">
        @error('password')<span style="display:block; color: var(--danger); font-size: var(--text-xs); margin-top: var(--space-1);">{{ $message }}</span>@enderror
    </label>

    <label style="display:block; margin-bottom: var(--space-5);">
        <span style="display:block; font-size: var(--text-sm); font-weight: var(--weight-medium); margin-bottom: var(--space-2);">Confirm password</span>
        <input type="password" name="password_confirmation" required autocomplete="new-password"
               style="width:100%; min-height: var(--tap-min); padding: 0 var(--space-3); border:1px solid var(--border-strong); border-radius: var(--radius-md); font-size: var(--text-base);">
    </label>

    <button type="submit" class="btn btn--primary" style="width:100%;">Save new password</button>
</form>
@endsection
