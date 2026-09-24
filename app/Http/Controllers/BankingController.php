<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Company;
use App\Models\Transaction;
use App\Services\Accounting\JournalService;
use App\Services\OfxParser;
use App\Services\QboParser;
use App\Traits\LogsActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class BankingController extends Controller
{
    use LogsActivity;

    public function index()
    {
        $accounts = BankAccount::withCount('transactions')->get();
        $totalBalance = $accounts->sum('current_balance');
        $recentTransactions = Transaction::with(['bankAccount', 'category'])->latest()->take(10)->get();
        $company = Company::first();

        return view('banking.index', compact('accounts', 'totalBalance', 'recentTransactions', 'company'));
    }

    public function store(Request $request, JournalService $journal)
    {
        $request->validate([
            'account_name' => 'required|string|max:100',
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50|unique:bank_accounts,account_number',
            'ifsc_code' => 'nullable|string|max:20',
            'branch_name' => 'nullable|string|max:100',
            'account_type' => 'nullable|string|max:50',
            'upi_id' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
            'opening_balance' => 'required|numeric|min:0',
        ]);

        $account = BankAccount::create([
            'name' => $request->account_name,
            'type' => $request->account_type ?: 'bank',
            'account_type' => $request->account_type ?: 'Current Account',
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'ifsc_code' => strtoupper($request->ifsc_code ?? ''),
            'branch_name' => $request->branch_name,
            'upi_id' => $request->upi_id,
            'currency' => $request->currency ?: 'SGD',
            'opening_balance' => $request->opening_balance,
            'current_balance' => $request->opening_balance,
            'bank_address' => $request->bank_address,
            'status' => 'active',
        ]);

        // Post opening balance to the ledger: DR Bank / CR Opening Balance Equity
        if ($request->opening_balance > 0) {
            $journal->postOpeningBalance($account, (float) $request->opening_balance);

            Transaction::create([
                'bank_account_id' => $account->id,
                'type' => 'income',
                'amount' => $request->opening_balance,
                'transaction_date' => now()->toDateString(),
                'payment_method' => 'Opening Balance',
                'description' => 'Opening balance for '.$account->name,
            ]);
        }

        $this->logActivity('created', "Created bank account {$account->name}", 'BankAccount', $account->id);

        return redirect()->route('banking.index')->with('success', 'Bank account registered successfully.');
    }

    public function transferForm()
    {
        $accounts = BankAccount::orderBy('name')->get();

        return view('banking.transfer', compact('accounts'));
    }

    public function transfer(Request $request, JournalService $journal)
    {
        $request->validate([
            'from_account_id' => 'required|exists:bank_accounts,id',
            'to_account_id' => 'required|exists:bank_accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'transfer_date' => 'required|date',
        ]);

        $fromAcc = BankAccount::findOrFail($request->from_account_id);
        $toAcc = BankAccount::findOrFail($request->to_account_id);

        if ($fromAcc->current_balance < $request->amount) {
            return back()->withInput()->with('error', 'Insufficient funds in '.$fromAcc->name);
        }

        DB::beginTransaction();
        try {
            $amount = (float) $request->amount;
            $fromAcc->decrement('current_balance', $amount);
            $toAcc->increment('current_balance', $amount);

            $ref = 'TRF-'.strtoupper(uniqid());

            // Debit from source
            Transaction::create([
                'bank_account_id' => $fromAcc->id,
                'type' => 'expense',
                'amount' => $amount,
                'transaction_date' => $request->transfer_date,
                'payment_method' => 'Transfer',
                'reference_number' => $ref,
                'description' => 'Internal transfer to '.$toAcc->name,
            ]);

            // Credit to destination
            Transaction::create([
                'bank_account_id' => $toAcc->id,
                'type' => 'income',
                'amount' => $amount,
                'transaction_date' => $request->transfer_date,
                'payment_method' => 'Transfer',
                'reference_number' => $ref,
                'description' => 'Internal transfer from '.$fromAcc->name,
            ]);

            // Post to the double-entry ledger: DR destination bank / CR source bank
            $journal->postTransfer($fromAcc, $toAcc, $amount, $request->transfer_date, $ref);

            DB::commit();
            $this->logActivity('created', 'Transferred S$'.number_format($amount, 2)." from {$fromAcc->name} to {$toAcc->name}", 'BankAccount', $fromAcc->id);

            return redirect()->route('banking.index')->with('success', 'Transfer of S$'.number_format($amount, 2).' executed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Transfer failed: '.$e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $account = BankAccount::findOrFail($id);

        $request->validate([
            'account_name' => 'required|string|max:100',
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50|unique:bank_accounts,account_number,'.$account->id,
            'ifsc_code' => 'nullable|string|max:20',
            'branch_name' => 'nullable|string|max:100',
            'account_type' => 'nullable|string|max:50',
            'upi_id' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
            'opening_balance' => 'required|numeric|min:0',
        ]);

        $account->update([
            'name' => $request->account_name,
            'account_type' => $request->account_type ?: 'Current Account',
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'ifsc_code' => strtoupper($request->ifsc_code ?? ''),
            'branch_name' => $request->branch_name,
            'upi_id' => $request->upi_id,
            'currency' => $request->currency ?: 'SGD',
            'opening_balance' => $request->opening_balance,
            'bank_address' => $request->bank_address,
        ]);

        $this->logActivity('updated', "Updated bank account {$account->name}", 'BankAccount', $account->id);

        return redirect()->route('banking.index')->with('success', 'Bank account updated successfully.');
    }

    public function destroy($id)
    {
        $account = BankAccount::findOrFail($id);

        if ($account->transactions()->count() > 0) {
            return redirect()->route('banking.index')->with('error', 'Cannot delete bank account with existing transactions.');
        }

        $this->logActivity('deleted', "Deleted bank account {$account->name}", 'BankAccount', $account->id);
        $account->delete();

        return redirect()->route('banking.index')->with('success', 'Bank account deleted successfully.');
    }

    public function exportTransactions(Request $request)
    {
        $query = Transaction::with(['bankAccount', 'category'])->latest();

        if ($request->filled('bank_account_id')) {
            $query->where('bank_account_id', $request->bank_account_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        $transactions = $query->get();

        $headers = ['Date', 'Type', 'Description', 'Category', 'Amount', 'Payment Method', 'Reference'];
        $rows = [];
        foreach ($transactions as $tx) {
            $rows[] = [
                $tx->transaction_date,
                $tx->type,
                $tx->description,
                $tx->category->name ?? 'General',
                number_format($tx->amount, 2),
                $tx->payment_method ?? 'Bank',
                $tx->reference_number,
            ];
        }

        return $this->buildCsvResponse('transactions.csv', $headers, $rows);
    }

    public function importTransactions(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt,ofx,qfx,qbo|max:5120',
            'bank_account_id' => 'required|exists:bank_accounts,id',
        ]);

        $account = BankAccount::findOrFail($request->bank_account_id);
        $file = $request->file('csv_file');
        $extension = strtolower($file->getClientOriginalExtension());

        // Route to the appropriate parser based on file extension
        if (in_array($extension, ['ofx', 'qfx'])) {
            return $this->importOfxFile($file->getRealPath(), $account, 'OFX');
        }

        if ($extension === 'qbo') {
            return $this->importOfxFile($file->getRealPath(), $account, 'QBO');
        }

        // Default: CSV import (existing logic)
        return $this->importCsvFile($file->getRealPath(), $account);
    }

    private function importCsvFile(string $filePath, BankAccount $account)
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return redirect()->route('banking.transactions')->with('error', 'Unable to read the uploaded file.');
        }

        $header = fgetcsv($handle, 0, ',');
        if ($header === false) {
            fclose($handle);

            return redirect()->route('banking.transactions')->with('error', 'The CSV file is empty or malformed.');
        }

        // Normalize header names
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $imported = 0;
        $skipped = 0;
        $balanceAdjustment = 0;
        $rowNum = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                $rowNum++;

                // Skip empty rows
                if (count($row) === 1 && trim($row[0]) === '') {
                    continue;
                }

                try {
                    $data = array_combine($header, array_pad($row, count($header), ''));
                    if ($data === false) {
                        $skipped++;

                        continue;
                    }

                    $type = strtolower(trim($data['type'] ?? ''));
                    $amount = floatval(str_replace(',', '', $data['amount'] ?? '0'));
                    $date = trim($data['date'] ?? '');

                    if (! in_array($type, ['income', 'expense']) || $amount <= 0 || empty($date)) {
                        $skipped++;

                        continue;
                    }

                    // Try parsing the date
                    try {
                        $parsedDate = Carbon::parse($date)->toDateString();
                    } catch (\Exception $e) {
                        $skipped++;

                        continue;
                    }

                    // Look up category by name if provided
                    $categoryId = null;
                    $categoryName = trim($data['category'] ?? '');
                    if (! empty($categoryName)) {
                        $category = Category::where('name', $categoryName)->first();
                        if ($category) {
                            $categoryId = $category->id;
                        }
                    }

                    Transaction::create([
                        'bank_account_id' => $account->id,
                        'type' => $type,
                        'amount' => $amount,
                        'transaction_date' => $parsedDate,
                        'description' => trim($data['description'] ?? ''),
                        'category_id' => $categoryId,
                        'payment_method' => trim($data['payment method'] ?? '') ?: 'Bank',
                        'reference_number' => trim($data['reference'] ?? ''),
                    ]);

                    if ($type === 'income') {
                        $balanceAdjustment += $amount;
                    } else {
                        $balanceAdjustment -= $amount;
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $skipped++;
                }
            }

            // Update bank account balance
            $account->increment('current_balance', $balanceAdjustment);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);

            return redirect()->route('banking.transactions')->with('error', 'Import failed: '.$e->getMessage());
        }

        fclose($handle);

        $this->logActivity('imported', "Imported {$imported} transactions from CSV into {$account->name} ({$skipped} skipped)", 'Transaction');

        return redirect()->route('banking.transactions')
            ->with('success', "CSV import complete: {$imported} transactions imported, {$skipped} rows skipped.");
    }

    private function importOfxFile(string $filePath, BankAccount $account, string $format)
    {
        $content = file_get_contents($filePath);

        if ($content === false || trim($content) === '') {
            return redirect()->route('banking.transactions')->with('error', 'The uploaded file is empty or unreadable.');
        }

        // QBO files can be either OFX format or CSV format - try OFX first, fall back to QBO CSV parser
        if ($format === 'QBO') {
            $parser = new QboParser;
            $parsed = $parser->parse($content);

            // If QBO CSV parser found nothing, try OFX parser (some .qbo files are actually OFX)
            if (empty($parsed)) {
                $ofxParser = new OfxParser;
                $parsed = $ofxParser->parse($content);
            }
        } else {
            $parser = new OfxParser;
            $parsed = $parser->parse($content);
        }

        if (empty($parsed)) {
            return redirect()->route('banking.transactions')->with('error', "No transactions found in the {$format} file. Ensure it is a valid bank export.");
        }

        $imported = 0;
        $skipped = 0;
        $balanceAdjustment = 0;

        DB::beginTransaction();
        try {
            foreach ($parsed as $tx) {
                try {
                    $type = $tx['type'] === 'credit' ? 'income' : ($tx['type'] === 'debit' ? 'expense' : $tx['type']);
                    if (! in_array($type, ['income', 'expense'])) {
                        $type = $tx['amount'] >= 0 ? 'income' : 'expense';
                    }

                    Transaction::create([
                        'bank_account_id' => $account->id,
                        'type' => $type,
                        'amount' => abs($tx['amount']),
                        'transaction_date' => $tx['date'],
                        'description' => $tx['description'],
                        'payment_method' => 'Bank',
                        'reference_number' => $tx['reference'] ?? '',
                    ]);

                    if ($type === 'income') {
                        $balanceAdjustment += abs($tx['amount']);
                    } else {
                        $balanceAdjustment -= abs($tx['amount']);
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $skipped++;
                }
            }

            $account->increment('current_balance', $balanceAdjustment);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('banking.transactions')->with('error', "{$format} import failed: ".$e->getMessage());
        }

        $this->logActivity('imported', "Imported {$imported} transactions from {$format} file into {$account->name} ({$skipped} skipped)", 'Transaction');

        return redirect()->route('banking.transactions')
            ->with('success', "{$format} import complete: {$imported} transactions imported, {$skipped} rows skipped.");
    }

    public function importTemplate()
    {
        $headers = ['Date', 'Type', 'Description', 'Amount', 'Category', 'Payment Method', 'Reference'];
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers, ',', '"', '\\');
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="transactions_import_template.csv"',
        ]);
    }

    private function buildCsvResponse(string $filename, array $headers, array $rows): Response
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers, ',', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($output, $row, ',', '"', '\\');
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function reconcile($bankAccountId)
    {
        $account = BankAccount::findOrFail($bankAccountId);

        $unreconciledTransactions = Transaction::where('bank_account_id', $account->id)
            ->where('is_reconciled', false)
            ->orderBy('transaction_date', 'asc')
            ->get();

        $reconciledTransactions = Transaction::where('bank_account_id', $account->id)
            ->where('is_reconciled', true)
            ->orderBy('reconciled_at', 'desc')
            ->get();

        $reconciledIncome = $reconciledTransactions->where('type', 'income')->sum('amount');
        $reconciledExpense = $reconciledTransactions->where('type', 'expense')->sum('amount');
        $reconciledBalance = (float) $account->opening_balance + $reconciledIncome - $reconciledExpense;

        return view('banking.reconcile', compact(
            'account',
            'unreconciledTransactions',
            'reconciledTransactions',
            'reconciledBalance'
        ));
    }

    public function processReconcile(Request $request, $bankAccountId)
    {
        $request->validate([
            'transaction_ids' => 'required|array|min:1',
            'transaction_ids.*' => 'exists:transactions,id',
            'statement_balance' => 'required|numeric',
        ]);

        $account = BankAccount::findOrFail($bankAccountId);

        Transaction::where('bank_account_id', $account->id)
            ->whereIn('id', $request->transaction_ids)
            ->update([
                'is_reconciled' => true,
                'reconciled_at' => now(),
            ]);

        // Calculate reconciled balance after marking
        $reconciledTx = Transaction::where('bank_account_id', $account->id)
            ->where('is_reconciled', true)
            ->get();

        $reconciledIncome = $reconciledTx->where('type', 'income')->sum('amount');
        $reconciledExpense = $reconciledTx->where('type', 'expense')->sum('amount');
        $reconciledBalance = (float) $account->opening_balance + $reconciledIncome - $reconciledExpense;

        $statementBalance = (float) $request->statement_balance;
        $difference = round($statementBalance - $reconciledBalance, 2);

        $this->logActivity('reconciled', 'Reconciled '.count($request->transaction_ids)." transactions for {$account->name}", 'BankAccount', $account->id);

        if ($difference == 0) {
            return redirect()->route('banking.reconcile', $account->id)
                ->with('success', 'Reconciliation complete. Balances match perfectly.');
        }

        return redirect()->route('banking.reconcile', $account->id)
            ->with('warning', 'Transactions reconciled. There is a difference of '.($difference >= 0 ? '+' : '').number_format($difference, 2).' between the statement balance and reconciled balance.');
    }

    public function unreconcile($transactionId)
    {
        $transaction = Transaction::findOrFail($transactionId);

        $transaction->update([
            'is_reconciled' => false,
            'reconciled_at' => null,
        ]);

        return redirect()->route('banking.reconcile', $transaction->bank_account_id)
            ->with('success', 'Transaction un-reconciled successfully.');
    }

    public function transactions(Request $request)
    {
        $query = Transaction::with(['bankAccount', 'category'])->latest();

        if ($request->filled('bank_account_id')) {
            $query->where('bank_account_id', $request->bank_account_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        $transactions = $query->paginate(25);
        $accounts = BankAccount::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('banking.transactions', compact('transactions', 'accounts', 'categories'));
    }
}
