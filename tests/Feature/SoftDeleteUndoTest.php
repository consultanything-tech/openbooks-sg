<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Quote;
use App\Models\Vendor;
use Tests\TestCase;

/**
 * Destructive actions move records to the trash and offer an "Undo" toast,
 * which posts to the matching restore route.
 */
class SoftDeleteUndoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->actingAsAdmin();
    }

    public function test_deleting_an_invoice_soft_deletes_it_and_offers_undo(): void
    {
        $invoice = Invoice::factory()->create();

        $response = $this->delete(route('invoices.destroy', $invoice->id));

        $response->assertRedirect(route('invoices.index'));
        $response->assertSessionHas('undo_url', route('invoices.restore', $invoice->id));
        $response->assertSessionHas('undo_label', 'Undo');

        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
        $this->assertNull(Invoice::find($invoice->id));
        $this->assertNotNull(Invoice::withTrashed()->find($invoice->id));
    }

    public function test_undo_restores_an_invoice_and_reapplies_the_customer_balance(): void
    {
        $customer = Customer::factory()->create(['balance' => 500.00]);
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
        $due = (float) $invoice->due_amount;

        $this->delete(route('invoices.destroy', $invoice->id));
        $this->assertEqualsWithDelta(500.00 - $due, (float) $customer->fresh()->balance, 0.01);

        $this->post(route('invoices.restore', $invoice->id))->assertRedirect(route('invoices.index'));

        $this->assertNotNull(Invoice::find($invoice->id));
        $this->assertEqualsWithDelta(500.00, (float) $customer->fresh()->balance, 0.01);
    }

    public function test_undo_restores_a_bill_and_reapplies_the_vendor_balance(): void
    {
        $vendor = Vendor::factory()->create(['balance' => 300.00]);
        $bill = Bill::factory()->create(['vendor_id' => $vendor->id]);
        $due = (float) $bill->due_amount;

        $this->delete(route('bills.destroy', $bill->id));
        $this->assertEqualsWithDelta(300.00 - $due, (float) $vendor->fresh()->balance, 0.01);

        $this->post(route('bills.restore', $bill->id))->assertRedirect(route('bills.index'));

        $this->assertNotNull(Bill::find($bill->id));
        $this->assertEqualsWithDelta(300.00, (float) $vendor->fresh()->balance, 0.01);
    }

    public function test_paid_invoice_is_still_protected_from_deletion(): void
    {
        $invoice = Invoice::factory()->paid()->create();

        $this->delete(route('invoices.destroy', $invoice->id))->assertSessionHas('error');

        $this->assertNotNull(Invoice::find($invoice->id));
    }

    public function test_master_data_records_are_restorable(): void
    {
        $customer = Customer::factory()->create();
        $vendor = Vendor::factory()->create();
        $item = Item::factory()->create();

        $this->delete(route('customers.destroy', $customer->id))->assertSessionHas('undo_url');
        $this->delete(route('vendors.destroy', $vendor->id))->assertSessionHas('undo_url');
        $this->delete(route('items.destroy', $item->id))->assertSessionHas('undo_url');

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        $this->assertSoftDeleted('vendors', ['id' => $vendor->id]);
        $this->assertSoftDeleted('items', ['id' => $item->id]);

        $this->post(route('customers.restore', $customer->id));
        $this->post(route('vendors.restore', $vendor->id));
        $this->post(route('items.restore', $item->id));

        $this->assertNotNull(Customer::find($customer->id));
        $this->assertNotNull(Vendor::find($vendor->id));
        $this->assertNotNull(Item::find($item->id));
    }

    public function test_quotes_can_be_deleted_and_undone(): void
    {
        $quote = Quote::factory()->create();

        $this->delete(route('quotes.destroy', $quote->id))->assertSessionHas('undo_url');
        $this->assertSoftDeleted('quotes', ['id' => $quote->id]);

        $this->post(route('quotes.restore', $quote->id));
        $this->assertNotNull(Quote::find($quote->id));
    }

    public function test_restoring_an_active_record_is_a_harmless_no_op(): void
    {
        $invoice = Invoice::factory()->create();

        $this->post(route('invoices.restore', $invoice->id))
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHas('info');

        $this->assertNotNull(Invoice::find($invoice->id));
    }

    public function test_trashed_records_disappear_from_listings_and_lookups(): void
    {
        $invoice = Invoice::factory()->create();

        $this->delete(route('invoices.destroy', $invoice->id));

        $this->get(route('invoices.index'))->assertDontSee($invoice->invoice_number);
        $this->get(route('invoices.show', $invoice->id))->assertNotFound();
    }

    public function test_document_number_generation_skips_trashed_records(): void
    {
        $invoice = Invoice::factory()->create();
        $invoiceNumber = $invoice->invoice_number;

        $this->delete(route('invoices.destroy', $invoice->id));

        // The suggestion must not collide with the trashed row's unique number.
        $suggested = $this->get(route('invoices.create'))->assertOk()->viewData('nextNumber');

        $this->assertNotSame($invoiceNumber, $suggested);
        $this->assertSame(0, Invoice::withTrashed()->where('invoice_number', $suggested)->count());
    }
}
