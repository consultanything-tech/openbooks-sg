<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Transaction;
use App\Models\Vendor;
use App\Services\Accounting\JournalService;
use App\Services\Accounting\LedgerReportService;
use Tests\TestCase;

/**
 * Stage 2: the Balance Sheet is derived from the ledger and must balance by
 * construction, with cash taken from the bank accounts.
 */
class BalanceSheetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->actingAsAdmin();
    }

    private function seedScenario(): void
    {
        // Opening cash (owner capital sitting in the bank, not in the ledger P&L).
        BankAccount::factory()->create(['name' => 'DBS Current', 'current_balance' => 5000.00]);

        // Issued, unpaid sales invoice: 1000 + 90 GST = 1090.
        Invoice::factory()->create([
            'subtotal' => 1000.00, 'tax_total' => 90.00, 'total' => 1090.00,
            'due_amount' => 1090.00, 'paid_amount' => 0, 'status' => 'sent',
        ]);

        // Received, unpaid vendor bill: 500 + 45 GST = 545.
        Bill::factory()->create([
            'vendor_id' => Vendor::factory()->create()->id,
            'subtotal' => 500.00, 'tax_total' => 45.00, 'total' => 545.00,
            'due_amount' => 545.00, 'paid_amount' => 0, 'status' => 'received',
        ]);

        // Map bank accounts to ledger accounts and post opening balances
        app(JournalService::class)->backfillCash();
    }

    public function test_balance_sheet_balances_by_construction(): void
    {
        $this->seedScenario();

        $bs = app(LedgerReportService::class)->balanceSheet();

        $this->assertEqualsWithDelta(5000.00, $bs['totalCashBank'], 0.01);
        $this->assertEqualsWithDelta(1090.00, $bs['accountsReceivable'], 0.01);
        $this->assertEqualsWithDelta(6090.00, $bs['totalAssets'], 0.01);

        $this->assertEqualsWithDelta(545.00, $bs['accountsPayable'], 0.01);
        $this->assertEqualsWithDelta(45.00, $bs['gstPayable'], 0.01); // 90 output - 45 input
        $this->assertEqualsWithDelta(590.00, $bs['totalLiabilities'], 0.01);

        // Revenue 1000 - expense 500 = 500 retained earnings.
        $this->assertEqualsWithDelta(500.00, $bs['retainedEarnings'], 0.01);
        // Opening cash is booked to Opening Balance Equity (3200).
        $this->assertEqualsWithDelta(5000.00, $bs['openingBalanceEquity'], 0.01);
        // Owner's equity residual = Assets - Liabilities - RE - Opening Equity = 0.
        $this->assertEqualsWithDelta(0.00, $bs['ownersEquity'], 0.01);

        $this->assertEqualsWithDelta($bs['totalAssets'], $bs['totalLiabilitiesAndEquity'], 0.01);
    }

    public function test_standalone_transactions_flow_to_retained_earnings(): void
    {
        $bank = BankAccount::factory()->create(['current_balance' => 0.00]);

        // Manual cash income unrelated to any invoice (e.g. interest received).
        Transaction::create([
            'type' => 'income', 'bank_account_id' => $bank->id, 'amount' => 300.00,
            'transaction_date' => now()->toDateString(), 'description' => 'Interest',
        ]);

        $bs = app(LedgerReportService::class)->balanceSheet();

        $this->assertEqualsWithDelta(300.00, $bs['retainedEarnings'], 0.01);
        $this->assertEqualsWithDelta($bs['totalAssets'], $bs['totalLiabilitiesAndEquity'], 0.01);
    }

    public function test_balance_sheet_page_renders_balanced(): void
    {
        $this->seedScenario();

        $response = $this->get(route('reports.balance_sheet'));
        $response->assertStatus(200);
        $response->assertSee('Balanced');
        $response->assertSee("Owner's Equity", false);
        $response->assertSee('Opening Balance Equity');
        $response->assertSee('GST Payable (net)');
        $response->assertDontSee('Difference (Assets');
    }

    public function test_backfill_command_is_idempotent(): void
    {
        $invoice = Invoice::factory()->create([
            'subtotal' => 1000.00, 'tax_total' => 90.00, 'total' => 1090.00,
            'due_amount' => 1090.00, 'status' => 'sent',
        ]);

        // Observer already posted the accrual on creation.
        $this->assertSame(1, JournalEntry::where('reference', $invoice->invoice_number . '-ACCRUAL')->count());

        $this->artisan('ledger:backfill')->assertSuccessful();
        $this->artisan('ledger:backfill')->assertSuccessful();

        // Still exactly one accrual entry — backfill never double-posts.
        $this->assertSame(1, JournalEntry::where('reference', $invoice->invoice_number . '-ACCRUAL')->count());
    }
}
