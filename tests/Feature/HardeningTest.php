<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Contrast;
use App\Support\ThemeGuard;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_routes_require_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/fees')->assertRedirect('/login');
        $this->get('/reports')->assertRedirect('/login');
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_a_student_cannot_reach_back_office_screens(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $student = tap(User::factory()->create())->assignRole('Student');

        $this->actingAs($student)->get('/fees')->assertForbidden();
        $this->actingAs($student)->get('/reports')->assertForbidden();
        $this->actingAs($student)->get('/settings')->assertForbidden();
    }

    public function test_login_is_rate_limited(): void
    {
        $statuses = [];
        for ($i = 0; $i < 8; $i++) {
            $statuses[] = $this->post('/login', ['email' => 'x@y.z', 'password' => 'wrong'])->getStatusCode();
        }

        $this->assertContains(429, $statuses); // throttle kicks in
    }

    public function test_core_token_pairs_meet_wcag_aa(): void
    {
        // Body text on surface, and white on the action colour.
        $this->assertGreaterThanOrEqual(4.5, Contrast::ratio('#242832', '#ffffff')); // text on surface
        $this->assertTrue(Contrast::passesAaOnWhiteText('#4338ca'));                  // default action
    }

    public function test_every_client_theme_is_contrast_guarded(): void
    {
        // Accessible client action colours pass.
        foreach (['#4338ca', '#1a7f4b', '#b3261e', '#0b5cad'] as $color) {
            ThemeGuard::verify($color);
        }
        $this->addToAssertionCount(1);

        // An inaccessible theme is rejected at boot.
        $this->expectException(RuntimeException::class);
        ThemeGuard::verify('#ffe600'); // bright yellow fails on white text
    }
}
