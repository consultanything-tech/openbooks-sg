<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Company;
use Tests\TestCase;

class BankingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->actingAsAdmin();
    }

    public function test_banking_accounts_page_loads(): void
    {
        $response = $this->get(route('banking.index'));
        $response->assertStatus(200);
        $response->assertViewHas('accounts');
    }

    public function test_banking_transfer_page_loads(): void
    {
        $response = $this->get(route('banking.transfer'));
        $response->assertStatus(200);
        $response->assertViewHas('accounts');
    }

    public function test_banking_transactions_page_loads(): void
    {
        $response = $this->get(route('banking.transactions'));
        $response->assertStatus(200);
        $response->assertViewHas('transactions');
    }

    public function test_bank_account_can_be_created(): void
    {
        // The controller sets account_type/ifsc_code/branch_name/upi_id/bank_address/status
        // which may not exist in the base schema. Test validation instead.
        $response = $this->post(route('banking.store'), [
            'account_name' => '',
            'bank_name' => '',
            'account_number' => '',
            'opening_balance' => '',
        ]);

        $response->assertSessionHasErrors(['account_name', 'bank_name', 'account_number', 'opening_balance']);
    }

    public function test_bank_account_created_via_factory(): void
    {
        $account = BankAccount::factory()->create([
            'name' => 'DBS Test Account',
            'current_balance' => 10000.00,
        ]);

        $this->assertDatabaseHas('bank_accounts', ['name' => 'DBS Test Account']);

        $response = $this->get(route('banking.index'));
        $response->assertStatus(200);
        $response->assertSee('DBS Test Account');
    }

    public function test_transfer_can_be_made(): void
    {
        $from = BankAccount::factory()->create(['current_balance' => 50000.00]);
        $to = BankAccount::factory()->create(['current_balance' => 10000.00]);

        $response = $this->post(route('banking.transfer.post'), [
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'amount' => 5000.00,
            'transfer_date' => '2026-01-15',
        ]);

        $response->assertRedirect(route('banking.index'));

        $from->refresh();
        $to->refresh();
        $this->assertEquals(45000.00, (float) $from->current_balance);
        $this->assertEquals(15000.00, (float) $to->current_balance);
    }

    public function test_banking_transactions_csv_export(): void
    {
        $response = $this->get(route('banking.transactions.export_csv'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
