<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->actingAsAdmin();
    }

    public function test_inventory_index_loads(): void
    {
        $response = $this->get(route('inventory.index'));
        $response->assertStatus(200);
        $response->assertViewHas('items');
    }

    public function test_inventory_movements_page_loads(): void
    {
        $item = Item::factory()->inventoryTracked()->create();

        $response = $this->get(route('inventory.movements', $item->id));
        $response->assertStatus(200);
        $response->assertViewHas('movements');
    }

    public function test_stock_adjustment_works(): void
    {
        $item = Item::factory()->inventoryTracked()->create(['stock_quantity' => 100]);

        $response = $this->post(route('inventory.adjust', $item->id), [
            'adjustment_type' => 'increase',
            'quantity' => 25,
            'notes' => 'Test adjustment',
        ]);

        $response->assertRedirect();
        $item->refresh();
        $this->assertEquals(125, (float) $item->stock_quantity);
    }

    public function test_receive_stock_works(): void
    {
        $item = Item::factory()->inventoryTracked()->create(['stock_quantity' => 50]);

        $response = $this->post(route('inventory.receive'), [
            'item_id' => $item->id,
            'quantity' => 30,
            'bill_reference' => '',
            'notes' => 'Received from supplier',
        ]);

        $response->assertRedirect();
        $item->refresh();
        $this->assertEquals(80, (float) $item->stock_quantity);
    }

    public function test_low_stock_json_endpoint_works(): void
    {
        Item::factory()->create([
            'track_inventory' => true,
            'stock_quantity' => 2,
            'reorder_level' => 10,
        ]);

        $response = $this->getJson(route('inventory.low_stock'));
        $response->assertStatus(200);
        $response->assertJsonStructure(['count', 'items']);
        $this->assertGreaterThanOrEqual(1, $response->json('count'));
    }

    public function test_inventory_csv_export_works(): void
    {
        Item::factory()->inventoryTracked()->count(2)->create();

        $response = $this->get(route('inventory.export_csv'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
