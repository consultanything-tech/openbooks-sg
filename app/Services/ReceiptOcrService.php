<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReceiptOcrService
{
    /**
     * Extract receipt data from an image file.
     */
    public function extractFromImage(string $imagePath): array
    {
        $company = Company::first() ?? new Company;
        $apiKey = ! empty($company->nvidia_api_key)
            ? trim($company->nvidia_api_key)
            : trim(config('services.nvidia.api_key', ''));

        if (! empty($apiKey)) {
            return $this->extractWithNvidiaNim($imagePath, $apiKey, $company);
        }

        return $this->extractFallback($imagePath);
    }

    /**
     * Primary method: use NVIDIA NIM vision API for OCR extraction.
     */
    protected function extractWithNvidiaNim(string $imagePath, string $apiKey, Company $company): array
    {
        $model = ! empty($company->nvidia_model)
            ? trim($company->nvidia_model)
            : config('services.nvidia.model', 'meta/llama-3.2-11b-vision-instruct');

        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';

        $prompt = <<<'PROMPT'
You are a receipt OCR extraction engine. Analyze the receipt image and extract all fields.
Return ONLY a valid JSON object with these exact keys:
{
  "merchant": "name of the store/vendor/merchant",
  "date": "YYYY-MM-DD format date from the receipt",
  "amount": 0.00,
  "gst_amount": null,
  "items": [
    {"description": "item name", "quantity": 1, "unit_price": 0.00, "total": 0.00}
  ],
  "confidence": 0.95
}
Rules:
- amount = the total/final amount on the receipt (including tax)
- gst_amount = the GST/VAT/tax amount if visible, otherwise null
- confidence = your confidence score from 0.0 to 1.0
- If a field is not readable, use null for strings, 0 for numbers, and empty array for items
- Return ONLY the JSON, no markdown, no explanation
PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post(config('services.nvidia.api_url', 'https://integrate.api.nvidia.com/v1/chat/completions'), [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $prompt,
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:{$mimeType};base64,{$imageData}",
                                ],
                            ],
                        ],
                    ],
                ],
                'temperature' => 0.1,
                'max_tokens' => 1500,
            ]);

            if (! $response->successful()) {
                Log::error('Receipt OCR NVIDIA API Error: '.$response->body());

                return $this->errorResult('AI vision service returned an error (HTTP '.$response->status().').');
            }

            $rawContent = $response->json()['choices'][0]['message']['content'] ?? '';

            // Clean up possible markdown code blocks
            $cleanJson = trim($rawContent);
            if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $cleanJson, $matches)) {
                $cleanJson = $matches[1];
            } elseif (preg_match('/(\{.*\})/s', $cleanJson, $matches)) {
                $cleanJson = $matches[1];
            }

            $parsed = json_decode($cleanJson, true);

            if (! is_array($parsed)) {
                Log::warning('Receipt OCR: failed to parse AI response as JSON', ['raw' => $rawContent]);

                return $this->errorResult('AI returned data in an unexpected format. Please review and enter details manually.');
            }

            return [
                'merchant' => (string) ($parsed['merchant'] ?? ''),
                'date' => (string) ($parsed['date'] ?? date('Y-m-d')),
                'amount' => (float) ($parsed['amount'] ?? 0),
                'gst_amount' => isset($parsed['gst_amount']) ? (float) $parsed['gst_amount'] : null,
                'items' => is_array($parsed['items'] ?? null) ? $parsed['items'] : [],
                'confidence' => (float) ($parsed['confidence'] ?? 0.5),
                'method' => 'nvidia_nim',
                'message' => 'Receipt data extracted successfully using AI vision.',
            ];

        } catch (\Exception $e) {
            Log::error('Receipt OCR Exception: '.$e->getMessage());

            return $this->errorResult('AI service error: '.$e->getMessage());
        }
    }

    /**
     * Fallback method when no AI key is configured.
     */
    protected function extractFallback(string $imagePath): array
    {
        return [
            'merchant' => '',
            'date' => date('Y-m-d'),
            'amount' => 0.0,
            'gst_amount' => null,
            'items' => [],
            'confidence' => 0.0,
            'method' => 'fallback',
            'message' => 'AI configuration required. Please set your NVIDIA API key in Settings to enable automatic receipt extraction. Free key: sign up at build.nvidia.com, open API Keys from your avatar menu, click Generate API Key (starts with nvapi-), then paste it in Settings → AI Assistant. You can still enter receipt details manually below.',
        ];
    }

    /**
     * Build a structured error result.
     */
    protected function errorResult(string $message): array
    {
        return [
            'merchant' => '',
            'date' => date('Y-m-d'),
            'amount' => 0.0,
            'gst_amount' => null,
            'items' => [],
            'confidence' => 0.0,
            'method' => 'error',
            'message' => $message,
        ];
    }
}
