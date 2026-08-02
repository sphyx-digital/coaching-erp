<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Auth\Totp;
use Illuminate\Support\Str;

/**
 * Two-factor lifecycle: provision a secret, confirm it with a live code, verify
 * codes (TOTP or single-use recovery codes) at login, and disable.
 */
class TwoFactorService
{
    public function __construct(private AuditLogger $audit) {}

    /** Provision an (unconfirmed) secret so the user can scan and confirm. */
    public function generateSecret(User $user): string
    {
        $secret = Totp::generateSecret();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return $secret;
    }

    /** Confirm setup: the code must validate against the pending secret. */
    public function confirm(User $user, string $code): bool
    {
        if (! $user->two_factor_secret || ! Totp::verify($user->two_factor_secret, $code)) {
            return false;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => $this->newRecoveryCodes(),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->audit->log('2fa.enabled', $user);

        return true;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->audit->log('2fa.disabled', $user);
    }

    /** @return array<int,string> */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->newRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return $codes;
    }

    /**
     * Verify a login challenge: a valid TOTP, or a single-use recovery code
     * (which is then consumed).
     */
    public function verifyChallenge(User $user, string $code): bool
    {
        if (! $user->hasTwoFactorEnabled()) {
            return false;
        }

        if (Totp::verify($user->two_factor_secret, $code)) {
            return true;
        }

        $codes = $user->two_factor_recovery_codes ?? [];
        $normalized = strtoupper(trim($code));
        if (in_array($normalized, $codes, true)) {
            $user->forceFill([
                'two_factor_recovery_codes' => array_values(array_diff($codes, [$normalized])),
            ])->save();
            $this->audit->log('2fa.recovery_used', $user);

            return true;
        }

        return false;
    }

    /** @return array<int,string> */
    private function newRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => strtoupper(Str::random(5).'-'.Str::random(5)))
            ->all();
    }
}
