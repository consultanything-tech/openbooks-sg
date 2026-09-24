<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\TimeEntry;
use Tests\TestCase;

class TimeTrackingTest extends TestCase
{
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->admin = $this->actingAsAdmin();
    }

    public function test_time_tracking_index_loads(): void
    {
        $response = $this->get(route('time_tracking.index'));
        $response->assertStatus(200);
        $response->assertViewHas('entries');
    }

    public function test_time_tracking_create_page_loads(): void
    {
        $response = $this->get(route('time_tracking.create'));
        $response->assertStatus(200);
        $response->assertViewHas('customers');
    }

    public function test_time_entry_can_be_created(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->post(route('time_tracking.store'), [
            'customer_id' => $customer->id,
            'project' => 'Website Redesign',
            'description' => 'Frontend development work',
            'entry_date' => '2026-01-15',
            'hours' => 4.5,
            'rate' => 150.00,
            'is_billable' => true,
        ]);

        $response->assertRedirect(route('time_tracking.index'));
        $this->assertDatabaseHas('time_entries', ['project' => 'Website Redesign']);
    }

    public function test_time_entry_show_page_loads(): void
    {
        $customer = Customer::factory()->create();

        $entry = TimeEntry::create([
            'user_id' => $this->admin->id,
            'customer_id' => $customer->id,
            'project' => 'API Integration',
            'entry_date' => '2026-01-15',
            'hours' => 3.00,
            'rate' => 200.00,
            'amount' => 600.00,
            'is_billable' => true,
        ]);

        $response = $this->get(route('time_tracking.show', $entry->id));
        $response->assertStatus(200);
        $response->assertViewHas('entry');
    }

    public function test_time_tracking_csv_export_works(): void
    {
        $response = $this->get(route('time_tracking.export_csv'));
        $response->assertStatus(200);
    }
}
