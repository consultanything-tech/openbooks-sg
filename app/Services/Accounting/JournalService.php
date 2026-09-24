<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Double-entry posting layer.
 *
 * Stage 1 of the ledger work: this service posts balanced journal entries
 * alongside the existing single-source documents (invoices / bills) WITHOUT
 * changing how any report is computed. It is deliberately defensive — posting
 * is wrapped in a transaction and never throws, so a ledger problem can never
 * break invoice/bill creation. Entries are idempotent (keyed on `reference`),
 * so re-saving a document or replaying an event will not double-post.
 *
 * Account codes match the seeded chart of accounts (ChartOfAccountsController).
 */
class JournalService
{
    /** Default system accounts used for posting (code => [name, type, sub_type]). */
    private const ACCOUNTS = [
        '1010' => ['Bank', 'asset', 'current_asset'],
        '1100' => ['Accounts Receivable', 'asset', 'current_asset'],
        '2000' => ['Accounts Payable', 'liability', 'current_liability'],
        '2100' => ['GST Payable', 'liability', 'current_liability'],
        '3200' => ['Opening Balance Equity', 'equity', 'equity'],
        '4000' => ['Sales Revenue', 'revenue', 'revenue'],
        '5100' => ['Operating Expenses', 'expense', 'operating_expense'],
    ];

    /** Resolve (or lazily create) a system account by code. */
    public function account(string $code): Account
    {
        $existing = Account::where('code', $code)->first();
        if ($existing) {
            return $existing;
        }

        [$name, $type, $subType] = self::ACCOUNTS[$code]
            ?? ['Account ' . $code, 'asset', 'current_asset'];

        return Account::create([
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'sub_type' => $subType,
            'is_system' => true,
            'is_active' => true,
            'balance' => 0,
        ]);
    }

    /**
     * Resolve (creating + mapping if needed) the dedicated ledger account for a
     * bank/cash account. Each BankAccount gets its own chart-of-accounts entry
     * under the 1010 Bank parent so that per-account GL drill-down works.
     */
    public function bankLedgerAccount(BankAccount $bank): Account
    {
        if ($bank->account_id) {
            $existing = Account::find($bank->account_id);
            if ($existing) {
                return $existing;
            }
        }

        $parent = $this->account('1010');
        $code = $this->allocateBankCode();

        $account = Account::create([
            'code' => $code,
            'name' => $bank->name ?: ('Bank ' . $code),
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'parent_id' => $parent->id,
            'is_system' => false,
            'is_active' => true,
            'balance' => 0,
        ]);

        $bank->account_id = $account->id;
        $bank->save();

        return $account;
    }

    /** Allocate the next free account code in the 1011–1099 range for bank sub-accounts. */
    private function allocateBankCode(): string
    {
        $taken = Account::where('code', 'like', '10%')->pluck('code')->all();
        for ($n = 1011; $n <= 1099; $n++) {
            if (!in_array((string) $n, $taken, true)) {
                return (string) $n;
            }
        }
        return '10' . Str::lower(Str::random(3));
    }

    /**
     * Post a balanced entry. Each line: ['account' => code, 'debit' => x, 'credit' => y, 'desc' => ?].
     * Idempotent on $reference; silently skips unbalanced or empty entries.
     */
    public function post(string $reference, string $referenceType, ?int $referenceId, string $date, string $description, array $lines): ?JournalEntry
    {
        try {
            if (JournalEntry::where('reference', $reference)->exists()) {
                return null; // already posted
            }

            $debits = 0.0;
            $credits = 0.0;
            foreach ($lines as $line) {
                $debits += round((float) ($line['debit'] ?? 0), 2);
                $credits += round((float) ($line['credit'] ?? 0), 2);
            }
            if ($debits <= 0 || abs($debits - $credits) >= 0.01) {
                Log::warning('JournalService: skipped unbalanced/empty entry', [
                    'reference' => $reference, 'debits' => $debits, 'credits' => $credits,
                ]);
                return null;
            }

            return DB::transaction(function () use ($reference, $referenceType, $referenceId, $date, $description, $lines) {
                $entry = JournalEntry::create([
                    'entry_number' => 'JE-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                    'entry_date' => $date,
                    'description' => $description,
                    'reference' => $reference,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'created_by' => auth()->id(),
                    'is_posted' => true,
                ]);

                $touched = [];
                foreach ($lines as $line) {
                    $account = $this->account($line['account']);
                    $entry->lines()->create([
                        'account_id' => $account->id,
                        'debit' => round((float) ($line['debit'] ?? 0), 2),
                        'credit' => round((float) ($line['credit'] ?? 0), 2),
                        'description' => $line['desc'] ?? $description,
                    ]);
                    $touched[$account->id] = $account;
                }

                foreach ($touched as $account) {
                    $account->recalculateBalance();
                }

                return $entry;
            });
        } catch (\Throwable $e) {
            Log::warning('JournalService: posting failed for ' . $reference . ' — ' . $e->getMessage());
            return null;
        }
    }

