<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request)
    {
        abort_unless($request->session()->has('2fa.user_id'), 403);

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $userId = $request->session()->get('2fa.user_id');
        abort_unless($userId, 403);

        $request->validate(['code' => ['required', 'string']]);

        $user = User::findOrFail($userId);

        if (! $twoFactor->verifyChallenge($user, $request->string('code'))) {
            throw ValidationException::withMessages([
                'code' => 'That code is invalid or has expired.',
            ]);
        }

        $remember = (bool) $request->session()->pull('2fa.remember', false);
        $request->session()->forget('2fa.user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $home = $user->isPortalUser() ? route('portal') : route('dashboard');

        return redirect()->intended($home);
    }
}
