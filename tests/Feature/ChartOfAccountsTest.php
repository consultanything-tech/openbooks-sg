<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

class ChartOfAccountsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->actingAsAdmin();
    }

    public function test_chart_of_accounts_index_loads(): void
    {
        $response = $this->get(route('accounts.index'));
        $response->assertStatus(200);
        $response->assertViewHas('accounts');
    }

    public function test_trial_balance_page_loads(): void
    {
        $response = $this->get(route('accounts.trial_balance'));
        $response->assertStatus(200);
        $response->assertViewHas('accounts');
    }

    public function test_journal_entries_page_loads(): void
    {
        $response = $this->get(route('accounts.journal_entries'));
        $response->assertStatus(200);
        $response->assertViewHas('entries');
    }

    public function test_account_can_be_created(): void
    {
        $response = $this->post(route('accounts.store'), [
            'code' => '6000',
            'name' => 'Test Revenue Account',
            'type' => 'revenue',
            'sub_type' => 'revenue',
            'description' => 'A test revenue account',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('accounts', ['code' => '6000', 'name' => 'Test Revenue Account']);
    }

    public function test_seed_defaults_works(): void
    {
        // Ensure accounts table is empty
        Account::query()->delete();

        $response = $this->post(route('accounts.seed_defaults'));
        $response->assertRedirect();

        // Verify default accounts were created
        $this->assertDatabaseHas('accounts', ['code' => '1000', 'name' => 'Cash']);
        $this->assertDatabaseHas('accounts', ['code' => '4000', 'name' => 'Sales Revenue']);
        $this->assertDatabaseHas('accounts', ['code' => '5400', 'name' => 'Salaries']);
        $this->assertGreaterThanOrEqual(19, Account::count());
    }

    public function test_seed_defaults_fails_when_accounts_exist(): void
    {
        Account::create([
            'code' => '9999',
            'name' => 'Existing Account',
            'type' => 'asset',
            'is_system' => false,
            'balance' => 0,
        ]);

        $response = $this->post(route('accounts.seed_defaults'));
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_viewer_cannot_create_accounts(): void
    {
        $viewer = User::factory()->create(['role' => 'VIEWER', 'is_active' => true]);
        $this->actingAs($viewer);

        $response = $this->post(route('accounts.store'), [
            'code' => '7000',
            'name' => 'Viewer Account',
            'type' => 'expense',
        ]);
        $response->assertStatus(403);
    }
}
