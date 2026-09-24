<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Vendor;
use App\Services\Accounting\LedgerReportService;
use Tests\TestCase;

/**
 * The Trial Balance is the integrity check for the whole ledger: because every
 * journal entry is balanced, total debits must equal total credits.
 */
class TrialBalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->actingAsAdmin();
    }

    private function seedDocs(): void
    {
        // Invoice 1000 + 90 GST = 1090  ->  DR AR 1090 / CR Revenue 1000 / CR GST 90
        Invoice::factory()->create([
            'invoice_date' => '2026-03-15',
            'subtotal' => 1000.00, 'tax_total' => 90.00, 'total' => 1090.00,
            'due_amount' => 1090.00, 'status' => 'sent',
        ]);
        // Bill 500 + 45 GST = 545  ->  DR Expense 500 / DR GST 45 / CR AP 545
        Bill::factory()->create([
            'vendor_id' => Vendor::factory()->create()->id,
            'bill_date' => '2026-04-10',
            'subtotal' => 500.00, 'tax_total' => 45.00, 'total' => 545.00,
            'due_amount' => 545.00, 'status' => 'received',
        ]);
    }

    public function test_trial_balance_debits_equal_credits(): void
    {
        $this->seedDocs();

        $tb = app(LedgerReportService::class)->trialBalance('2026-12-31');

        $this->assertTrue($tb['balanced']);
        $this->assertEqualsWithDelta(1590.00, $tb['totalDebit'], 0.01);
        $this->assertEqualsWithDelta($tb['totalDebit'], $tb['totalCredit'], 0.01);
    }

    public function test_trial_balance_places_accounts_in_correct_columns(): void
    {
        $this->seedDocs();

        $tb = app(LedgerReportService::class)->trialBalance('2026-12-31');
        $byCode = collect($tb['accounts'])->keyBy('code');

        // AR is a debit balance; Revenue a credit balance.
        $this->assertEqualsWithDelta(1090.00, $byCode['1100']['debit'], 0.01);
        $this->assertEqualsWithDelta(0.0, $byCode['1100']['credit'], 0.01);
        $this->assertEqualsWithDelta(1000.00, $byCode['4000']['credit'], 0.01);
        // Net GST = 90 output - 45 input = 45 credit.
        $this->assertEqualsWithDelta(45.00, $byCode['2100']['credit'], 0.01);
        // Expense is a debit balance.
        $this->assertEqualsWithDelta(500.00, $byCode['5100']['debit'], 0.01);
    }

    public function test_trial_balance_page_renders_balanced(): void
    {
        $this->seedDocs();

        $response = $this->get(route('reports.trial_balance', ['as_of' => '2026-12-31']));
        $response->assertStatus(200);
        $response->assertSee('Trial Balance');
        $response->assertSee('Balanced');
        $response->assertSee('Accounts Receivable');
    }

    public function test_trial_balance_csv_exports(): void
    {
        $this->seedDocs();

        $response = $this->get(route('reports.trial_balance.export_csv', ['as_of' => '2026-12-31']));
        $response->assertStatus(200);
        $this->assertStringContainsString('TOTAL', $response->getContent());
    }
}
