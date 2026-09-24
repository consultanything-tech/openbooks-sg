<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Vendor;
use App\Services\Accounting\LedgerReportService;
use Tests\TestCase;

/**
 * The General Ledger drills from the Trial Balance into a single account: every
 * posted journal line with an opening balance, a running balance in the
 * account's normal direction, and a closing balance that ties to the Trial
 * Balance figure for the same as-of date.
 */
class GeneralLedgerTest extends TestCase
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

    public function test_general_ledger_returns_running_and_closing_balance(): void
    {
        $this->seedDocs();

        $gl = app(LedgerReportService::class)->generalLedger('1100', null, '2026-12-31');

        $this->assertNotNull($gl);
        $this->assertSame('debit', $gl['normal']);
        $this->assertEqualsWithDelta(0.0, $gl['opening'], 0.01);
        $this->assertEqualsWithDelta(1090.00, $gl['closing'], 0.01);
        $this->assertEqualsWithDelta(1090.00, $gl['totalDebit'], 0.01);
        $this->assertEqualsWithDelta(0.0, $gl['totalCredit'], 0.01);
        $this->assertCount(1, $gl['lines']);
        $this->assertEqualsWithDelta(1090.00, $gl['lines'][0]['balance'], 0.01);
    }

    public function test_general_ledger_closing_ties_to_trial_balance(): void
    {
        $this->seedDocs();
        $ledger = app(LedgerReportService::class);

        $tb = $ledger->trialBalance('2026-12-31');
        $byCode = collect($tb['accounts'])->keyBy('code');

        foreach (['1100', '4000', '2100', '5100', '2000'] as $code) {
            $gl = $ledger->generalLedger($code, null, '2026-12-31');
            $tbAmount = $byCode[$code]['debit'] > 0 ? $byCode[$code]['debit'] : $byCode[$code]['credit'];
            $this->assertEqualsWithDelta(
                $tbAmount,
                abs($gl['closing']),
                0.01,
                "GL closing for {$code} should match the Trial Balance figure"
            );
        }
    }

    public function test_general_ledger_opening_balance_excludes_period(): void
    {
        $this->seedDocs();
        $ledger = app(LedgerReportService::class);

        // Period starts after both documents, so everything is in the opening balance.
        $gl = $ledger->generalLedger('1100', '2026-06-01', '2026-12-31');

        $this->assertEqualsWithDelta(1090.00, $gl['opening'], 0.01);
        $this->assertCount(0, $gl['lines']);
        $this->assertEqualsWithDelta(1090.00, $gl['closing'], 0.01);
    }

    public function test_general_ledger_unknown_account_returns_null(): void
    {
        $this->seedDocs();

        $gl = app(LedgerReportService::class)->generalLedger('9999', null, '2026-12-31');

        $this->assertNull($gl);
    }

    public function test_general_ledger_page_renders(): void
    {
        $this->seedDocs();

        $response = $this->get(route('reports.general_ledger', ['account' => '1100', 'end_date' => '2026-12-31']));
        $response->assertStatus(200);
        $response->assertSee('General Ledger');
        $response->assertSee('Accounts Receivable');
        $response->assertSee('Trial Balance');
    }

    public function test_general_ledger_unknown_account_page_404s(): void
    {
        $this->seedDocs();

        $response = $this->get(route('reports.general_ledger', ['account' => '9999', 'end_date' => '2026-12-31']));
        $response->assertStatus(404);
    }

    public function test_general_ledger_csv_exports(): void
    {
        $this->seedDocs();

        $response = $this->get(route('reports.general_ledger.export_csv', ['account' => '1100', 'end_date' => '2026-12-31']));
        $response->assertStatus(200);
        $this->assertStringContainsString('TOTAL', $response->getContent());
    }
}
