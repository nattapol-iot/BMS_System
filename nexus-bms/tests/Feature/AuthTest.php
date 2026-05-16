<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_page_renders_with_email_and_password_inputs(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
    }

    public function test_login_with_valid_credentials_redirects_to_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@nexus.com',
            'password' => 'admin1234',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_login_with_invalid_credentials_returns_to_login_with_error(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@nexus.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_logout_invalidates_session(): void
    {
        $user = \App\Models\User::where('email', 'admin@nexus.com')->first();

        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_dashboard_requires_auth(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }
}
