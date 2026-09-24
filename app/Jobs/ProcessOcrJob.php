<?php

namespace App\Jobs;

use App\Services\ReceiptOcrService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOcrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public string $filePath,
        public int $userId,
        public ?int $expenseClaimId = null,
    ) {}

    public function handle(ReceiptOcrService $ocrService): void
    {
        Log::info('Processing OCR for receipt', [
            'file' => $this->filePath,
            'user_id' => $this->userId,
        ]);

        try {
            $result = $ocrService->extractFromReceipt($this->filePath);

            Log::info('OCR processing complete', [
                'file' => $this->filePath,
                'result_keys' => array_keys($result ?? []),
            ]);
        } catch (\Exception $e) {
            Log::error('OCR processing failed', [
                'file' => $this->filePath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