    /** Sales invoice issued: DR Accounts Receivable, CR Revenue, CR GST Payable. */
    public function postInvoiceAccrual(Invoice $invoice): ?JournalEntry
    {
        $total = round((float) $invoice->total, 2);
        $tax = round((float) $invoice->tax_total, 2);
        $subtotal = round($total - $tax, 2);
        if ($total <= 0) {
            return null;
        }

        $lines = [
            ['account' => '1100', 'debit' => $total, 'credit' => 0, 'desc' => 'Receivable ' . $invoice->invoice_number],
            ['account' => '4000', 'debit' => 0, 'credit' => $subtotal, 'desc' => 'Sales revenue'],
        ];
        if ($tax > 0) {
            $lines[] = ['account' => '2100', 'debit' => 0, 'credit' => $tax, 'desc' => 'Output GST'];
        }

        return $this->post(
            $invoice->invoice_number . '-ACCRUAL',
            'Invoice',
            $invoice->id,
            optional($invoice->invoice_date)->format('Y-m-d') ?? now()->toDateString(),
            'Sales invoice ' . $invoice->invoice_number,
            $lines
        );
    }

    /** Vendor bill received: DR Operating Expenses, DR GST input credit, CR Accounts Payable. */
    public function postBillAccrual(Bill $bill): ?JournalEntry
    {
        $total = round((float) $bill->total, 2);
        $tax = round((float) $bill->tax_total, 2);
        $subtotal = round($total - $tax, 2);
        if ($total <= 0) {
            return null;
        }

        $lines = [
            ['account' => '5100', 'debit' => $subtotal, 'credit' => 0, 'desc' => 'Purchase / expense'],
            ['account' => '2000', 'debit' => 0, 'credit' => $total, 'desc' => 'Payable ' . $bill->bill_number],
        ];
        if ($tax > 0) {
            $lines[] = ['account' => '2100', 'debit' => $tax, 'credit' => 0, 'desc' => 'Input GST credit'];
        }

        return $this->post(
            $bill->bill_number . '-ACCRUAL',
            'Bill',
            $bill->id,
            optional($bill->bill_date)->format('Y-m-d') ?? now()->toDateString(),
            'Vendor bill ' . $bill->bill_number,
            $lines
        );
    }

    /** Customer payment received: DR Bank(per-account), CR Accounts Receivable. */
    public function postInvoicePayment(Invoice $invoice, float $cumulativePaid, ?string $date = null, ?BankAccount $bank = null): ?JournalEntry
    {
        $amount = round($cumulativePaid, 2);
        if ($amount <= 0) {
            return null;
        }

        $bankCode = $bank ? $this->bankLedgerAccount($bank)->code : '1010';

        return $this->post(
            $invoice->invoice_number . '-PAY-' . number_format($amount, 2, '.', ''),
            'Invoice',
            $invoice->id,
            $date ?? now()->toDateString(),
            'Payment received for ' . $invoice->invoice_number,
            [
                ['account' => $bankCode, 'debit' => $amount, 'credit' => 0, 'desc' => 'Cash/bank in'],
                ['account' => '1100', 'debit' => 0, 'credit' => $amount, 'desc' => 'Settle receivable'],
            ]
        );
    }

    /** Vendor bill paid: DR Accounts Payable, CR Bank(per-account). */
    public function postBillPayment(Bill $bill, float $cumulativePaid, ?string $date = null, ?BankAccount $bank = null): ?JournalEntry
    {
        $amount = round($cumulativePaid, 2);
        if ($amount <= 0) {
            return null;
        }

        $bankCode = $bank ? $this->bankLedgerAccount($bank)->code : '1010';

        return $this->post(
            $bill->bill_number . '-PAY-' . number_format($amount, 2, '.', ''),
            'Bill',
            $bill->id,
            $date ?? now()->toDateString(),
            'Payment made for ' . $bill->bill_number,
            [
                ['account' => '2000', 'debit' => $amount, 'credit' => 0, 'desc' => 'Settle payable'],
                ['account' => $bankCode, 'debit' => 0, 'credit' => $amount, 'desc' => 'Cash/bank out'],
            ]
        );
    }

