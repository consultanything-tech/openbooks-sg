<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Company;
use App\Models\User;
use App\Models\Vendor;
use Tests\TestCase;

class BillTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->actingAsAdmin();
    }

    public function test_bill_index_loads(): void
    {
        $response = $this->get(route('bills.index'));
        $response->assertStatus(200);
        $response->assertViewHas('bills');
    }

    public function test_bill_create_page_loads(): void
    {
        $response = $this->get(route('bills.create'));
        $response->assertStatus(200);
        $response->assertViewHas('vendors');
    }

    public function test_bill_can_be_created(): void
    {
        $vendor = Vendor::factory()->create();

        $response = $this->post(route('bills.store'), [
            'vendor_id' => $vendor->id,
            'bill_number' => 'BILL-TEST-0001',
            'bill_date' => '2026-01-15',
            'due_date' => '2026-02-15',
            'items' => [
                ['item_name' => 'Consulting Service', 'quantity' => 5, 'price' => 200.00, 'tax_rate' => 9],
            ],
            'notes' => 'Test bill',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bills', ['bill_number' => 'BILL-TEST-0001']);
    }

    public function test_bill_show_page_loads(): void
    {
        $bill = Bill::factory()->create();

        $response = $this->get(route('bills.show', $bill->id));
        $response->assertStatus(200);
        $response->assertViewHas('bill');
    }

    public function test_bill_edit_page_loads(): void
    {
        $bill = Bill::factory()->create();

        $response = $this->get(route('bills.edit', $bill->id));
        $response->assertStatus(200);
        $response->assertViewHas('bill');
    }

    public function test_viewer_cannot_create_bills(): void
    {
        $viewer = User::factory()->create(['role' => 'VIEWER', 'is_active' => true]);
        $this->actingAs($viewer);

        $response = $this->get(route('bills.create'));
        $response->assertStatus(403);
    }

    public function test_bill_csv_export_works(): void
    {
        Bill::factory()->count(3)->create();

        $response = $this->get(route('bills.export_csv'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
