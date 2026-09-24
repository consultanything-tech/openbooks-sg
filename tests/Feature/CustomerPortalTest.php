<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
    }

    public function test_portal_login_page_loads(): void
    {
        $response = $this->get('/portal/login');
        $response->assertStatus(200);
    }

    public function test_portal_login_with_valid_credentials(): void
    {
        $customer = Customer::factory()->create([
            'portal_enabled' => true,
            'portal_password' => Hash::make('portalsecret'),
        ]);

        $response = $this->post('/portal/login', [
            'email' => $customer->email,
            'password' => 'portalsecret',
        ]);

        $response->assertRedirect(route('portal.dashboard'));
    }

    public function test_portal_login_with_invalid_credentials(): void
    {
        $customer = Customer::factory()->create([
            'portal_enabled' => true,
            'portal_password' => Hash::make('correctpass'),
        ]);

        $response = $this->post('/portal/login', [
            'email' => $customer->email,
            'password' => 'wrongpass',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_portal_dashboard_requires_auth(): void
    {
        $response = $this->get('/portal');
        $response->assertRedirect(route('portal.login'));
    }

    public function test_portal_dashboard_loads_when_authenticated(): void
    {
        $customer = Customer::factory()->create(['portal_enabled' => true]);

        $response = $this->withSession(['portal_customer_id' => $customer->id])
            ->get('/portal');

        $response->assertStatus(200);
    }

    public function test_portal_invoices_loads(): void
    {
        $customer = Customer::factory()->create(['portal_enabled' => true]);

        $response = $this->withSession(['portal_customer_id' => $customer->id])
            ->get('/portal/invoices');

        $response->assertStatus(200);
    }

    public function test_portal_logout(): void
    {
        $customer = Customer::factory()->create(['portal_enabled' => true]);

        $response = $this->withSession(['portal_customer_id' => $customer->id])
            ->post('/portal/logout');

        $response->assertRedirect(route('portal.login'));
        $this->assertNull(session('portal_customer_id'));
    }
}
