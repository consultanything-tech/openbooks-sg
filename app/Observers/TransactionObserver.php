<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Services\Accounting\JournalService;

/**
 * Posts the cash leg of every Transaction to the double-entry ledger.
 * This is the single source of truth for cash movements in the journal:
 * invoice/bill payments, standalone income/expense, and expense claims all
 * flow through here. Transfers and opening balances are handled explicitly
 * by BankingController (they need paired logic or equity credit).
 *
 * Non-throwing: ledger failures never break transaction persistence.
 */
class TransactionObserver
{
    public function __construct(private JournalService $journal)
    {
    }

    public function created(Transaction $transaction): void
    {
        $this->journal->postTransactionCashLeg($transaction);
    }
}
