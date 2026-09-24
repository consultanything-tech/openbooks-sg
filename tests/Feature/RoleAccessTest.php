<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
    }

    private function createUserWithRole(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_access_settings(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/settings');
        $response->assertStatus(200);
    }

    public function test_accountant_cannot_access_settings(): void
    {
        $user = $this->createUserWithRole('ACCOUNTANT');
        $this->actingAs($user);

        $response = $this->get('/settings');
        $response->assertStatus(403);
    }

    public function test_viewer_cannot_access_settings(): void
    {
        $user = $this->createUserWithRole('VIEWER');
        $this->actingAs($user);

        $response = $this->get('/settings');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_user_management(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/settings/users');
        $response->assertStatus(200);
    }

    public function test_accountant_can_create_invoices(): void
    {
        $user = $this->createUserWithRole('ACCOUNTANT');
        $this->actingAs($user);

        $response = $this->get('/invoices/create');
        $response->assertStatus(200);
    }

    public function test_viewer_cannot_create_invoices(): void
    {
        $user = $this->createUserWithRole('VIEWER');
        $this->actingAs($user);

        $response = $this->get('/invoices/create');
        $response->assertStatus(403);
    }
}
