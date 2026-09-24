<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\User;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->actingAsAdmin();
    }

    public function test_quote_index_loads(): void
    {
        $response = $this->get(route('quotes.index'));
        $response->assertStatus(200);
        $response->assertViewHas('quotes');
    }

    public function test_quote_create_page_loads(): void
    {
        $response = $this->get(route('quotes.create'));
        $response->assertStatus(200);
    }

    public function test_quote_edit_page_loads(): void
    {
        $quote = Quote::factory()->create();

        $response = $this->get(route('quotes.edit', $quote->id));
        $response->assertStatus(200);
    }

    public function test_quote_can_be_created(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->post(route('quotes.store'), [
            'quote_number' => 'QUO-TEST-0001',
            'customer_id' => $customer->id,
            'quote_date' => '2026-01-15',
            'expiry_date' => '2026-02-15',
            'items' => [
                ['name' => 'Web Development', 'quantity' => 10, 'price' => 150.00, 'tax_rate' => 9],
            ],
            'notes' => 'Test quote',
            'terms' => 'Valid for 30 days',
        ]);

        $response->assertRedirect(route('quotes.index'));
        $this->assertDatabaseHas('quotes', ['quote_number' => 'QUO-TEST-0001']);
    }

    public function test_quote_show_page_loads(): void
    {
        $quote = Quote::factory()->create();

        $response = $this->get(route('quotes.show', $quote->id));
        $response->assertStatus(200);
        $response->assertViewHas('quote');
    }

    public function test_quote_convert_to_invoice(): void
    {
        $quote = Quote::factory()->create(['status' => 'accepted']);

        $response = $this->post(route('quotes.convert', $quote->id));
        $response->assertRedirect();

        $quote->refresh();
        $this->assertEquals('converted', $quote->status);
        $this->assertNotNull($quote->converted_invoice_id);
        $this->assertDatabaseHas('invoices', ['id' => $quote->converted_invoice_id]);
    }

    public function test_viewer_cannot_create_quotes(): void
    {
        $viewer = User::factory()->create(['role' => 'VIEWER', 'is_active' => true]);
        $this->actingAs($viewer);

        $response = $this->get(route('quotes.create'));
        $response->assertStatus(403);
    }

    public function test_quote_csv_export_works(): void
    {
        Quote::factory()->count(2)->create();

        $response = $this->get(route('quotes.export_csv'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
