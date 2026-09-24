<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\JournalEntryLine;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ledger-derived financial statements.
 *
 * Stage 2: the Balance Sheet is computed from the double-entry ledger for the
 * accrual figures (AR, AP, GST, revenue, expense), while cash is taken from the
 * BankAccount balances (the operational system of record, so it always ties to
 * the Banking page). Owner's equity is the residual, which makes the statement
 * balance by construction without double-counting cash.
 */
class LedgerReportService
{
    /** Net balance of a single account as of a date (positive in its normal direction). */
    public function accountBalance(string $code, ?string $asOf = null, string $normal = 'debit'): float
    {
        $q = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('accounts.code', $code)
            ->where('journal_entries.is_posted', true);
        if ($asOf) {
            $q->whereDate('journal_entries.entry_date', '<=', $asOf);
        }
        $row = $q->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')->first();
        $d = (float) $row->d;
        $c = (float) $row->c;

        return round($normal === 'debit' ? $d - $c : $c - $d, 2);
    }

    /** Net balance of all accounts of a type as of a date (positive in normal direction). */
    public function typeBalance(string $type, ?string $asOf = null): float
    {
        $normal = in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit';
        $q = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('accounts.type', $type)
            ->where('journal_entries.is_posted', true);
        if ($asOf) {
            $q->whereDate('journal_entries.entry_date', '<=', $asOf);
        }
        $row = $q->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')->first();
        $d = (float) $row->d;
        $c = (float) $row->c;

        return round($normal === 'debit' ? $d - $c : $c - $d, 2);
    }

    /**
     * Net income from standalone cash transactions (not tied to an invoice/bill,
     * which are already recognised in the ledger). Keeps owner's equity from
     * absorbing genuine operational income/expenses.
     */
    public function standaloneNetIncome(?string $asOf = null): float
    {
        $t = $this->standaloneTotals(null, $asOf);

        return round($t['income'] - $t['expense'], 2);
    }

    /**
     * Income/expense totals from standalone transactions (no invoice/bill link).
     * Pass $start and $end for a period, or only $end for "up to" a date.
     */
    public function standaloneTotals(?string $start = null, ?string $end = null): array
    {
        // Cash movements that are NOT profit & loss: internal transfers between
        // bank accounts and opening-balance postings. Both write a transactions
        // row tagged with their payment_method, so exclude them from income/expense.
        $q = Transaction::query()
            ->whereNull('invoice_id')
            ->whereNull('bill_id')
            ->whereNotIn('payment_method', ['Transfer', 'Opening Balance']);
        if ($start && $end) {
            $q->whereBetween('transaction_date', [$start, $end]);
        } elseif ($end) {
            $q->whereDate('transaction_date', '<=', $end);
        }
        $income = (float) (clone $q)->where('type', 'income')->sum('amount');
        $expense = (float) $q->where('type', 'expense')->sum('amount');

        return ['income' => round($income, 2), 'expense' => round($expense, 2)];
    }

    /** Net balance of all accounts of a type within a date range (normal direction). */
    public function periodTypeBalance(string $type, string $start, string $end): float
    {
        $normal = in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit';
        $row = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('accounts.type', $type)
            ->where('journal_entries.is_posted', true)
            ->whereBetween('journal_entries.entry_date', [$start, $end])
            ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
            ->first();
        $d = (float) $row->d;
        $c = (float) $row->c;

        return round($normal === 'debit' ? $d - $c : $c - $d, 2);
    }

    /**
     * Accrual profit & loss for a period, net of GST, entirely from the ledger.
     * Standalone cash transactions are now posted to ledger revenue/expense
     * accounts by TransactionObserver, so no separate addition is needed.
     */
    public function profitLoss(string $start, string $end): array
    {
        $revenue = round($this->periodTypeBalance('revenue', $start, $end), 2);
        $expense = round($this->periodTypeBalance('expense', $start, $end), 2);

        return [
            'netRevenue' => $revenue,
            'netExpense' => $expense,
            'netProfit' => round($revenue - $expense, 2),
        ];
    }

