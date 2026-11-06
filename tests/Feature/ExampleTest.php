<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     * Tests that unauthenticated users are redirected to login.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);

        $authResponse = $this->get('/dashboard');
        $authResponse->assertRedirect('/login');
    }
}
