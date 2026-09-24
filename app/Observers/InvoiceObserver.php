<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\Accounting\JournalService;

/**
 * Keeps the double-entry ledger in sync with invoices, across every creation
 * path (web controller, AI assistant, REST API, recurring generator, seeders).
 * Posts the ACCRUAL leg only (DR AR / CR Revenue / CR GST).
 * Cash legs (DR Bank / CR AR) are posted by TransactionObserver when the
 * payment Transaction is created — that path knows the bank_account_id.
 *
 * All posting is delegated to JournalService, which is idempotent and never
 * throws — so observing can never break invoice persistence.
 */
class InvoiceObserver
{
    private const DRAFT_STATES = ['draft', 'cancelled'];

    public function __construct(private JournalService $journal) {}

    public function created(Invoice $invoice): void
    {
        if (! in_array($invoice->status, self::DRAFT_STATES, true)) {
            $this->journal->postInvoiceAccrual($invoice);
        }
    }

    public function updated(Invoice $invoice): void
    {
        if (! in_array($invoice->status, self::DRAFT_STATES, true)) {
            // Idempotent: posts only if the accrual entry does not yet exist
            // (handles the draft -> sent transition).
            $this->journal->postInvoiceAccrual($invoice);
        }
    }
}
