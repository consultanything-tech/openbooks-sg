<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

class BackupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
    }

    public function test_backup_index_loads_for_admin(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('settings.backups'));
        $response->assertStatus(200);
        $response->assertViewHas('backups');
    }

    public function test_non_admin_cannot_access_backups(): void
    {
        $user = User::factory()->create(['role' => 'ACCOUNTANT', 'is_active' => true]);
        $this->actingAs($user);

        $response = $this->get(route('settings.backups'));
        $response->assertStatus(403);
    }

    public function test_viewer_cannot_access_backups(): void
    {
        $user = User::factory()->create(['role' => 'VIEWER', 'is_active' => true]);
        $this->actingAs($user);

        $response = $this->get(route('settings.backups'));
        $response->assertStatus(403);
    }
}
