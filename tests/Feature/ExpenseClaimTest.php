<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ExpenseClaim;
use Tests\TestCase;

class ExpenseClaimTest extends TestCase
{
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->admin = $this->actingAsAdmin();
    }

    public function test_expense_claim_index_loads(): void
    {
        $response = $this->get(route('expense_claims.index'));
        $response->assertStatus(200);
        $response->assertViewHas('claims');
    }

    public function test_expense_claim_create_page_loads(): void
    {
        $response = $this->get(route('expense_claims.create'));
        $response->assertStatus(200);
        $response->assertViewHas('categories');
    }

    public function test_expense_claim_can_be_created(): void
    {
        $response = $this->post(route('expense_claims.store'), [
            'claim_number' => 'EXP-TEST-0001',
            'claim_date' => '2026-01-15',
            'title' => 'Taxi fare to client meeting',
            'description' => 'Round trip to Marina Bay',
            'total_amount' => 45.50,
        ]);

        $response->assertRedirect(route('expense_claims.index'));
        $this->assertDatabaseHas('expense_claims', ['claim_number' => 'EXP-TEST-0001']);
    }

    public function test_expense_claim_show_page_loads(): void
    {
        $claim = ExpenseClaim::create([
            'claim_number' => 'EXP-SHOW-0001',
            'user_id' => $this->admin->id,
            'claim_date' => '2026-01-15',
            'title' => 'Office supplies',
            'total_amount' => 120.00,
            'status' => 'submitted',
        ]);

        $response = $this->get(route('expense_claims.show', $claim->id));
        $response->assertStatus(200);
        $response->assertViewHas('claim');
    }

    public function test_expense_claim_can_be_approved(): void
    {
        $claim = ExpenseClaim::create([
            'claim_number' => 'EXP-APPR-0001',
            'user_id' => $this->admin->id,
            'claim_date' => '2026-01-15',
            'title' => 'Client dinner',
            'total_amount' => 250.00,
            'status' => 'submitted',
        ]);

        $response = $this->post(route('expense_claims.approve', $claim->id));
        $response->assertRedirect();

        $claim->refresh();
        $this->assertEquals('approved', $claim->status);
    }
}