    /** Opening balance for a bank account: DR Bank, CR Opening Balance Equity (3200). */
    public function postOpeningBalance(BankAccount $bank, float $amount, ?string $date = null): ?JournalEntry
    {
        $amount = round($amount, 2);
        if ($amount == 0) {
            return null;
        }

        $code = $this->bankLedgerAccount($bank)->code;

        return $this->post(
            'BANK-' . $bank->id . '-OPEN',
            'BankAccount',
            $bank->id,
            $date ?? now()->toDateString(),
            'Opening balance for ' . $bank->name,
            [
                ['account' => $code, 'debit' => abs($amount), 'credit' => 0, 'desc' => 'Opening cash'],
                ['account' => '3200', 'debit' => 0, 'credit' => abs($amount), 'desc' => 'Opening balance equity'],
            ]
        );
    }

    /** Internal transfer between bank accounts: DR destination bank, CR source bank. */
    public function postTransfer(BankAccount $from, BankAccount $to, float $amount, ?string $date = null, ?string $ref = null): ?JournalEntry
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $fromCode = $this->bankLedgerAccount($from)->code;
        $toCode = $this->bankLedgerAccount($to)->code;
        $reference = 'TRF-' . ($ref ?: ($from->id . '-' . $to->id . '-' . number_format($amount, 2, '.', '')));

