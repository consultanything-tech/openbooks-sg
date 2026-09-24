<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
    }

    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_valid_login_redirects_to_dashboard(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_honours_safe_intended_return_path(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
            'intended' => '/invoices?page=2',
        ]);

        $response->assertRedirect('/invoices?page=2');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_unsafe_intended_return_path(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        // Protocol-relative / off-site URLs must be ignored, falling back to dashboard.
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
            'intended' => '//evil.example.com/phish',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_login_returns_error(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('correctpassword'),
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $this->actingAsAdmin();

        $response = $this->withSession(['onboarding_dismissed' => true])->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_logout_redirects_to_login(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/logout');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
