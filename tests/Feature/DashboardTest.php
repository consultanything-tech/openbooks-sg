<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->actingAsAdmin();

        // The DashboardController redirects to /onboarding when no Customer
        // and no Invoice exist ("fresh install" guard). Seed one customer so
        // the dashboard renders normally.
        Customer::factory()->create();
    }

    public function test_dashboard_loads_for_admin(): void
    {
        BankAccount::factory()->create();

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertViewHas('totalCash');
        $response->assertViewHas('totalReceivables');
    }

    public function test_dashboard_loads_for_accountant(): void
    {
        $user = User::factory()->create(['role' => 'ACCOUNTANT', 'is_active' => true]);
        $this->actingAs($user);
        BankAccount::factory()->create();

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertViewHas('netProfit');
    }

    public function test_dashboard_loads_for_viewer(): void
    {
        $user = User::factory()->create(['role' => 'VIEWER', 'is_active' => true]);
        $this->actingAs($user);
        BankAccount::factory()->create();

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
    }

    public function test_dashboard_shows_correct_data(): void
    {
        BankAccount::factory()->create(['current_balance' => 25000.00]);
        Invoice::factory()->create([
            'status' => 'sent',
            'due_amount' => 5000.00,
            'total' => 5000.00,
        ]);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertViewHas('totalCash', 25000.00);
        $response->assertViewHas('totalReceivables', 5000.00);
        $response->assertViewHas('recentInvoices');
        $response->assertViewHas('forecastData');
    }

    public function test_dashboard_redirects_unauthenticated_user(): void
    {
        auth()->logout();

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_redirects_to_onboarding_on_fresh_install(): void
    {
        // Remove all customers and invoices to trigger the fresh-install guard
        Invoice::query()->delete();
        Customer::query()->delete();

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('onboarding.index'));
    }
}
