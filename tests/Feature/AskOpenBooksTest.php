<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AskOpenBooks\QuestionCatalog;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AskOpenBooksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->actingAsAdmin();
    }

    public function test_ask_page_loads_with_catalog(): void
    {
        $response = $this->get(route('ask.index'));
        $response->assertStatus(200);
        $response->assertSee('Ask OpenBooks');
        $response->assertSee('Who owes me money and how much?');
    }

    public function test_every_catalog_key_returns_a_payload(): void
    {
        foreach (QuestionCatalog::keys() as $key) {
            $response = $this->postJson(route('ask.run'), ['key' => $key]);
            $response->assertStatus(200);
            $response->assertJsonPath('status', 'ok');
            $response->assertJsonPath('payload.intent', $key);
            $this->assertNotEmpty(
                $response->json('payload.answer'),
                "Handler for {$key} returned an empty answer"
            );
        }
    }

    public function test_cash_position_reports_exact_bank_total(): void
    {
        BankAccount::factory()->create(['name' => 'DBS Current', 'current_balance' => 5000.00]);
        BankAccount::factory()->create(['name' => 'UOB Savings', 'current_balance' => 2500.50]);

        $response = $this->postJson(route('ask.run'), ['key' => 'cash_position']);
        $response->assertStatus(200);
        $this->assertSame(7500.50, $response->json('payload.figure'));
        $this->assertStringContainsString('7,500.50', $response->json('payload.headline'));
    }

    public function test_ar_overdue_lists_only_past_due_invoices(): void
    {
        $customer = Customer::factory()->create(['name' => 'Slow Payer Pte Ltd']);
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'sent',
            'total' => 1000.00,
            'due_amount' => 1000.00,
            'due_date' => now()->subDays(10),
        ]);
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'sent',
            'total' => 800.00,
            'due_amount' => 800.00,
            'due_date' => now()->addDays(10),
        ]);

        $response = $this->postJson(route('ask.run'), ['key' => 'ar_overdue']);
        $response->assertStatus(200);
        $this->assertEqualsWithDelta(1000.0, $response->json('payload.figure'), 0.001);
        $this->assertCount(1, $response->json('payload.breakdown.rows'));
        $this->assertStringContainsString('Slow Payer Pte Ltd', $response->json('payload.breakdown.rows.0.1'));
    }

    public function test_layman_question_maps_to_intent_via_keywords(): void
    {
        $response = $this->postJson(route('ask.run'), ['question' => 'Who owes me money right now?']);
        $response->assertStatus(200);
        $response->assertJsonPath('payload.intent', 'ar_summary');
    }

    public function test_layman_question_about_gst_maps_to_gst_handler(): void
    {
        $response = $this->postJson(route('ask.run'), ['question' => 'what is my gst position for this quarter?']);
        $response->assertStatus(200);
        $response->assertJsonPath('payload.intent', 'gst_position');
    }

    public function test_out_of_scope_question_is_refused_with_suggestions(): void
    {
        $response = $this->postJson(route('ask.run'), ['question' => 'write me a poem about rain clouds']);
        $response->assertStatus(200);
        $response->assertJsonPath('status', 'out_of_scope');
        $this->assertCount(4, $response->json('suggestions'));
    }

    public function test_unknown_key_is_rejected_by_scope_guard(): void
    {
        $response = $this->postJson(route('ask.run'), ['key' => 'drop_all_tables']);
        $response->assertStatus(200);
        $response->assertJsonPath('status', 'out_of_scope');
    }

    public function test_empty_request_is_validation_error(): void
    {
        $response = $this->postJson(route('ask.run'), []);
        $response->assertStatus(422);
    }

    public function test_ask_is_read_only(): void
    {
        Invoice::factory()->create(['status' => 'sent', 'due_amount' => 500]);
        Bill::factory()->create(['status' => 'received', 'due_amount' => 300]);

        $before = [
            'invoices' => Invoice::count(),
            'bills' => Bill::count(),
            'transactions' => Transaction::count(),
            'customers' => Customer::count(),
            'vendors' => Vendor::count(),
        ];

        foreach (QuestionCatalog::keys() as $key) {
            $this->postJson(route('ask.run'), ['key' => $key])->assertStatus(200);
        }

        $this->assertSame($before['invoices'], Invoice::count());
        $this->assertSame($before['bills'], Bill::count());
        $this->assertSame($before['transactions'], Transaction::count());
        $this->assertSame($before['customers'], Customer::count());
        $this->assertSame($before['vendors'], Vendor::count());
    }

    public function test_viewer_role_can_ask_questions(): void
    {
        $viewer = User::factory()->create(['role' => 'VIEWER', 'is_active' => true]);
        $this->actingAs($viewer);

        $this->get(route('ask.index'))->assertStatus(200);
        $this->postJson(route('ask.run'), ['key' => 'cash_position'])
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        auth()->logout();
        $this->get(route('ask.index'))->assertRedirect(route('login'));
    }

    public function test_questions_are_logged_and_shown_as_recent(): void
    {
        $this->postJson(route('ask.run'), ['key' => 'cash_position'])->assertStatus(200);
        $this->postJson(route('ask.run'), ['question' => 'who owes me money?'])->assertStatus(200);

        $this->assertDatabaseCount('ask_queries', 2);
        $this->assertDatabaseHas('ask_queries', ['intent_key' => 'ar_summary']);

        $this->get(route('ask.index'))
            ->assertStatus(200)
            ->assertSee('Who owes me money and how much?');
    }

    public function test_llm_resolves_intent_and_narrates_when_key_configured(): void
    {
        Company::first()->update(['nvidia_api_key' => 'nvapi-test-key']);

        Http::fake([
            'integrate.api.nvidia.com/*' => Http::sequence()
                ->push(['choices' => [['message' => ['content' => '{"key": "profit_summary"}']]]])
                ->push(['choices' => [['message' => ['content' => 'You made a healthy profit this month after covering all your bills.']]]]),
        ]);

        // No keyword overlap with any catalog entry, so only the LLM can route it
        $response = $this->postJson(route('ask.run'), ['question' => 'give me a pulse check on the business']);
        $response->assertStatus(200);
        $response->assertJsonPath('payload.intent', 'profit_summary');
        $response->assertJsonPath('payload.narrated', true);
        $this->assertStringContainsString('healthy profit', $response->json('payload.answer'));
    }

    public function test_llm_bad_key_is_discarded_by_scope_guard(): void
    {
        Company::first()->update(['nvidia_api_key' => 'nvapi-test-key']);
        Http::fake([
            'integrate.api.nvidia.com/*' => Http::response(
                ['choices' => [['message' => ['content' => '{"key": "drop_all_tables"}']]]]
            ),
        ]);

        $response = $this->postJson(route('ask.run'), ['question' => 'give me a pulse check on the business']);
        $response->assertStatus(200);
        $response->assertJsonPath('status', 'out_of_scope');
    }

    public function test_llm_outage_falls_back_gracefully(): void
    {
        Company::first()->update(['nvidia_api_key' => 'nvapi-test-key']);
        Http::fake([
            'integrate.api.nvidia.com/*' => Http::response([], 500),
        ]);

        // Keyword path still works without the LLM
        $response = $this->postJson(route('ask.run'), ['question' => 'which invoices are overdue?']);
        $response->assertStatus(200);
        $response->assertJsonPath('payload.intent', 'ar_overdue');
        $this->assertArrayNotHasKey('narrated', $response->json('payload'));
    }

    public function test_drill_returns_row_level_documents(): void
    {
        $customer = Customer::factory()->create(['name' => 'Drill Down Pte Ltd']);
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-DRILL-001',
            'status' => 'sent',
            'total' => 900.00,
            'due_amount' => 900.00,
            'due_date' => now()->subDays(5),
        ]);

        $response = $this->postJson(route('ask.drill'), ['key' => 'ar_overdue']);
        $response->assertStatus(200);
        $response->assertJsonPath('payload.drill', true);
        $this->assertStringContainsString('INV-DRILL-001', $response->json('payload.breakdown.rows.0.0'));

        // Intents without row-level detail are rejected
        $this->postJson(route('ask.drill'), ['key' => 'cash_position'])->assertStatus(422);
    }

    public function test_why_followup_drills_into_previous_answer(): void
    {
        $response = $this->postJson(route('ask.run'), [
            'question' => 'why?',
            'context' => 'ar_overdue',
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('payload.drill', true);
    }

    public function test_answers_carry_followups_and_drill_flags(): void
    {
        $response = $this->postJson(route('ask.run'), ['key' => 'profit_summary']);
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('payload.followups'));
        $this->assertTrue($response->json('payload.drill_available'));
    }

    public function test_ask_page_accepts_q_deeplink_param(): void
    {
        $this->get(route('ask.index', ['q' => 'profit_summary']))
            ->assertStatus(200)
            ->assertSee('data-auto="profit_summary"', false);
    }
}
