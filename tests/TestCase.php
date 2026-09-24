<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function actingAsAdmin(): User
    {
        $user = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@test.openbooks.sg',
            'role' => 'ADMIN',
            'is_active' => true,
        ]);
        $this->actingAs($user);

        return $user;
    }
}
