<?php

namespace Tests\Feature;

use Tests\TestCase;

class SmokeTest extends TestCase
{
    public function test_home_route_boots_inside_the_app_shell_with_branding(): void
    {
        config()->set('client.institute_name', 'Acme Coaching');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Acme Coaching');
        $response->assertSee('app-shell', false); // shell rendered
        $response->assertSee('data-brand="client"', false); // theme hook present
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $this->get('/up')
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }
}
