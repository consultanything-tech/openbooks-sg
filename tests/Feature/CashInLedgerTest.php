<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Transaction;
use App\Services\Accounting\JournalService;
use Tests\TestCase;

/**
 * Stage D of the cash-in-ledger work: every bank account maps to its own ledger
 * sub-account under 1010, opening balances / transfers / standalone cash all post
 * balanced journal lines, and the ledger cash ties out to the Banking page.
 */
class CashInLedgerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->actingAsAdmin();
    }

    private function debit(string $entryRef, string $code): float
    {
        return (float) JournalEntryLine::where('journal_entry_id', JournalEntry::where('reference', $entryRef)->value('id'))
            ->where('account_id', Account::where('code', $code)->value('id'))->sum('debit');
    }

    private function credit(string $entryRef, string $code): float
    {
        return (float) JournalEntryLine::where('journal_entry_id', JournalEntry::where('reference', $entryRef)->value('id'))
            ->where('account_id', Account::where('code', $code)->value('id'))->sum('credit');
    }

    public function test_bank_account_maps_to_ledger_sub_account_under_1010(): void
    {
        $bank = BankAccount::factory()->create(['current_balance' => 0.00]);
        $acct = app(JournalService::class)->bankLedgerAccount($bank);

        $this->assertNotNull($bank->fresh()->account_id);
        $this->assertSame($acct->id, $bank->fresh()->account_id);
        $this->assertSame('asset', $acct->type);
        $this->assertSame(Account::where('code', '1010')->value('id'), $acct->parent_id);
    }

    public function test_bank_ledger_account_is_idempotent(): void
    {
        $bank = BankAccount::factory()->create(['current_balance' => 0.00]);
        $svc = app(JournalService::class);
        $first = $svc->bankLedgerAccount($bank);
        $second = $svc->bankLedgerAccount($bank->fresh());

        $this->assertSame($first->id, $second->id);
    }

    public function test_opening_balance_posts_to_opening_balance_equity(): void
    {
        $bank = BankAccount::factory()->create(['current_balance' => 0.00]);
        $entry = app(JournalService::class)->postOpeningBalance($bank, 5000.00);

        $this->assertNotNull($entry);
        $this->assertTrue($entry->isBalanced());

        $code = $bank->fresh()->ledgerAccount->code;
        $this->assertEqualsWithDelta(5000.00, $this->debit('BANK-' . $bank->id . '-OPEN', $code), 0.01);
        $this->assertEqualsWithDelta(5000.00, $this->credit('BANK-' . $bank->id . '-OPEN', '3200'), 0.01);
    }

    public function test_transfer_posts_debit_destination_credit_source(): void
    {
        $from = BankAccount::factory()->create(['current_balance' => 0.00]);
        $to = BankAccount::factory()->create(['current_balance' => 0.00]);
        $svc = app(JournalService::class);

        $entry = $svc->postTransfer($from, $to, 1200.00, null, 'TEST');
        $this->assertNotNull($entry);
        $this->assertTrue($entry->isBalanced());

        $fromCode = $from->fresh()->ledgerAccount->code;
        $toCode = $to->fresh()->ledgerAccount->code;
        $this->assertNotSame($fromCode, $toCode);
        $this->assertEqualsWithDelta(1200.00, $this->debit('TRF-TEST', $toCode), 0.01);
        $this->assertEqualsWithDelta(1200.00, $this->credit('TRF-TEST', $fromCode), 0.01);
    }

    public function test_standalone_expense_transaction_posts_expense_and_cash(): void
    {
        $bank = BankAccount::factory()->create(['current_balance' => 0.00]);

        $txn = Transaction::create([
            'type' => 'expense', 'bank_account_id' => $bank->id, 'amount' => 250.00,
            'transaction_date' => now()->toDateString(), 'payment_method' => 'Card',
            'description' => 'Office supplies',
        ]);

        $entry = JournalEntry::where('reference', 'TXN-' . $txn->id)->first();
        $this->assertNotNull($entry, 'TransactionObserver did not post the cash leg');
        $this->assertTrue($entry->isBalanced());

        $code = $bank->fresh()->ledgerAccount->code;
        $this->assertEqualsWithDelta(250.00, $this->debit('TXN-' . $txn->id, '5100'), 0.01);
        $this->assertEqualsWithDelta(250.00, $this->credit('TXN-' . $txn->id, $code), 0.01);
    }

    public function test_transfer_and_opening_transactions_do_not_double_post(): void
    {
        $bank = BankAccount::factory()->create(['current_balance' => 0.00]);

        $txn = Transaction::create([
            'type' => 'income', 'bank_account_id' => $bank->id, 'amount' => 800.00,
            'transaction_date' => now()->toDateString(), 'payment_method' => 'Opening Balance',
            'description' => 'Opening',
        ]);

        $this->assertNull(JournalEntry::where('reference', 'TXN-' . $txn->id)->first());
    }

    public function test_ledger_cash_ties_to_bank_account_balances(): void
    {
        BankAccount::factory()->create(['current_balance' => 3000.00]);
        BankAccount::factory()->create(['current_balance' => 1500.50]);

        app(JournalService::class)->backfillCash();

        $bs = app(\App\Services\Accounting\LedgerReportService::class)->balanceSheet();
        $expected = round(BankAccount::sum('current_balance'), 2);

        $this->assertEqualsWithDelta($expected, $bs['totalCashBank'], 0.01);
        $this->assertEqualsWithDelta($expected, $bs['openingBalanceEquity'], 0.01);
        $this->assertEqualsWithDelta($bs['totalAssets'], $bs['totalLiabilitiesAndEquity'], 0.01);
    }

    public function test_backfill_cash_is_idempotent(): void
    {
        $bank = BankAccount::factory()->create(['current_balance' => 2500.00]);
        Transaction::create([
            'type' => 'income', 'bank_account_id' => $bank->id, 'amount' => 400.00,
            'transaction_date' => now()->toDateString(), 'payment_method' => 'Bank Transfer',
            'description' => 'Interest',
        ]);

        $svc = app(JournalService::class);
        $svc->backfillCash();
        $firstCash = app(\App\Services\Accounting\LedgerReportService::class)->balanceSheet()['totalCashBank'];
        $count = JournalEntry::where('is_posted', true)->count();

        $svc->backfillCash();
        $secondCash = app(\App\Services\Accounting\LedgerReportService::class)->balanceSheet()['totalCashBank'];

        $this->assertEqualsWithDelta($firstCash, $secondCash, 0.01);
        $this->assertSame($count, JournalEntry::where('is_posted', true)->count());
        $this->assertSame(1, JournalEntry::where('reference', 'BANK-' . $bank->id . '-OPEN')->count());
    }
}
