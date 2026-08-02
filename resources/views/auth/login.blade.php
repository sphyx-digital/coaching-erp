@extends('layouts.auth')
@section('title', 'Sign in')

@section('content')
<h1 style="font-size: var(--text-xl); margin-bottom: var(--space-2);">Sign in</h1>
<p style="color: var(--text-muted); font-size: var(--text-sm); margin-bottom: var(--space-5);">Use your institute account.</p>

@if (session('status'))
    <div class="pill pill--success" style="margin-bottom: var(--space-4);"><span class="pill__dot"></span> {{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf
    <label style="display:block; margin-bottom: var(--space-4);">
        <span style="display:block; font-size: var(--text-sm); font-weight: var(--weight-medium); margin-bottom: var(--space-2);">Email</span>
        <input id="login-email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
               style="width:100%; min-height: var(--tap-min); padding: 0 var(--space-3); border:1px solid var(--border-strong); border-radius: var(--radius-md); font-size: var(--text-base);">
        @error('email')<span style="display:block; color: var(--danger); font-size: var(--text-xs); margin-top: var(--space-1);">{{ $message }}</span>@enderror
    </label>

    <label style="display:block; margin-bottom: var(--space-4);">
        <span style="display:block; font-size: var(--text-sm); font-weight: var(--weight-medium); margin-bottom: var(--space-2);">Password</span>
        <input id="login-password" type="password" name="password" required autocomplete="current-password"
               style="width:100%; min-height: var(--tap-min); padding: 0 var(--space-3); border:1px solid var(--border-strong); border-radius: var(--radius-md); font-size: var(--text-base);">
        @error('password')<span style="display:block; color: var(--danger); font-size: var(--text-xs); margin-top: var(--space-1);">{{ $message }}</span>@enderror
    </label>

    <label style="display:flex; align-items:center; gap: var(--space-2); margin-bottom: var(--space-5); font-size: var(--text-sm);">
        <input type="checkbox" name="remember"> Remember me
    </label>

    <button type="submit" class="btn btn--primary" style="width:100%;">Sign in</button>
</form>

<p style="text-align:center; margin-top: var(--space-4); font-size: var(--text-sm);">
    <a href="{{ route('password.request') }}">Forgot your password?</a>
</p>

@if (client_setting('demo_mode'))
    <div style="margin-top: var(--space-5); border-top:1px dashed var(--border); padding-top: var(--space-4);">
        <div style="font-size: var(--text-xs); text-transform:uppercase; letter-spacing:.06em; color: var(--text-muted); margin-bottom: var(--space-3);">Demo logins — tap to fill</div>
        <div style="display:flex; flex-wrap:wrap; gap: var(--space-2);">
            @foreach (client_setting('demo_accounts', []) as $acc)
                <button type="button" class="btn btn--sm btn--secondary"
                        onclick="fillLogin('{{ $acc['email'] }}','{{ $acc['password'] }}')">
                    {{ $acc['label'] }}
                </button>
            @endforeach
        </div>
        <p style="font-size: var(--text-xs); color: var(--text-subtle); margin-top: var(--space-2);">Tap a role, then Sign in. Demo data resets periodically.</p>
    </div>
    <script>
        function fillLogin(email, password) {
            document.getElementById('login-email').value = email;
            document.getElementById('login-password').value = password;
            document.getElementById('login-password').focus();
        }
    </script>
@endif
@endsection
