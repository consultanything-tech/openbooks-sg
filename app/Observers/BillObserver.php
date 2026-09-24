<?php

namespace App\Observers;

use App\Models\Bill;
use App\Services\Accounting\JournalService;

/**
 * Keeps the double-entry ledger in sync with vendor bills, across every
 * creation path. Posts the ACCRUAL leg only (DR Expense / DR GST / CR AP).
 * Cash legs (DR AP / CR Bank) are posted by TransactionObserver when the
 * payment Transaction is created — that path knows the bank_account_id.
 *
 * Posting is idempotent and non-throwing.
 */
class BillObserver
{
    private const DRAFT_STATES = ['draft', 'cancelled'];

    public function __construct(private JournalService $journal)
    {
    }

    public function created(Bill $bill): void
    {
        if (!in_array($bill->status, self::DRAFT_STATES, true)) {
            $this->journal->postBillAccrual($bill);
        }
    }

    public function updated(Bill $bill): void
    {
        if (!in_array($bill->status, self::DRAFT_STATES, true)) {
            $this->journal->postBillAccrual($bill);
        }
    }
}
