<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Vendor;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiChatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create(['nvidia_api_key' => 'nvapi-test-key']);
        $this->actingAsAdmin();
    }

    protected function fakeAiReply(string $action, array $params): void
    {
        Http::fake([
            'integrate.api.nvidia.com/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode([
                        'message' => 'On it.',
                        'action' => $action,
                        'parameters' => $params,
                    ])],
                ]],
            ]),
        ]);
    }

    public function test_bill_to_customer_is_corrected_to_invoice(): void
    {
        Customer::factory()->create(['name' => 'Marina Bay Trading Pte Ltd']);

        // The model wrongly picks create_bill; the guard must flip it
        $this->fakeAiReply('create_bill', [
            'party_name' => 'Marina Bay Trading',
            'item_name' => 'Consulting services',
            'amount' => 1000,
        ]);

        $response = $this->postJson(route('ai.chat'), ['message' => 'Bill to Marina Bay Trading for $1000', 'history' => []]);
        $response->assertStatus(200);
        $response->assertJsonPath('action', 'create_invoice');

        $this->assertSame(1, Invoice::count());
        $this->assertSame(0, Bill::count());
        $this->assertStringContainsString('invoicing the customer', $response->json('reply'));
    }

    public function test_invoice_for_vendor_is_corrected_to_bill(): void
    {
        Vendor::factory()->create(['name' => 'AWS Asia Pacific']);

        $this->fakeAiReply('create_invoice', [
            'party_name' => 'AWS Asia Pacific',
            'item_name' => 'Hosting',
            'amount' => 500,
        ]);

        $response = $this->postJson(route('ai.chat'), ['message' => 'invoice AWS Asia Pacific 500', 'history' => []]);
        $response->assertStatus(200);
        $response->assertJsonPath('action', 'create_bill');

        $this->assertSame(1, Bill::count());
        $this->assertSame(0, Invoice::count());
    }

    public function test_correct_action_for_matching_party_type_is_untouched(): void
    {
        Vendor::factory()->create(['name' => 'Office Supplies Co']);

        $this->fakeAiReply('create_bill', [
            'party_name' => 'Office Supplies Co',
            'item_name' => 'Paper',
            'amount' => 200,
        ]);

        $response = $this->postJson(route('ai.chat'), ['message' => 'record a bill from Office Supplies Co for 200', 'history' => []]);
        $response->assertStatus(200);
        $response->assertJsonPath('action', 'create_bill');
        $this->assertSame(1, Bill::count());
    }

    public function test_unknown_party_keeps_model_action(): void
    {
        $this->fakeAiReply('create_bill', [
            'party_name' => 'Brand New Supplier Pte Ltd',
            'item_name' => 'Goods',
            'amount' => 300,
        ]);

        $response = $this->postJson(route('ai.chat'), ['message' => 'bill from Brand New Supplier Pte Ltd for 300', 'history' => []]);
        $response->assertStatus(200);
        $response->assertJsonPath('action', 'create_bill');
        $this->assertSame(1, Bill::count());
    }

    public function test_chat_without_api_key_returns_setup_guidance(): void
    {
        Company::query()->update(['nvidia_api_key' => null]);

        $response = $this->postJson(route('ai.chat'), ['message' => 'hello', 'history' => []]);
        $response->assertStatus(200);
        $this->assertStringContainsString('build.nvidia.com', $response->json('reply'));
    }

    protected function fakeAiReplyWithConfidence(string $action, array $params, float $confidence): void
    {
        Http::fake([
            'integrate.api.nvidia.com/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode([
                        'message' => 'Attempting the action.',
                        'action' => $action,
                        'confidence' => $confidence,
                        'parameters' => $params,
                    ])],
                ]],
            ]),
        ]);
    }

    public function test_low_confidence_write_action_is_blocked_for_confirmation(): void
    {
        Customer::factory()->create(['name' => 'Maybe Corp']);

        $this->fakeAiReplyWithConfidence('create_bill', [
            'party_name' => 'Maybe Corp',
            'item_name' => 'Something',
            'amount' => 700,
        ], 0.3);

        $response = $this->postJson(route('ai.chat'), ['message' => 'do the thing for 700', 'history' => []]);
        $response->assertStatus(200);
        $response->assertJsonPath('action', 'none');
        $this->assertStringContainsString('confirm', $response->json('reply'));
        $this->assertSame(0, Bill::count());
        $this->assertSame(0, Invoice::count());
    }

    public function test_high_confidence_write_action_executes(): void
    {
        Customer::factory()->create(['name' => 'Sure Corp']);

        $this->fakeAiReplyWithConfidence('create_invoice', [
            'party_name' => 'Sure Corp',
            'item_name' => 'Consulting',
            'amount' => 800,
        ], 0.9);

        $response = $this->postJson(route('ai.chat'), ['message' => 'invoice Sure Corp 800', 'history' => []]);
        $response->assertStatus(200);
        $response->assertJsonPath('action', 'create_invoice');
        $this->assertSame(1, Invoice::count());
    }

    public function test_ai_write_is_recorded_in_activity_log(): void
    {
        Customer::factory()->create(['name' => 'Audit Trail Pte Ltd']);

        $this->fakeAiReply('create_invoice', [
            'party_name' => 'Audit Trail Pte Ltd',
            'item_name' => 'Bookkeeping',
            'amount' => 1200,
        ]);

        $response = $this->postJson(route('ai.chat'), ['message' => 'invoice Audit Trail Pte Ltd 1200', 'history' => []]);
        $response->assertStatus(200);
        $response->assertJsonPath('action', 'create_invoice');

        $invoice = Invoice::first();
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'model_type' => 'Invoice',
            'model_id' => $invoice->id,
        ]);

        $log = \App\Models\ActivityLog::where('model_type', 'Invoice')->first();
        $this->assertSame('ai_assistant', $log->properties['source'] ?? null);
        $this->assertStringContainsString('via AI Assistant', $log->description);
    }

    public function test_viewer_role_cannot_write_via_chat(): void
    {
        $viewer = \App\Models\User::factory()->create(['role' => 'VIEWER', 'is_active' => true]);
        $this->actingAs($viewer);

        $this->fakeAiReply('create_invoice', [
            'party_name' => 'Anyone',
            'item_name' => 'Service',
            'amount' => 100,
        ]);

        $response = $this->postJson(route('ai.chat'), ['message' => 'invoice Anyone 100', 'history' => []]);
        $response->assertStatus(403);
        $this->assertSame(0, Invoice::count());
    }

    public function test_quote_request_never_creates_invoice_even_if_model_mispredicts(): void
    {
        $customer = Customer::factory()->create(['name' => 'Marina Bay Trading Pte Ltd', 'balance' => 0]);

        // The model wrongly maps "quote" onto create_invoice; the quote-sense guard must flip it.
        $this->fakeAiReply('create_invoice', [
            'party_name' => 'Marina Bay Trading',
            'item_name' => 'Website redesign',
            'amount' => 5000,
        ]);

        $response = $this->postJson(route('ai.chat'), [
            'message' => 'create a quote for Marina Bay Trading for 5000 for the website redesign',
            'history' => [],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('action', 'create_quote');
        $response->assertJsonPath('action_card.type', 'quote');

        $this->assertSame(1, \App\Models\Quote::count());
        $this->assertSame(0, Invoice::count());
        // Quotes are non-binding: no receivable may be posted.
        $this->assertSame(0.0, (float) $customer->fresh()->balance);
    }

    public function test_create_quote_action_executes_and_posts_no_receivable(): void
    {
        Customer::factory()->create(['name' => 'Orchard Retail Solutions Pte Ltd', 'balance' => 0]);

        $this->fakeAiReply('create_quote', [
            'party_name' => 'Orchard Retail',
            'item_name' => 'Consulting',
            'amount' => 2500,
            'tax_rate' => 9,
        ]);

        $response = $this->postJson(route('ai.chat'), [
            'message' => 'give Orchard Retail an estimate of 2500 plus tax for consulting',
            'history' => [],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('action', 'create_quote');

        $quote = \App\Models\Quote::first();
        $this->assertNotNull($quote);
        $this->assertStringStartsWith('QUO-', $quote->quote_number);
        $this->assertSame('sent', $quote->status);
        $this->assertSame(2725.0, (float) $quote->total);
        $this->assertSame(1, $quote->items()->count());
        $this->assertSame(0, Invoice::count());
    }

    public function test_same_name_customer_and_vendor_tie_broken_by_wording(): void
    {
        // The earlier bug left a vendor with the same name as the customer
        Customer::factory()->create(['name' => 'Marina Bay Trading Pte Ltd']);
        Vendor::factory()->create(['name' => 'Marina Bay Trading Pte Ltd']);

        // "bill ... to <party>" => sales invoice even though both types exist
        $this->fakeAiReply('create_bill', [
            'party_name' => 'Marina Bay Trading',
            'item_name' => 'Website Designing Services',
            'amount' => 10000,
        ]);

        $response = $this->postJson(route('ai.chat'), [
            'message' => 'create a bill of 10000 dollars for website designing services to marina bay trading',
            'history' => [],
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('action', 'create_invoice');
        $this->assertSame(1, Invoice::count());
        $this->assertSame(0, Bill::count());

        // "bill from <party>" => purchase bill
        $this->fakeAiReply('create_bill', [
            'party_name' => 'Marina Bay Trading',
            'item_name' => 'Supplies',
            'amount' => 400,
        ]);

        $response = $this->postJson(route('ai.chat'), [
            'message' => 'record the bill from marina bay trading for 400',
            'history' => [],
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('action', 'create_bill');
        $this->assertSame(1, Bill::count());
    }
}
