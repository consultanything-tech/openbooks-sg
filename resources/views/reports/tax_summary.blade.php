@extends('layouts.app')

@section('title', 'Tax Summary Report')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Tax Liability Summary</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Sales tax collected on customer invoices vs input tax paid on vendor bills</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.profit_loss') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Profit & Loss</a>
            <a href="{{ route('reports.balance_sheet') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Balance Sheet</a>
            <a href="{{ route('reports.trial_balance') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Trial Balance</a>
            <a href="{{ route('reports.income_expense') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Monthly Cashflow</a>
            <a href="{{ route('reports.tax_summary') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/20 shadow-sm">Tax Summary</a>
            <a href="{{ route('reports.ar_aging') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">AR Aging</a>
            <a href="{{ route('reports.ap_aging') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">AP Aging</a>
            <a href="{{ route('reports.gst_f5') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">GST F5</a>
        </div>
    </div>

    <!-- Date Range Filter -->
    <form method="GET" action="{{ route('reports.tax_summary') }}" class="px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm flex items-center gap-3 text-xs">
        <span class="text-slate-500 dark:text-slate-400 font-semibold whitespace-nowrap">Period:</span>
        <input type="date" name="start_date" value="{{ $startDate }}" class="input-date bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
        <span class="text-slate-400 dark:text-slate-500">to</span>
        <input type="date" name="end_date" value="{{ $endDate }}" class="input-date bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
        <button type="submit" class="btn btn-primary"><i data-lucide="refresh-cw" aria-hidden="true"></i> Update</button>
        <a href="{{ route('reports.tax_summary.export_csv', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-secondary">
            <i data-lucide="file-spreadsheet" aria-hidden="true"></i> Export CSV
        </a>
    </form>

    <!-- Tax Metric Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80">
            <span class="text-xs text-slate-600 dark:text-slate-400 uppercase font-semibold">Output Tax Collected (Sales)</span>
            <h3 class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">{{ $currencySymbol }}{{ number_format($collectedTax, 2) }}</h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">From issued customer invoices</p>
        </div>
        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80">
            <span class="text-xs text-slate-600 dark:text-slate-400 uppercase font-semibold">Input Tax Paid (Purchases)</span>
            <h3 class="text-2xl font-bold text-red-600 dark:text-red-400 mt-2">{{ $currencySymbol }}{{ number_format($paidTax, 2) }}</h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">From vendor purchase bills</p>
        </div>
        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80">
            <span class="text-xs text-slate-600 dark:text-slate-400 uppercase font-semibold">Net Tax Liability Due</span>
            <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-2">{{ $currencySymbol }}{{ number_format($netTaxDue, 2) }}</h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Estimated remittance to authorities</p>
        </div>
    </div>
</div>
@endsection
