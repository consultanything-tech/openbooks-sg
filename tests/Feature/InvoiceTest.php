<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
    }

    public function test_invoice_index_loads(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/invoices');
        $response->assertStatus(200);
    }

    public function test_invoice_create_page_loads(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/invoices/create');
        $response->assertStatus(200);
    }

    public function test_invoice_can_be_created(): void
    {
        $this->actingAsAdmin();

        $customer = Customer::factory()->create();
        $invoiceNumber = 'INV-TEST-'.Str::random(8);

        $response = $this->post('/invoices', [
            'invoice_number' => $invoiceNumber,
            'customer_id' => $customer->id,
            'invoice_date' => '2026-01-15',
            'due_date' => '2026-02-15',
            'items' => [
                [
                    'name' => 'Consulting Service',
                    'quantity' => 10,
                    'price' => 150.00,
                    'tax_rate' => 9,
                ],
            ],
            'notes' => 'Test invoice',
            'terms' => 'Net 30',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'invoice_number' => $invoiceNumber,
            'customer_id' => $customer->id,
        ]);
    }

    public function test_invoice_show_loads(): void
    {
        $this->actingAsAdmin();

        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $response = $this->get('/invoices/'.$invoice->id);
        $response->assertStatus(200);
    }

    public function test_viewer_cannot_create_invoice(): void
    {
        $viewer = User::factory()->create([
            'role' => 'VIEWER',
            'is_active' => true,
        ]);

        $this->actingAs($viewer);

        $response = $this->get('/invoices/create');
        $response->assertStatus(403);
    }

    public function test_invoice_csv_export(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/invoices/export-csv');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
