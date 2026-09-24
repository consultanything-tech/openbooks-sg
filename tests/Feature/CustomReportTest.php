<?php

namespace Tests\Feature;

use App\Models\Company;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->actingAsAdmin();
    }

    public function test_custom_report_index_loads(): void
    {
        $response = $this->get(route('reports.custom'));
        $response->assertStatus(200);
        $response->assertViewHas('savedReports');
    }

    public function test_custom_report_can_be_saved(): void
    {
        $response = $this->postJson(route('reports.custom.store'), [
            'name' => 'Monthly Invoice Summary',
            'description' => 'Invoice totals grouped by month',
            'source' => 'invoices',
            'definition' => [
                'dimensions' => ['date'],
                'metrics' => ['sum_total', 'count'],
            ],
            'is_shared' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('custom_reports', ['name' => 'Monthly Invoice Summary']);
    }

    public function test_custom_report_can_be_run(): void
    {
        $response = $this->postJson(route('reports.custom.run'), [
            'source' => 'invoices',
            'dimensions' => ['status'],
            'metrics' => ['sum_total', 'count'],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['success', 'results' => ['rows', 'summary']]);
    }

    public function test_custom_report_csv_export_works(): void
    {
        $response = $this->post(route('reports.custom.export'), [
            'source' => 'invoices',
            'dimensions' => ['status'],
            'metrics' => ['sum_total'],
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_custom_report_run_with_date_filters(): void
    {
        $response = $this->postJson(route('reports.custom.run'), [
            'source' => 'transactions',
            'dimensions' => ['date'],
            'metrics' => ['sum_total'],
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public static function sourceCapabilityProvider(): array
    {
        return [
            'invoices all dims' => ['invoices', ['date', 'customer', 'status'], ['sum_total', 'count', 'avg_total', 'sum_tax']],
            'bills all dims' => ['bills', ['date', 'customer', 'status'], ['sum_total', 'count', 'avg_total', 'sum_tax']],
            'transactions dims' => ['transactions', ['date', 'customer', 'category'], ['sum_total', 'count', 'avg_total']],
            'time_entries dims' => ['time_entries', ['date', 'customer'], ['sum_total', 'count', 'avg_total']],
        ];
    }

    /**
     * Every source/dimension/metric combo offered by the UI must query
     * real columns — guards against schema drift in getSourceConfig().
     */
    #[DataProvider('sourceCapabilityProvider')]
    public function test_every_source_runs_with_its_supported_dimensions(string $source, array $dimensions, array $metrics): void
    {
        $response = $this->postJson(route('reports.custom.run'), [
            'source' => $source,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_group_by_makes_dimension_primary(): void
    {
        $response = $this->postJson(route('reports.custom.run'), [
            'source' => 'invoices',
            'dimensions' => ['date', 'status'],
            'metrics' => ['sum_total'],
            'group_by' => 'status',
        ]);

        $response->assertStatus(200);
        $rows = $response->json('results.rows');
        if ($rows) {
            $this->assertSame('status', array_key_first($rows[0]));
        }
    }
}
