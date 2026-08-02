<?php

namespace App\Livewire\Security;

use App\Services\Auth\TwoFactorService;
use App\Support\Auth\Totp;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TwoFactorSettings extends Component
{
    public bool $showingSecret = false;

    public string $confirmCode = '';

    /** @var array<int,string>|null shown once, right after (re)generation */
    public ?array $freshRecoveryCodes = null;

    public function mount(): void
    {
        // Staff/back-office users only (portal 2FA can come later).
        abort_if(Auth::user()?->isPortalUser(), 403);
    }

    public function enable(): void
    {
        app(TwoFactorService::class)->generateSecret(Auth::user());
        $this->showingSecret = true;
        $this->confirmCode = '';
        $this->resetValidation();
    }

    public function confirm(): void
    {
        $this->validate(['confirmCode' => ['required', 'string']]);

        if (! app(TwoFactorService::class)->confirm(Auth::user(), $this->confirmCode)) {
            $this->addError('confirmCode', 'That code is invalid. Check the time on your device and try again.');

            return;
        }

        $this->showingSecret = false;
        $this->confirmCode = '';
        $this->freshRecoveryCodes = Auth::user()->fresh()->two_factor_recovery_codes;
        session()->flash('twofa_status', 'Two-factor authentication is now enabled.');
    }

    public function regenerateRecoveryCodes(): void
    {
        $this->freshRecoveryCodes = app(TwoFactorService::class)->regenerateRecoveryCodes(Auth::user());
        session()->flash('twofa_status', 'New recovery codes generated. Save them somewhere safe.');
    }

    public function disable(): void
    {
        app(TwoFactorService::class)->disable(Auth::user());
        $this->showingSecret = false;
        $this->freshRecoveryCodes = null;
        session()->flash('twofa_status', 'Two-factor authentication has been disabled.');
    }

    public function render()
    {
        $user = Auth::user()->fresh();
        $uri = null;
        $secret = null;
        if ($this->showingSecret && $user->two_factor_secret) {
            $secret = $user->two_factor_secret;
            $uri = Totp::provisioningUri($secret, $user->email, (string) client_setting('institute_name', 'Coaching ERP'));
        }

        return view('livewire.security.two-factor-settings', [
            'enabled' => $user->hasTwoFactorEnabled(),
            'secret' => $secret,
            'uri' => $uri,
        ]);
    }
}
