<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsController extends Controller
{
    use \App\Traits\LogsActivity;

    /**
     * List all accounts grouped by type.
     */
    public function index()
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $accounts = Account::orderBy('code')->get()->groupBy('type');

        return view('chart-of-accounts.index', compact('accounts', 'company'));
    }

    /**
     * Create new account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:accounts,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'sub_type' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:accounts,id',
        ]);

        $account = Account::create($validated);

        $this->logActivity('created', "Created account {$account->code} - {$account->name}", 'Account', $account->id);

        return redirect()->back()->with('success', "Account {$account->code} - {$account->name} created successfully.");
    }

    /**
     * Update account (cannot change type/code on system accounts).
     */
    public function update(Request $request, $id)
    {
        $account = Account::findOrFail($id);

        $rules = [
            'name' => 'required|string|max:255',
            'sub_type' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:accounts,id',
        ];

        // Allow code and type changes only for non-system accounts
        if (!$account->is_system) {
            $rules['code'] = 'required|string|max:20|unique:accounts,code,' . $account->id;
            $rules['type'] = 'required|in:asset,liability,equity,revenue,expense';
        }

        $validated = $request->validate($rules);

        $account->update($validated);

        $this->logActivity('updated', "Updated account {$account->code} - {$account->name}", 'Account', $account->id);

        return redirect()->back()->with('success', "Account {$account->code} updated successfully.");
    }

    /**
     * Delete account (not system accounts, not accounts with journal lines).
     */
    public function destroy($id)
    {
        $account = Account::findOrFail($id);

        if ($account->is_system) {
            return redirect()->back()->with('error', 'System accounts cannot be deleted.');
        }

        $hasJournalLines = JournalEntryLine::where('account_id', $account->id)->exists();
        if ($hasJournalLines) {
            return redirect()->back()->with('error', 'Cannot delete an account that has journal entry lines.');
        }

        $account->delete();

        $this->logActivity('deleted', "Deleted account {$account->code} - {$account->name}", 'Account', $account->id);

        return redirect()->back()->with('success', "Account {$account->code} deleted successfully.");
    }

    /**
     * List journal entries with pagination and date filter.
     */
    public function journalEntries(Request $request)
    {
        $query = JournalEntry::with('lines.account')->latest('entry_date');

        if ($request->filled('date_from')) {
            $query->whereDate('entry_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('entry_date', '<=', $request->date_to);
        }

        $entries = $query->paginate(15);
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);

        return view('chart-of-accounts.journal-entries', compact('entries', 'company'));
    }

    /**
     * Show form to create manual journal entry.
     */
    public function createJournalEntry()
    {
        $accounts = Account::orderBy('code')->get();

        return view('chart-of-accounts.create-journal-entry', compact('accounts'));
    }

    /**
     * Create journal entry with lines.
     */
    public function storeJournalEntry(Request $request)
    {
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|max:500',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($validated['lines'] as $line) {
            $totalDebits += (float) ($line['debit'] ?? 0);
            $totalCredits += (float) ($line['credit'] ?? 0);
        }

        if (round($totalDebits, 2) !== round($totalCredits, 2)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Total debits (' . number_format($totalDebits, 2) . ') must equal total credits (' . number_format($totalCredits, 2) . ').');
        }

        $journalEntry = DB::transaction(function () use ($validated) {
            // Auto-generate entry number: JE-YYYY-NNNN
            $year = date('Y');
            $lastEntry = JournalEntry::where('entry_number', 'like', "JE-{$year}-%")
                ->orderByDesc('entry_number')
                ->first();

            if ($lastEntry) {
                $lastSeq = (int) substr($lastEntry->entry_number, -4);
                $nextSeq = $lastSeq + 1;
            } else {
                $nextSeq = 1;
            }

            $entryNumber = 'JE-' . $year . '-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

            $entry = JournalEntry::create([
                'entry_number' => $entryNumber,
                'entry_date' => $validated['entry_date'],
                'description' => $validated['description'],
            ]);

            foreach ($validated['lines'] as $line) {
                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'debit' => (float) ($line['debit'] ?? 0),
                    'credit' => (float) ($line['credit'] ?? 0),
                ]);
            }

            // Recalculate balances for affected accounts
            $accountIds = collect($validated['lines'])->pluck('account_id')->unique();
            foreach (Account::whereIn('id', $accountIds)->get() as $account) {
                $account->recalculateBalance();
            }

            return $entry;
        });

        $this->logActivity('created', "Created journal entry {$journalEntry->entry_number}", 'JournalEntry', $journalEntry->id);

        return redirect()->route('accounts.journal_entries')->with('success', "Journal entry {$journalEntry->entry_number} created successfully.");
    }

    /**
     * Show trial balance report.
     */
    public function trialBalance()
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);

        $accounts = Account::orderBy('code')->get()->map(function ($account) {
            $lines = JournalEntryLine::where('account_id', $account->id);
            $account->total_debit = $lines->sum('debit');
            $account->total_credit = $lines->sum('credit');
            return $account;
        })->filter(function ($account) {
            return $account->total_debit > 0 || $account->total_credit > 0;
        });

        return view('chart-of-accounts.trial-balance', compact('accounts', 'company'));
    }

    /**
     * Seed default Singapore Chart of Accounts if accounts table is empty.
     */
    public function seedDefaults()
    {
        if (Account::count() > 0) {
            return redirect()->back()->with('error', 'Accounts already exist. Seeding is only allowed when the accounts table is empty.');
        }

        $defaults = [
            // Assets
            ['code' => '1000', 'name' => 'Cash', 'type' => 'asset', 'sub_type' => 'current_asset'],
            ['code' => '1010', 'name' => 'Bank', 'type' => 'asset', 'sub_type' => 'current_asset'],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset', 'sub_type' => 'current_asset'],
            ['code' => '1200', 'name' => 'Inventory', 'type' => 'asset', 'sub_type' => 'current_asset'],
            ['code' => '1500', 'name' => 'Fixed Assets', 'type' => 'asset', 'sub_type' => 'fixed_asset'],
            // Liabilities
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'sub_type' => 'current_liability'],
            ['code' => '2100', 'name' => 'GST Payable', 'type' => 'liability', 'sub_type' => 'current_liability'],
            ['code' => '2200', 'name' => 'Loans', 'type' => 'liability', 'sub_type' => 'long_term_liability'],
            // Equity
            ['code' => '3000', 'name' => "Owner's Equity", 'type' => 'equity', 'sub_type' => 'equity'],
            ['code' => '3100', 'name' => 'Retained Earnings', 'type' => 'equity', 'sub_type' => 'equity'],
            ['code' => '3200', 'name' => 'Opening Balance Equity', 'type' => 'equity', 'sub_type' => 'equity'],
            // Revenue
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'revenue', 'sub_type' => 'revenue'],
            ['code' => '4100', 'name' => 'Service Revenue', 'type' => 'revenue', 'sub_type' => 'revenue'],
            // Expenses
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'sub_type' => 'cost_of_goods_sold'],
            ['code' => '5100', 'name' => 'Operating Expenses', 'type' => 'expense', 'sub_type' => 'operating_expense'],
            ['code' => '5200', 'name' => 'Rent', 'type' => 'expense', 'sub_type' => 'operating_expense'],
            ['code' => '5300', 'name' => 'Utilities', 'type' => 'expense', 'sub_type' => 'operating_expense'],
            ['code' => '5400', 'name' => 'Salaries', 'type' => 'expense', 'sub_type' => 'operating_expense'],
            ['code' => '5500', 'name' => 'Marketing', 'type' => 'expense', 'sub_type' => 'operating_expense'],
            ['code' => '5600', 'name' => 'Depreciation', 'type' => 'expense', 'sub_type' => 'operating_expense'],
        ];

        foreach ($defaults as $data) {
            Account::create(array_merge($data, [
                'is_system' => true,
                'balance' => 0,
            ]));
        }

        $this->logActivity('seeded', 'Seeded default Singapore Chart of Accounts', 'Account', null);

        return redirect()->back()->with('success', 'Default Singapore Chart of Accounts has been created with ' . count($defaults) . ' accounts.');
    }
}
