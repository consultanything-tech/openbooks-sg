<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Str;
use Tests\TestCase;

class JournalEntryTest extends TestCase
{
    public function test_balanced_entry_is_valid(): void
    {
        $entry = JournalEntry::create([
            'entry_number' => 'JE-TEST-'.Str::random(8),
            'entry_date' => '2026-01-15',
            'description' => 'Balanced test entry',
            'is_posted' => false,
        ]);

        $debitAccount = Account::create([
            'code' => '1000-'.Str::random(4),
            'name' => 'Test Cash Account',
            'type' => 'asset',
            'balance' => 0,
            'is_active' => true,
        ]);

        $creditAccount = Account::create([
            'code' => '4000-'.Str::random(4),
            'name' => 'Test Revenue Account',
            'type' => 'revenue',
            'balance' => 0,
            'is_active' => true,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $debitAccount->id,
            'debit' => 500.00,
            'credit' => 0,
            'description' => 'Debit line',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $creditAccount->id,
            'debit' => 0,
            'credit' => 500.00,
            'description' => 'Credit line',
        ]);

        $this->assertTrue($entry->isBalanced());
        $this->assertEqualsWithDelta(500.00, $entry->totalDebits(), 0.01);
        $this->assertEqualsWithDelta(500.00, $entry->totalCredits(), 0.01);
    }

    public function test_unbalanced_entry_is_invalid(): void
    {
        $entry = JournalEntry::create([
            'entry_number' => 'JE-UNBAL-'.Str::random(8),
            'entry_date' => '2026-01-15',
            'description' => 'Unbalanced test entry',
            'is_posted' => false,
        ]);

        $debitAccount = Account::create([
            'code' => '1100-'.Str::random(4),
            'name' => 'Test Debit Account',
            'type' => 'asset',
            'balance' => 0,
            'is_active' => true,
        ]);

        $creditAccount = Account::create([
            'code' => '4100-'.Str::random(4),
            'name' => 'Test Credit Account',
            'type' => 'revenue',
            'balance' => 0,
            'is_active' => true,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $debitAccount->id,
            'debit' => 750.00,
            'credit' => 0,
            'description' => 'Debit line',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $creditAccount->id,
            'debit' => 0,
            'credit' => 500.00,
            'description' => 'Credit line',
        ]);

        $this->assertFalse($entry->isBalanced());
    }

    public function test_account_balance_calculation(): void
    {
        $account = Account::create([
            'code' => '1200-'.Str::random(4),
            'name' => 'Balance Calc Test Account',
            'type' => 'asset',
            'balance' => 0,
            'is_active' => true,
        ]);

        $entry1 = JournalEntry::create([
            'entry_number' => 'JE-BAL1-'.Str::random(8),
            'entry_date' => '2026-01-15',
            'description' => 'Balance test entry 1',
            'is_posted' => true,
        ]);

        $entry2 = JournalEntry::create([
            'entry_number' => 'JE-BAL2-'.Str::random(8),
            'entry_date' => '2026-01-20',
            'description' => 'Balance test entry 2',
            'is_posted' => true,
        ]);

        // Debit 1000 to asset account
        JournalEntryLine::create([
            'journal_entry_id' => $entry1->id,
            'account_id' => $account->id,
            'debit' => 1000.00,
            'credit' => 0,
            'description' => 'Initial deposit',
        ]);

        // Credit 300 from asset account
        JournalEntryLine::create([
            'journal_entry_id' => $entry2->id,
            'account_id' => $account->id,
            'debit' => 0,
            'credit' => 300.00,
            'description' => 'Withdrawal',
        ]);

        $account->recalculateBalance();

        // Asset account: balance = debits - credits = 1000 - 300 = 700
        $this->assertEqualsWithDelta(700.00, (float) $account->balance, 0.01);
    }
}
