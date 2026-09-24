<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Vendor;
use App\Services\Accounting\LedgerReportService;
use Tests\TestCase;

/**
 * Stage 2: the Profit & Loss is accrual and net of GST, derived from the ledger,
 * and reconciles with the Balance Sheet's retained earnings.
 */
class ProfitLossTest extends TestCase
{
    private const START = '2026-01-01';
    private const END = '2026-12-31';

    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->actingAsAdmin();
    }

    private function seedDocs(): void
    {
        Invoice::factory()->create([
            'invoice_date' => '2026-03-15',
            'subtotal' => 1000.00, 'tax_total' => 90.00, 'total' => 1090.00,
            'due_amount' => 1090.00, 'status' => 'sent',
        ]);
        Bill::factory()->create([
            'vendor_id' => Vendor::factory()->create()->id,
            'bill_date' => '2026-04-10',
            'subtotal' => 500.00, 'tax_total' => 45.00, 'total' => 545.00,
            'due_amount' => 545.00, 'status' => 'received',
        ]);
    }

    public function test_net_profit_excludes_gst(): void
    {
        $this->seedDocs();

        $pl = app(LedgerReportService::class)->profitLoss(self::START, self::END);

        // Revenue/expense are net of GST: 1000 - 500 = 500.
        // (The old gross basis would have wrongly given 1090 - 545 = 545.)
        $this->assertEqualsWithDelta(1000.00, $pl['netRevenue'], 0.01);
        $this->assertEqualsWithDelta(500.00, $pl['netExpense'], 0.01);
        $this->assertEqualsWithDelta(500.00, $pl['netProfit'], 0.01);
    }

    public function test_profit_loss_ties_to_balance_sheet_retained_earnings(): void
    {
        $this->seedDocs();

        $ledger = app(LedgerReportService::class);
        $pl = $ledger->profitLoss(self::START, self::END);
        $bs = $ledger->balanceSheet(self::END);

        // No standalone transactions or opening cash, so retained earnings == net profit.
        $this->assertEqualsWithDelta($pl['netProfit'], $bs['retainedEarnings'], 0.01);
    }

    public function test_profit_loss_page_renders_net_figures(): void
    {
        $this->seedDocs();

        $response = $this->get(route('reports.profit_loss', ['start_date' => self::START, 'end_date' => self::END]));
        $response->assertStatus(200);
        $response->assertSee('Total Revenue (Accrual, excl. GST)');
        // Net profit 500.00, not the gross 545.00.
        $response->assertSee('500.00');
    }
}
