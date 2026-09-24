@extends('layouts.app')

@section('title', 'Profit & Loss Statement')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Financial Reports</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Comprehensive accounting statements, audit ledgers, and tax liabilities</p>
        </div>
        <!-- Report Subnav -->
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.profit_loss') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/20 shadow-sm">Profit & Loss</a>
            <a href="{{ route('reports.balance_sheet') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Balance Sheet</a>
            <a href="{{ route('reports.trial_balance') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Trial Balance</a>
            <a href="{{ route('reports.income_expense') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Monthly Cashflow</a>
            <a href="{{ route('reports.tax_summary') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Tax Summary</a>
            <a href="{{ route('reports.ar_aging') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">AR Aging</a>
            <a href="{{ route('reports.ap_aging') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">AP Aging</a>
            <a href="{{ route('reports.gst_f5') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">GST F5</a>
        </div>
    </div>

    <!-- Date Range Filter -->
    <form method="GET" action="{{ route('reports.profit_loss') }}" class="px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm flex items-center gap-3 text-xs">
        <span class="text-slate-500 dark:text-slate-400 font-semibold whitespace-nowrap">Reporting Period:</span>
        <input type="date" name="start_date" value="{{ $startDate }}" class="input-date bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
        <span class="text-slate-400 dark:text-slate-500 font-medium">to</span>
        <input type="date" name="end_date" value="{{ $endDate }}" class="input-date bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
        <button type="submit" class="btn btn-primary"><i data-lucide="refresh-cw" aria-hidden="true"></i> Update</button>
        <a href="{{ route('reports.profit_loss.export_csv', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-secondary">
            <i data-lucide="file-spreadsheet" aria-hidden="true"></i> Export CSV
        </a>
    </form>

    <!-- P&L Sheet -->
    <div class="p-8 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm max-w-3xl mx-auto space-y-6">
        <div class="text-center border-b border-slate-100 dark:border-slate-800 pb-4">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Profit & Loss Statement</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">For period: {{ date('M d, Y', strtotime($startDate)) }} &ndash; {{ date('M d, Y', strtotime($endDate)) }}</p>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 uppercase tracking-wider font-semibold">{{ $company->name ?? 'OpenBooks Enterprise' }} &bull; Standard Financial Year: {{ $company->financial_year ?? 'April - March' }}</p>
        </div>

        <!-- Operating Revenue -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">1. Operating Income / Revenue</h3>
                <span class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">Sales & Cash Inflow</span>
            </div>

            <div class="p-4 rounded-xl space-y-2.5 text-xs border border-slate-100 dark:border-slate-800 divide-y divide-slate-50 dark:divide-slate-800 bg-white dark:bg-slate-900/60">
                <div class="flex justify-between text-slate-700 dark:text-slate-300 pt-1 first:pt-0">
                    <span class="font-medium">Gross Sales &amp; Invoiced Billings <span class="text-slate-400 text-[10px]">(incl. GST)</span></span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($grossSales, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-500 dark:text-slate-400 pt-2 text-[11px]">
                    <span>Total Cash Collections Received</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ $currencySymbol }}{{ number_format($cashIncome, 2) }}</span>
                </div>
            </div>

            <div class="flex justify-between text-xs font-bold text-slate-900 dark:text-white px-2 pt-1">
                <span>Total Revenue (Accrual, excl. GST)</span>
                <span class="text-emerald-700 dark:text-emerald-400 font-extrabold text-sm">{{ $currencySymbol }}{{ number_format($netRevenue, 2) }}</span>
            </div>
        </div>

        <!-- Operating Expenses -->
        <div class="space-y-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-red-700 dark:text-red-400 uppercase tracking-wider">2. Operating Expenses & Bills</h3>
                <span class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">Categorized Outflow</span>
            </div>

            <div class="p-4 rounded-xl space-y-2 text-xs divide-y divide-slate-100 dark:divide-slate-800 border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900/60">
                @forelse($expensesByCategory as $ec)
                    <div class="flex justify-between text-slate-700 dark:text-slate-300 pt-2 first:pt-0">
                        <span class="font-medium">{{ $ec->name }}</span>
                        <span class="font-semibold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($ec->transactions_sum_amount ?? 0, 2) }}</span>
                    </div>
                @empty
                    <div class="text-slate-400 dark:text-slate-500 text-center py-2">No category expenses recorded in this period.</div>
                @endforelse
            </div>

            <div class="flex justify-between text-xs font-bold text-slate-900 dark:text-white px-2 pt-1">
                <span>Total Operating Expenses (Accrual, excl. GST)</span>
                <span class="text-red-700 dark:text-red-400 font-extrabold text-sm">{{ $currencySymbol }}{{ number_format($netExpense, 2) }}</span>
            </div>
        </div>

        <!-- Net Profit Summary -->
        <div class="pt-5 border-t-2 border-slate-200 dark:border-slate-700 space-y-3">
            <div class="p-4 rounded-xl border border-emerald-200 dark:border-emerald-500/20 bg-emerald-50/40 dark:bg-emerald-500/5 flex justify-between items-center text-sm font-bold shadow-sm">
                <span class="text-slate-900 dark:text-white">Net Income (Accrual Profit)</span>
                <span class="{{ $netProfit >= 0 ? 'text-emerald-700 dark:text-emerald-400 font-black text-lg' : 'text-red-700 dark:text-red-400 font-black text-lg' }}">
                    {{ $currencySymbol }}{{ number_format($netProfit, 2) }}
                </span>
            </div>
            <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 flex justify-between items-center text-xs">
                <span class="text-slate-500 dark:text-slate-400 font-medium">Net Cash Inflow (Cash Basis)</span>
                <span class="font-bold {{ $netCashFlow >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400' }}">
                    {{ $currencySymbol }}{{ number_format($netCashFlow, 2) }}
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
