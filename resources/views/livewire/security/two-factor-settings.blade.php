<div class="stack">
    <x-page-header title="Security" />

    @if (session('twofa_status'))<div class="alert alert--success" role="status">{{ session('twofa_status') }}</div>@endif

    <x-card>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
            <div>
                <h2 style="margin:0;font-size:16px;">Two-factor authentication</h2>
                <p class="field__hint" style="margin:4px 0 0;">Add a one-time code from an authenticator app to your sign-in.</p>
            </div>
            <div>
                @if ($enabled)<x-pill variant="success">Enabled</x-pill>@else<x-pill variant="muted">Disabled</x-pill>@endif
            </div>
        </div>

        {{-- Enabled state --}}
        @if ($enabled && ! $showingSecret)
            <div style="margin-top:var(--space-4);display:flex;gap:8px;flex-wrap:wrap;">
                <button class="btn" wire:click="regenerateRecoveryCodes">Regenerate recovery codes</button>
                <button class="btn btn--danger" wire:click="disable" wire:confirm="Disable two-factor authentication for your account?">Disable 2FA</button>
            </div>

        {{-- Setup state --}}
        @elseif ($showingSecret && $secret)
            <div style="margin-top:var(--space-4);border-top:1px solid var(--border);padding-top:var(--space-3);">
                <p style="font-size:14px;">1. In your authenticator app (Google Authenticator, Authy, 1Password…) add a new account and enter this key:</p>
                <div style="font-family:monospace;font-size:18px;letter-spacing:.15em;background:var(--surface-2, #f4f5f7);padding:12px 16px;border-radius:8px;margin:8px 0;word-break:break-all;">{{ $secret }}</div>
                <p class="field__hint" style="word-break:break-all;">Or use this setup link: <code>{{ $uri }}</code></p>

                <p style="font-size:14px;margin-top:var(--space-3);">2. Enter the 6-digit code it shows to confirm:</p>
                <div style="display:flex;gap:8px;align-items:flex-start;max-width:320px;">
                    <div style="flex:1;">
                        <input class="input" wire:model="confirmCode" inputmode="numeric" placeholder="123456" style="letter-spacing:.2em;">
                        @error('confirmCode')<span class="field__error">{{ $message }}</span>@enderror
                    </div>
                    <button class="btn btn--primary" wire:click="confirm">Confirm</button>
                </div>
            </div>

        {{-- Disabled state --}}
        @else
            <div style="margin-top:var(--space-4);">
                <button class="btn btn--primary" wire:click="enable">Enable two-factor authentication</button>
            </div>
        @endif

        {{-- Recovery codes, shown once after generation --}}
        @if ($freshRecoveryCodes)
            <div style="margin-top:var(--space-4);border-top:1px solid var(--border);padding-top:var(--space-3);">
                <h3 style="font-size:14px;margin:0 0 6px;">Recovery codes</h3>
                <p class="field__hint" style="margin:0 0 10px;">Store these safely. Each can be used once if you lose your device. They won't be shown again.</p>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:6px;font-family:monospace;font-size:14px;">
                    @foreach ($freshRecoveryCodes as $code)
                        <div style="background:var(--surface-2, #f4f5f7);padding:8px 12px;border-radius:6px;">{{ $code }}</div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-card>
</div>
