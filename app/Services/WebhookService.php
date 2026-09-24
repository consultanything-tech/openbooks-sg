<?php

namespace App\Services;

use App\Models\Webhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Dispatch a webhook event to all active subscribers.
     */
    public function dispatch(string $event, array $payload): void
    {
        $webhooks = Webhook::where('event', $event)
            ->where('is_active', true)
            ->get();

        foreach ($webhooks as $webhook) {
            $this->send($webhook, $event, $payload);
        }
    }

    /**
     * Send a single webhook payload with HMAC signature.
     */
    protected function send(Webhook $webhook, string $event, array $payload): void
    {
        $body = json_encode([
            'event' => $event,
            'data' => $payload,
            'timestamp' => now()->toIso8601String(),
        ]);

        $headers = [
            'Content-Type' => 'application/json',
            'X-OpenBooks-Event' => $event,
        ];

        if (!empty($webhook->secret)) {
            $signature = hash_hmac('sha256', $body, $webhook->secret);
            $headers['X-OpenBooks-Signature'] = $signature;
        }

        try {
            $response = Http::withHeaders($headers)
                ->withBody($body, 'application/json')
                ->timeout(10)
                ->post($webhook->url);

            $webhook->update(['last_triggered_at' => now()]);

            if ($response->failed()) {
                Log::warning("Webhook delivery failed", [
                    'webhook_id' => $webhook->id,
                    'url' => $webhook->url,
                    'event' => $event,
                    'status' => $response->status(),
                ]);
            } else {
                Log::info("Webhook delivered successfully", [
                    'webhook_id' => $webhook->id,
                    'event' => $event,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Webhook delivery exception", [
                'webhook_id' => $webhook->id,
                'url' => $webhook->url,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
