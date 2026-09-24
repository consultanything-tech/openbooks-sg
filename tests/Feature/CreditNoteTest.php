<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Customer;
use Tests\TestCase;

class CreditNoteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->actingAsAdmin();
    }

    public function test_credit_note_index_loads(): void
    {
        $response = $this->get(route('credit_notes.index'));
        $response->assertStatus(200);
        $response->assertViewHas('creditNotes');
    }

    public function test_credit_note_create_page_loads(): void
    {
        $response = $this->get(route('credit_notes.create'));
        $response->assertStatus(200);
        $response->assertViewHas('customers');
    }

    public function test_credit_note_can_be_created(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->post(route('credit_notes.store'), [
            'credit_note_number' => 'CN-TEST-0001',
            'customer_id' => $customer->id,
            'credit_note_date' => '2026-01-15',
            'reason' => 'Goods returned',
            'items' => [
                ['name' => 'Returned Item', 'quantity' => 2, 'price' => 100.00, 'tax_rate' => 9],
            ],
        ]);

        $response->assertRedirect(route('credit_notes.index'));
        $this->assertDatabaseHas('credit_notes', ['credit_note_number' => 'CN-TEST-0001']);
    }

    public function test_credit_note_show_page_loads(): void
    {
        $customer = Customer::factory()->create();
        $creditNote = CreditNote::create([
            'credit_note_number' => 'CN-SHOW-0001',
            'customer_id' => $customer->id,
            'credit_note_date' => '2026-01-15',
            'subtotal' => 200.00,
            'tax_total' => 18.00,
            'total' => 218.00,
            'status' => 'issued',
        ]);

        $response = $this->get(route('credit_notes.show', $creditNote->id));
        $response->assertStatus(200);
        $response->assertViewHas('creditNote');
    }

    public function test_credit_note_print_page_loads(): void
    {
        $customer = Customer::factory()->create();
        $creditNote = CreditNote::create([
            'credit_note_number' => 'CN-PRINT-0001',
            'customer_id' => $customer->id,
            'credit_note_date' => '2026-01-15',
            'subtotal' => 100.00,
            'tax_total' => 9.00,
            'total' => 109.00,
            'status' => 'issued',
        ]);

        $response = $this->get(route('credit_notes.print', $creditNote->id));
        $response->assertStatus(200);
    }
}
