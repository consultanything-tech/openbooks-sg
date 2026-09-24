<?php

namespace Tests\Feature;

use App\Models\Company;
use Tests\TestCase;

class ReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->actingAsAdmin();
    }

    public function test_profit_loss_report_loads(): void
    {
        $response = $this->get(route('reports.profit_loss'));
        $response->assertStatus(200);
        $response->assertViewHas('netProfit');
    }

    public function test_balance_sheet_report_loads(): void
    {
        $response = $this->get(route('reports.balance_sheet'));
        $response->assertStatus(200);
        $response->assertViewHas('totalAssets');
    }

    public function test_income_expense_report_loads(): void
    {
        $response = $this->get(route('reports.income_expense'));
        $response->assertStatus(200);
        $response->assertViewHas('monthlyData');
    }

    public function test_tax_summary_report_loads(): void
    {
        $response = $this->get(route('reports.tax_summary'));
        $response->assertStatus(200);
        $response->assertViewHas('collectedTax');
    }

    public function test_ar_aging_report_loads(): void
    {
        $response = $this->get(route('reports.ar_aging'));
        $response->assertStatus(200);
        $response->assertViewHas('buckets');
    }

    public function test_ap_aging_report_loads(): void
    {
        $response = $this->get(route('reports.ap_aging'));
        $response->assertStatus(200);
        $response->assertViewHas('buckets');
    }

    public function test_gst_f5_report_loads(): void
    {
        $response = $this->get(route('reports.gst_f5'));
        $response->assertStatus(200);
        $response->assertViewHas('box1');
    }

    public function test_profit_loss_csv_export_works(): void
    {
        $response = $this->get(route('reports.profit_loss.export_csv'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_balance_sheet_csv_export_works(): void
    {
        $response = $this->get(route('reports.balance_sheet.export_csv'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_ar_aging_csv_export_works(): void
    {
        $response = $this->get(route('reports.ar_aging.export_csv'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
