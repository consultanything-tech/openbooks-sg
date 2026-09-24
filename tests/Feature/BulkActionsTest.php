<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Tests\TestCase;

/**
 * Bulk row actions on index tables: select-all -> bulk delete (soft, with Undo).
 * Deletion reuses the single-record destroy() so guards and balance side-effects
 * stay identical; the batch can be restored via the Undo action.
 */
class BulkActionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->actingAsAdmin();
    }

    public function test_bulk_delete_soft_deletes_all_selected_records(): void
    {
        $a = Customer::factory()->create();
        $b = Customer::factory()->create();

        $response = $this->post(route('customers.bulk_delete'), ['ids' => [$a->id, $b->id]]);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('undo_url', route('customers.bulk_restore'));
        $response->assertSessionHas('undo_label', 'Undo');

        $this->assertSoftDeleted('customers', ['id' => $a->id]);
        $this->assertSoftDeleted('customers', ['id' => $b->id]);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], session('ob_bulk_undo_ids'));
    }

    public function test_bulk_undo_restores_the_whole_batch(): void
    {
        $a = Customer::factory()->create();
        $b = Customer::factory()->create();

        $this->post(route('customers.bulk_delete'), ['ids' => [$a->id, $b->id]]);
        $this->assertNull(Customer::find($a->id));

        $this->post(route('customers.bulk_restore'))->assertRedirect(route('customers.index'));

        $this->assertNotNull(Customer::find($a->id));
        $this->assertNotNull(Customer::find($b->id));
        $this->assertNull(session('ob_bulk_undo_ids'));
    }

    public function test_bulk_delete_skips_protected_records_and_reports_the_count(): void
    {
        $deletable = Invoice::factory()->create(['status' => 'sent']);
        $paid = Invoice::factory()->create(['status' => 'paid']);

        $response = $this->post(route('invoices.bulk_delete'), ['ids' => [$deletable->id, $paid->id]]);

        $response->assertRedirect(route('invoices.index'));
        $this->assertSoftDeleted('invoices', ['id' => $deletable->id]);
        // Paid invoices are guarded by destroy() and must survive.
        $this->assertNotNull(Invoice::find($paid->id));
        $this->assertEquals([$deletable->id], session('ob_bulk_undo_ids'));
    }

    public function test_bulk_delete_requires_at_least_one_id(): void
    {
        $response = $this->post(route('customers.bulk_delete'), ['ids' => []]);
        $response->assertSessionHasErrors('ids');
    }

    public function test_viewer_cannot_bulk_delete(): void
    {
        $viewer = User::factory()->create(['role' => 'VIEWER', 'is_active' => true]);
        $customer = Customer::factory()->create();
        $this->actingAs($viewer);

        $this->post(route('customers.bulk_delete'), ['ids' => [$customer->id]])->assertStatus(403);

        $this->assertNotNull(Customer::find($customer->id));
    }
}