    /**
     * Trial Balance: every account's net debit/credit balance as of a date.
     * Because each journal entry is balanced, total debits must equal total
     * credits — this is the integrity check for the whole ledger.
     */
    public function trialBalance(?string $asOf = null): array
    {
        $asOf = $asOf ?: now()->toDateString();

        $rows = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.is_posted', true)
            ->whereDate('journal_entries.entry_date', '<=', $asOf)
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->select(
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('COALESCE(SUM(journal_entry_lines.debit),0) as d'),
                DB::raw('COALESCE(SUM(journal_entry_lines.credit),0) as c')
            )
            ->get();

        $accounts = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        foreach ($rows as $r) {
            $net = round((float) $r->d - (float) $r->c, 2);
            if (abs($net) < 0.005) {
                continue; // zero-balance accounts are omitted from a trial balance
            }
            $debit = $net > 0 ? $net : 0.0;
            $credit = $net < 0 ? round(-$net, 2) : 0.0;
            $totalDebit += $debit;
            $totalCredit += $credit;
            $accounts[] = [
                'code' => $r->code,
                'name' => $r->name,
                'type' => $r->type,
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        $totalDebit = round($totalDebit, 2);
        $totalCredit = round($totalCredit, 2);

        return [
            'asOfDate' => $asOf,
            'accounts' => $accounts,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'balanced' => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }

    /**
     * Build the balance sheet figures. Returns an array ready for the view/CSV.
     */
    public function balanceSheet(?string $asOf = null): array
    {
        $asOf = $asOf ?: now()->toDateString();

        // Cash is now read from the ledger (per-bank sub-accounts under 1010).
        // This ties to the Banking page because backfillCash reconciles them.
        $bankAccounts = BankAccount::all();
        $cash = 0.0;
        foreach ($bankAccounts as $b) {
            if ($b->account_id) {
                $acct = Account::find($b->account_id);
                if ($acct) {
                    $cash += $this->accountBalance($acct->code, $asOf, 'debit');
                }
            }
        }
        $cash = round($cash, 2);

        $accountsReceivable = $this->accountBalance('1100', $asOf, 'debit');
        $accountsPayable = $this->accountBalance('2000', $asOf, 'credit');
        $gstPayable = $this->accountBalance('2100', $asOf, 'credit');

        $revenue = $this->typeBalance('revenue', $asOf);
        $expense = $this->typeBalance('expense', $asOf);

        $retainedEarnings = round($revenue - $expense, 2);

        // Opening Balance Equity (3200) holds the reconciling plug for bank openings
        $openingBalanceEquity = $this->accountBalance('3200', $asOf, 'credit');

        $totalAssets = round($cash + $accountsReceivable, 2);
        $totalLiabilities = round($accountsPayable + $gstPayable, 2);

        // Owner's equity is the residual so the statement balances by construction.
        $ownersEquity = round($totalAssets - $totalLiabilities - $retainedEarnings - $openingBalanceEquity, 2);
        $totalEquity = round($ownersEquity + $retainedEarnings + $openingBalanceEquity, 2);
        $totalLiabilitiesAndEquity = round($totalLiabilities + $totalEquity, 2);

        return [
            'asOfDate' => $asOf,
            'bankAccounts' => $bankAccounts,
            'totalCashBank' => $cash,
            'accountsReceivable' => $accountsReceivable,
            'totalAssets' => $totalAssets,
            'accountsPayable' => $accountsPayable,
            'gstPayable' => $gstPayable,
            'totalLiabilities' => $totalLiabilities,
            'retainedEarnings' => $retainedEarnings,
            'openingBalanceEquity' => $openingBalanceEquity,
            'ownersEquity' => $ownersEquity,
            'totalEquity' => $totalEquity,
            'totalLiabilitiesAndEquity' => $totalLiabilitiesAndEquity,
        ];
    }

    /**
     * General Ledger for a single account: opening balance, every posted journal
     * line in the period with a running balance (in the account's normal
     * direction), and the closing balance. Returns null for an unknown code.
     */
    public function generalLedger(string $code, ?string $start = null, ?string $end = null): ?array
    {
        $account = Account::where('code', $code)->first();
        if (! $account) {
            return null;
        }

        $end = $end ?: now()->toDateString();
        $normal = in_array($account->type, ['asset', 'expense'], true) ? 'debit' : 'credit';

        $opening = 0.0;
        if ($start) {
            $o = JournalEntryLine::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                ->where('journal_entry_lines.account_id', $account->id)
                ->where('journal_entries.is_posted', true)
                ->whereDate('journal_entries.entry_date', '<', $start)
                ->selectRaw('COALESCE(SUM(journal_entry_lines.debit),0) as d, COALESCE(SUM(journal_entry_lines.credit),0) as c')
                ->first();
            $net = (float) $o->d - (float) $o->c;
            $opening = round($normal === 'debit' ? $net : -$net, 2);
        }

        $q = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.account_id', $account->id)
            ->where('journal_entries.is_posted', true)
            ->whereDate('journal_entries.entry_date', '<=', $end);
        if ($start) {
            $q->whereDate('journal_entries.entry_date', '>=', $start);
        }

        $raw = $q->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_entry_lines.id')
            ->select(
                'journal_entries.entry_date',
                'journal_entries.entry_number',
                'journal_entries.description as entry_description',
                'journal_entries.reference',
                'journal_entry_lines.description as line_description',
                'journal_entry_lines.debit',
                'journal_entry_lines.credit'
            )
            ->get();

        $running = $opening;
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $lines = [];
        foreach ($raw as $r) {
            $d = round((float) $r->debit, 2);
            $c = round((float) $r->credit, 2);
            $totalDebit += $d;
            $totalCredit += $c;
            $running = round($normal === 'debit' ? $running + $d - $c : $running + $c - $d, 2);
            $lines[] = [
                'date' => Carbon::parse($r->entry_date)->toDateString(),
                'entry_number' => $r->entry_number,
                'description' => $r->line_description ?: $r->entry_description,
                'reference' => $r->reference,
                'debit' => $d,
                'credit' => $c,
                'balance' => $running,
            ];
        }

        return [
            'account' => $account,
            'normal' => $normal,
            'startDate' => $start,
            'endDate' => $end,
            'opening' => $opening,
            'lines' => $lines,
            'totalDebit' => round($totalDebit, 2),
            'totalCredit' => round($totalCredit, 2),
            'closing' => $running,
        ];
    }
}