        return $this->post(
            $reference,
            'Transfer',
            $from->id,
            $date ?? now()->toDateString(),
            'Transfer ' . $from->name . ' → ' . $to->name,
            [
                ['account' => $toCode, 'debit' => $amount, 'credit' => 0, 'desc' => 'Cash in'],
                ['account' => $fromCode, 'debit' => 0, 'credit' => $amount, 'desc' => 'Cash out'],
            ]
        );
    }

    /**
     * Post the cash leg for a Transaction record. Called by TransactionObserver.
     * Skips transfers and opening-balance rows (handled explicitly by their controllers).
     * Returns null if the transaction cannot be posted (no bank account, zero amount, etc).
     */
    public function postTransactionCashLeg(Transaction $t): ?JournalEntry
    {
        $amount = round((float) $t->amount, 2);
        if ($amount == 0) {
            return null;
        }

        // Skip special payment methods handled explicitly elsewhere
        if (in_array($t->payment_method, ['Transfer', 'Opening Balance'], true)) {
            return null;
        }

        // Cannot post a cash leg without knowing which bank account
        if (!$t->bank_account_id) {
            return null;
        }

        $bank = BankAccount::find($t->bank_account_id);
        if (!$bank) {
            return null;
        }

        $bankCode = $this->bankLedgerAccount($bank)->code;
        $ref = 'TXN-' . $t->id;
        $date = optional($t->transaction_date)->format('Y-m-d') ?? now()->toDateString();
        $absAmount = abs($amount);

        // Invoice payment received: DR Bank / CR AR
        if ($t->type === 'income' && $t->invoice_id) {
            return $this->post($ref, 'Transaction', $t->id, $date, 'Payment received (Invoice #' . $t->invoice_id . ')', [
                ['account' => $bankCode, 'debit' => $absAmount, 'credit' => 0, 'desc' => 'Cash in'],
                ['account' => '1100', 'debit' => 0, 'credit' => $absAmount, 'desc' => 'Settle receivable'],
            ]);
        }

        // Bill payment made: DR AP / CR Bank
        if ($t->type === 'expense' && $t->bill_id) {
            return $this->post($ref, 'Transaction', $t->id, $date, 'Payment made (Bill #' . $t->bill_id . ')', [
                ['account' => '2000', 'debit' => $absAmount, 'credit' => 0, 'desc' => 'Settle payable'],
                ['account' => $bankCode, 'debit' => 0, 'credit' => $absAmount, 'desc' => 'Cash out'],
            ]);
        }

        // Standalone cash income: DR Bank / CR Revenue
        if ($t->type === 'income' && $amount > 0) {
            return $this->post($ref, 'Transaction', $t->id, $date, $t->description ?: 'Cash income', [
                ['account' => $bankCode, 'debit' => $absAmount, 'credit' => 0, 'desc' => 'Cash in'],
                ['account' => '4000', 'debit' => 0, 'credit' => $absAmount, 'desc' => 'Revenue'],
            ]);
        }

        // Standalone cash expense: DR Expense / CR Bank
        if ($t->type === 'expense' && $amount > 0) {
            return $this->post($ref, 'Transaction', $t->id, $date, $t->description ?: 'Cash expense', [
                ['account' => '5100', 'debit' => $absAmount, 'credit' => 0, 'desc' => 'Expense'],
                ['account' => $bankCode, 'debit' => 0, 'credit' => $absAmount, 'desc' => 'Cash out'],
            ]);
        }

        return null;
    }

    /**
     * Backfill accrual entries for invoices/bills created before observers existed.
     * Cash legs are handled separately by backfillCash(). Idempotent.
     */
    public function backfillAll(): array
    {
        $invoices = 0;
        $bills = 0;

        Invoice::whereNotIn('status', ['draft', 'cancelled'])->orderBy('id')->each(function (Invoice $invoice) use (&$invoices) {
            $this->postInvoiceAccrual($invoice);
            $invoices++;
        });

        Bill::whereNotIn('status', ['draft', 'cancelled'])->orderBy('id')->each(function (Bill $bill) use (&$bills) {
            $this->postBillAccrual($bill);
            $bills++;
        });

        return ['invoices' => $invoices, 'bills' => $bills];
    }

    /**
     * Backfill the cash side of the ledger: opening balances, all transaction
     * cash legs, and transfers. After this runs, every bank account's ledger
     * balance equals its current_balance. Idempotent (keyed on references).
     *
     * The opening-balance entry acts as the reconciling plug: it equals
     * current_balance minus the net of all non-opening transactions, so the
     * ledger always ties to the Banking page regardless of historical gaps.
     */
    public function backfillCash(): array
    {
        $stats = ['banks' => 0, 'transactions' => 0, 'transfers' => 0];

        // 1. Ensure every bank account has a ledger account mapped
        BankAccount::orderBy('id')->each(function (BankAccount $bank) {
            $this->bankLedgerAccount($bank);
        });

        // 2. Post all transaction cash legs (income/expense, invoice/bill payments)
        Transaction::orderBy('id')->each(function (Transaction $t) use (&$stats) {
            if ($this->postTransactionCashLeg($t)) {
                $stats['transactions']++;
            }
        });

        // 3. Post transfers (grouped by reference_number, one entry per pair)
        $transferRefs = Transaction::where('payment_method', 'Transfer')
            ->whereNotNull('reference_number')
            ->pluck('reference_number')
            ->unique();

        foreach ($transferRefs as $ref) {
            $legs = Transaction::where('reference_number', $ref)
                ->where('payment_method', 'Transfer')
                ->orderBy('id')
                ->get();

            if ($legs->count() < 2) {
                continue;
            }

            $fromLeg = $legs->firstWhere('type', 'expense');
            $toLeg = $legs->firstWhere('type', 'income');

            if (!$fromLeg || !$toLeg) {
                continue;
            }

            $fromBank = BankAccount::find($fromLeg->bank_account_id);
            $toBank = BankAccount::find($toLeg->bank_account_id);

            if ($fromBank && $toBank) {
                $this->postTransfer(
                    $fromBank,
                    $toBank,
                    (float) $fromLeg->amount,
                    optional($fromLeg->transaction_date)->format('Y-m-d'),
                    $ref
                );
                $stats['transfers']++;
            }
        }

        // 4. Post reconciling opening balances so ledger cash == current_balance
        BankAccount::orderBy('id')->each(function (BankAccount $bank) use (&$stats) {
            $ledgerAcct = $this->bankLedgerAccount($bank);

            // Compute what the ledger already has for this bank account
            $ledgerBalance = (float) JournalEntry::join('journal_entry_lines', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                ->where('journal_entry_lines.account_id', $ledgerAcct->id)
                ->where('journal_entries.reference', '!=', 'BANK-' . $bank->id . '-OPEN')
                ->selectRaw('COALESCE(SUM(journal_entry_lines.debit),0) - COALESCE(SUM(journal_entry_lines.credit),0) as net')
                ->value('net');

            // The opening plug = current_balance - what transactions already explain
            $plug = round((float) $bank->current_balance - $ledgerBalance, 2);

            if (abs($plug) > 0.001) {
                $this->postOpeningBalance($bank, $plug, optional($bank->created_at)->format('Y-m-d') ?? now()->toDateString());
            }

            $stats['banks']++;
        });

        return $stats;
    }
}
