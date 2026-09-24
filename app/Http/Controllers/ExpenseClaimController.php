<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Company;
use App\Models\ExpenseClaim;
use App\Models\Transaction;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseClaimController extends Controller
{
    use LogsActivity;

    public function index(Request $request)
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';

        $query = ExpenseClaim::with(['user', 'category'])->latest();

        // Non-admin users only see their own claims
        if (strtoupper(Auth::user()->role) === 'VIEWER' || strtoupper(Auth::user()->role) === 'ACCOUNTANT') {
            $query->where('user_id', Auth::id());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $claims = $query->paginate(15);
        $categories = Category::where('type', 'expense')->get();

        return view('expense-claims.index', compact('claims', 'company', 'currencySymbol', 'categories'));
    }

    public function create()
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $categories = Category::where('type', 'expense')->get();
        $lastId = ExpenseClaim::withTrashed()->max('id') ?? 0;
        $nextNumber = 'EXP-'.date('Y').'-'.str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);

        return view('expense-claims.create', compact('company', 'categories', 'nextNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'claim_number' => 'required|string|unique:expense_claims,claim_number',
            'claim_date' => 'required|date',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0.01',
            'category_id' => 'nullable|exists:categories,id',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }

        $claim = ExpenseClaim::create([
            'claim_number' => $validated['claim_number'],
            'user_id' => Auth::id(),
            'claim_date' => $validated['claim_date'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'total_amount' => $validated['total_amount'],
            'status' => $request->input('status', 'submitted'),
            'category_id' => $validated['category_id'] ?? null,
            'receipt_path' => $receiptPath,
        ]);

        $this->logActivity('created', "Submitted expense claim {$claim->claim_number}", 'ExpenseClaim', $claim->id);

        return redirect()->route('expense_claims.index')->with('success', 'Expense claim submitted.');
    }

    public function show($id)
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';
        $claim = ExpenseClaim::with(['user', 'approver', 'category'])->findOrFail($id);
        $bankAccounts = BankAccount::all();

        return view('expense-claims.show', compact('claim', 'company', 'currencySymbol', 'bankAccounts'));
    }

    public function approve(Request $request, $id)
    {
        $claim = ExpenseClaim::findOrFail($id);

        if ($claim->status !== 'submitted') {
            return back()->with('error', 'Only submitted claims can be approved.');
        }

        $claim->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now()->toDateString(),
        ]);

        $this->logActivity('approved', "Approved expense claim {$claim->claim_number}", 'ExpenseClaim', $claim->id);

        return back()->with('success', 'Expense claim approved.');
    }

    public function reject(Request $request, $id)
    {
        $claim = ExpenseClaim::findOrFail($id);

        $claim->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('rejection_reason', 'Rejected by admin'),
            'approved_by' => Auth::id(),
            'approved_at' => now()->toDateString(),
        ]);

        $this->logActivity('rejected', "Rejected expense claim {$claim->claim_number}", 'ExpenseClaim', $claim->id);

        return back()->with('success', 'Expense claim rejected.');
    }

    public function markPaid(Request $request, $id)
    {
        $claim = ExpenseClaim::findOrFail($id);

        if ($claim->status !== 'approved') {
            return back()->with('error', 'Only approved claims can be marked as paid.');
        }

        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
        ]);

        DB::transaction(function () use ($claim, $validated) {
            $claim->update(['status' => 'paid']);

            $bankAccount = BankAccount::find($validated['bank_account_id']);
            if ($bankAccount) {
                $bankAccount->decrement('current_balance', $claim->total_amount);
            }

            Transaction::create([
                'type' => 'expense',
                'bank_account_id' => $validated['bank_account_id'],
                'category_id' => $claim->category_id,
                'amount' => $claim->total_amount,
                'payment_method' => 'Bank Transfer',
                'reference_number' => $claim->claim_number,
                'transaction_date' => now()->toDateString(),
                'description' => "Expense claim: {$claim->title} ({$claim->claim_number})",
            ]);
        });

        $this->logActivity('paid', "Paid expense claim {$claim->claim_number}", 'ExpenseClaim', $claim->id);

        return back()->with('success', 'Expense claim marked as paid. Transaction recorded.');
    }

    public function destroy($id)
    {
        $claim = ExpenseClaim::findOrFail($id);

        if ($claim->status === 'paid') {
            return back()->with('error', 'Cannot delete a paid expense claim.');
        }

        $claim->delete();
        $this->logActivity('deleted', "Deleted expense claim {$claim->claim_number}", 'ExpenseClaim', $claim->id);

        return redirect()->route('expense_claims.index')
            ->with('success', 'Expense claim deleted.')
            ->with('undo_url', route('expense_claims.restore', $claim->id))
            ->with('undo_label', 'Undo');
    }

    /** Restore a soft-deleted expense claim (the "Undo" action on the delete toast). */
    public function restore($id)
    {
        $claim = ExpenseClaim::withTrashed()->findOrFail($id);

        if (! $claim->trashed()) {
            return redirect()->route('expense_claims.index')->with('info', 'That expense claim is already active.');
        }

        $claim->restore();
        $this->logActivity('restored', "Restored expense claim {$claim->claim_number}", 'ExpenseClaim', $claim->id);

        return redirect()->route('expense_claims.index')->with('success', "Expense claim {$claim->claim_number} restored.");
    }
}
