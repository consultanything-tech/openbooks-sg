<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
    }

    public function test_notification_index_returns_json(): void
    {
        $user = $this->actingAsAdmin();

        Notification::create([
            'user_id' => $user->id,
            'type' => 'invoice',
            'title' => 'Invoice Paid',
            'message' => 'Invoice INV-001 has been paid.',
            'is_read' => false,
        ]);

        $response = $this->getJson(route('notifications.index'));
        $response->assertStatus(200);
        $response->assertJsonStructure(['notifications', 'unread_count']);
        $this->assertEquals(1, $response->json('unread_count'));
    }

    public function test_notification_mark_as_read_works(): void
    {
        $user = $this->actingAsAdmin();

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'bill',
            'title' => 'Bill Overdue',
            'message' => 'Bill BILL-001 is overdue.',
            'is_read' => false,
        ]);

        $response = $this->postJson(route('notifications.read', $notification->id));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $notification->refresh();
        $this->assertTrue($notification->is_read);
        $this->assertNotNull($notification->read_at);
    }

    public function test_notification_mark_all_read_works(): void
    {
        $user = $this->actingAsAdmin();

        Notification::create([
            'user_id' => $user->id,
            'type' => 'system',
            'title' => 'Welcome',
            'is_read' => false,
        ]);
        Notification::create([
            'user_id' => $user->id,
            'type' => 'invoice',
            'title' => 'New Invoice',
            'is_read' => false,
        ]);

        $response = $this->postJson(route('notifications.read_all'));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals(0, Notification::where('user_id', $user->id)->where('is_read', false)->count());
    }

    public function test_notification_can_be_deleted(): void
    {
        $user = $this->actingAsAdmin();

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'system',
            'title' => 'Test Notification',
            'is_read' => true,
        ]);

        $response = $this->deleteJson(route('notifications.destroy', $notification->id));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }
}
