<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $company = Company::first() ?? new Company(['name' => 'OpenBooks SG', 'currency_symbol' => 'S$', 'currency_code' => 'SGD']);

        // Redirect to onboarding wizard for fresh installs
        $isFreshInstall = ! Customer::exists() && ! Invoice::exists();
        $onboardingDismissed = session('onboarding_dismissed', false);

        if ($isFreshInstall && ! $onboardingDismissed) {
            return redirect()->route('onboarding.index');
        }

        // KPI 1: Bank & Cash Balances
        $totalCash = BankAccount::sum('current_balance');
        $bankAccounts = BankAccount::orderByDesc('is_default')->get();

        // KPI 2: Total Receivables (Unpaid & Partial Invoices)
        $totalReceivables = Invoice::whereIn('status', ['draft', 'sent', 'partial', 'overdue'])->sum('due_amount');

        // KPI 3: Total Payables (Unpaid & Partial Bills)
        $totalPayables = Bill::whereIn('status', ['draft', 'received', 'partial', 'overdue'])->sum('due_amount');

        // KPI 4: Net Profit (All-time or YTD)
        $totalIncome = Transaction::where('type', 'income')->sum('amount');
        $totalExpense = Transaction::where('type', 'expense')->sum('amount');
        $netProfit = $totalIncome - $totalExpense;

        // Chart 1: Cash Flow (Last 6 Months Income vs Expense)
        $months = [];
        $incomeData = [];
        $expenseData = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthDate = Carbon::now()->subMonths($i);
            $monthKey = $monthDate->format('M Y');
            $year = $monthDate->year;
            $month = $monthDate->month;

            $months[] = $monthKey;

            $inc = Transaction::where('type', 'income')
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month)
                ->sum('amount');

            $exp = Transaction::where('type', 'expense')
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month)
                ->sum('amount');

            $incomeData[] = (float) $inc;
            $expenseData[] = (float) $exp;
        }

        // Chart 2: Expenses by Category (Donut)
        $expensesByCategory = Transaction::where('type', 'expense')
            ->whereNotNull('category_id')
            ->select('category_id', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('category_id')
            ->with('category')
            ->get();

        $categoryLabels = [];
        $categorySeries = [];
        $categoryColors = [];

        foreach ($expensesByCategory as $item) {
            $categoryLabels[] = $item->category->name ?? 'Uncategorized';
            $categorySeries[] = (float) $item->total_amount;
            $categoryColors[] = $item->category->color ?? '#10b981';
        }

        // Recent Invoices
        $recentInvoices = Invoice::with('customer')->latest()->take(5)->get();

        // Recent Transactions
        $recentTransactions = Transaction::with(['bankAccount', 'customer', 'vendor', 'category'])
            ->latest('transaction_date')
            ->take(6)
            ->get();

        // Cash Flow Forecast (next 30/60/90 days)
        $today = Carbon::today();
        $forecastLabels = ['Current', '30 Days', '60 Days', '90 Days'];
        $currentCash = (float) $totalCash;

        // Expected inflows: unpaid invoices due within each period
        $inflow30 = (float) Invoice::whereIn('status', ['sent', 'partial', 'overdue'])->where('due_date', '<=', $today->copy()->addDays(30))->sum('due_amount');
        $inflow60 = (float) Invoice::whereIn('status', ['sent', 'partial', 'overdue'])->where('due_date', '<=', $today->copy()->addDays(60))->sum('due_amount');
        $inflow90 = (float) Invoice::whereIn('status', ['sent', 'partial', 'overdue'])->where('due_date', '<=', $today->copy()->addDays(90))->sum('due_amount');

        // Expected outflows: unpaid bills due within each period
        $outflow30 = (float) Bill::whereIn('status', ['received', 'partial', 'overdue'])->where('due_date', '<=', $today->copy()->addDays(30))->sum('due_amount');
        $outflow60 = (float) Bill::whereIn('status', ['received', 'partial', 'overdue'])->where('due_date', '<=', $today->copy()->addDays(60))->sum('due_amount');
        $outflow90 = (float) Bill::whereIn('status', ['received', 'partial', 'overdue'])->where('due_date', '<=', $today->copy()->addDays(90))->sum('due_amount');

        $forecastData = [
            $currentCash,
            $currentCash + $inflow30 - $outflow30,
            $currentCash + $inflow60 - $outflow60,
            $currentCash + $inflow90 - $outflow90,
        ];
        $forecastInflows = [0, $inflow30, $inflow60, $inflow90];
        $forecastOutflows = [0, $outflow30, $outflow60, $outflow90];

        // Overdue invoices count
        $overdueInvoiceCount = Invoice::whereIn('status', ['sent', 'partial'])->where('due_date', '<', $today)->count();
        $overdueBillCount = Bill::whereIn('status', ['received', 'partial'])->where('due_date', '<', $today)->count();
        $overdueInvoiceTotal = (float) Invoice::whereIn('status', ['sent', 'partial'])->where('due_date', '<', $today)->sum('due_amount');

        return view('dashboard.index', compact(
            'company',
            'totalCash',
            'bankAccounts',
            'totalReceivables',
            'totalPayables',
            'totalIncome',
            'totalExpense',
            'netProfit',
            'months',
            'incomeData',
            'expenseData',
            'categoryLabels',
            'categorySeries',
            'categoryColors',
            'recentInvoices',
            'recentTransactions',
            'forecastLabels',
            'forecastData',
            'forecastInflows',
            'forecastOutflows',
            'overdueInvoiceCount',
            'overdueBillCount',
            'overdueInvoiceTotal'
        ));
    }
}
