<?php

namespace App\Services\AskOpenBooks;

use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Minimal NVIDIA NIM chat client shared by the intent resolver and narrator.
 * Returns null on any failure so callers can fall back to deterministic paths.
 */
class LlmClient
{
    public function isConfigured(): bool
    {
        return $this->apiKey() !== '';
    }

    public function apiKey(): string
    {
        $company = Company::first();
        $key = ! empty($company->nvidia_api_key) ? trim($company->nvidia_api_key) : trim(config('services.nvidia.api_key', ''));

        return $key;
    }

    protected function model(): string
    {
        $company = Company::first();

        return ! empty($company->nvidia_model) ? trim($company->nvidia_model) : config('services.nvidia.model', 'meta/llama-3.2-11b-vision-instruct');
    }

    /**
     * Single-turn completion. Returns assistant text or null.
     */
    public function complete(string $system, string $user, int $maxTokens = 200, float $temperature = 0.1): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey(),
                'Accept' => 'application/json',
            ])->timeout(20)->post(config('services.nvidia.api_url', 'https://integrate.api.nvidia.com/v1/chat/completions'), [
                'model' => $this->model(),
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);

            if (! $response->successful()) {
                Log::warning('AskOpenBooks LLM call failed: HTTP '.$response->status());

                return null;
            }

            $text = $response->json('choices.0.message.content');

            return is_string($text) && trim($text) !== '' ? trim($text) : null;
        } catch (\Exception $e) {
            Log::warning('AskOpenBooks LLM call error: '.$e->getMessage());

            return null;
        }
    }
}
