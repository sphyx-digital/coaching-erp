<?php

namespace Tests\Feature;

use App\Livewire\Security\TwoFactorSettings;
use App\Models\User;
use App\Services\Auth\TwoFactorService;
use App\Support\Auth\Totp;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_totp_matches_the_rfc6238_reference_vector(): void
    {
        // RFC 6238 seed "12345678901234567890" in Base32, at T=59s (6-digit).
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $this->assertSame('287082', Totp::codeAt($secret, 59));
        $this->assertTrue(Totp::verify($secret, '287082', window: 1, timestamp: 59));
        $this->assertFalse(Totp::verify($secret, '000000', window: 1, timestamp: 59));
    }

    public function test_enable_confirm_and_verify_lifecycle(): void
    {
        $svc = app(TwoFactorService::class);
        $user = User::factory()->create();

        $secret = $svc->generateSecret($user);
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled()); // not until confirmed

        // A wrong code does not confirm.
        $this->assertFalse($svc->confirm($user->fresh(), '000000'));

        // The right code confirms and issues recovery codes.
        $this->assertTrue($svc->confirm($user->fresh(), Totp::codeAt($secret)));
        $user->refresh();
        $this->assertTrue($user->hasTwoFactorEnabled());
        $this->assertCount(8, $user->two_factor_recovery_codes);

        // Challenge accepts a live TOTP.
        $this->assertTrue($svc->verifyChallenge($user, Totp::codeAt($secret)));
    }

    public function test_recovery_code_works_once_then_is_consumed(): void
    {
        $svc = app(TwoFactorService::class);
        $user = User::factory()->create();
        $secret = $svc->generateSecret($user);
        $svc->confirm($user->fresh(), Totp::codeAt($secret));
        $user->refresh();

        $recovery = $user->two_factor_recovery_codes[0];
        $this->assertTrue($svc->verifyChallenge($user, $recovery));   // first use OK
        $this->assertFalse($svc->verifyChallenge($user->fresh(), $recovery)); // consumed
    }

    public function test_login_defers_to_challenge_when_two_factor_is_enabled(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $svc = app(TwoFactorService::class);
        $user = tap(User::factory()->create(['password' => Hash::make('secret123')]))->assignRole('Institute Admin');
        $secret = $svc->generateSecret($user);
        $svc->confirm($user->fresh(), Totp::codeAt($secret));

        // Password is correct, but login is not completed — redirected to challenge.
        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();

        // Completing the challenge logs the user in.
        $this->post('/two-factor-challenge', ['code' => Totp::codeAt($secret)])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_without_two_factor_is_unaffected(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = tap(User::factory()->create(['password' => Hash::make('secret123')]))->assignRole('Institute Admin');

        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_settings_screen_can_enable_two_factor(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(TwoFactorSettings::class)
            ->call('enable')
            ->assertSet('showingSecret', true);

        $secret = $user->fresh()->two_factor_secret;
        $component->set('confirmCode', Totp::codeAt($secret))
            ->call('confirm')
            ->assertHasNoErrors();

        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_portal_users_cannot_reach_the_security_screen(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $portal = tap(User::factory()->create())->assignRole('Student');
        Livewire::actingAs($portal)->test(TwoFactorSettings::class)->assertForbidden();
    }
}
