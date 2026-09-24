<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function actingAsAdmin(): \App\Models\User
    {
        $user = \App\Models\User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@test.openbooks.sg',
            'role' => 'ADMIN',
            'is_active' => true,
        ]);
        $this->actingAs($user);
        return $user;
    }
}
