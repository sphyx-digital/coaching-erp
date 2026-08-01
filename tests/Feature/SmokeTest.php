<?php

namespace Tests\Feature;

use Tests\TestCase;

class SmokeTest extends TestCase
{
    public function test_landing_is_a_branded_sign_in_gateway(): void
    {
        config()->set('client.institute_name', 'Acme Coaching');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Acme Coaching');
        $response->assertSee('Sign in');
        $response->assertSee('data-brand="client"', false); // theme hook present
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $this->get('/up')
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }
}
