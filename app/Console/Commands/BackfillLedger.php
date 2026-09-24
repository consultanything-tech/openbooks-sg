<?php

namespace App\Console\Commands;

use App\Services\Accounting\JournalService;
use Illuminate\Console\Command;

class BackfillLedger extends Command
{
    protected $signature = 'ledger:backfill {--cash : Also backfill cash movements (opening balances, transactions, transfers)}';

    protected $description = 'Post balanced journal entries for invoices/bills (and optionally cash) created before the ledger observers existed (idempotent)';

    public function handle(JournalService $journal): int
    {
        $result = $journal->backfillAll();
        $this->info(sprintf(
            'Accrual backfill: %d invoices, %d bills processed.',
            $result['invoices'],
            $result['bills']
        ));

        if ($this->option('cash')) {
            $cash = $journal->backfillCash();
            $this->info(sprintf(
                'Cash backfill: %d bank accounts, %d transactions, %d transfers posted.',
                $cash['banks'],
                $cash['transactions'],
                $cash['transfers']
            ));
        }

        return self::SUCCESS;
    }
}
