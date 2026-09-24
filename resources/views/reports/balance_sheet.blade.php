@extends('layouts.app')

@section('title', 'Balance Sheet')

@section('content')
<div class="space-y-6">
    {{-- Header and Subnav --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Financial Reports</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Comprehensive accounting statements, audit ledgers, and tax liabilities</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('reports.profit_loss') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Profit & Loss</a>
            <a href="{{ route('reports.balance_sheet') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/20 shadow-sm">Balance Sheet</a>
            <a href="{{ route('reports.trial_balance') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Trial Balance</a>
            <a href="{{ route('reports.income_expense') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Monthly Cashflow</a>
            <a href="{{ route('reports.tax_summary') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Tax Summary</a>
            <a href="{{ route('reports.ar_aging') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">AR Aging</a>
            <a href="{{ route('reports.ap_aging') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">AP Aging</a>
            <a href="{{ route('reports.gst_f5') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">GST F5</a>
        </div>
    </div>

    {{-- Date Filter --}}
    <form method="GET" action="{{ route('reports.balance_sheet') }}" class="px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm flex items-center gap-3 text-xs">
        <span class="text-slate-500 dark:text-slate-400 font-semibold whitespace-nowrap">As of Date:</span>
        <input type="date" name="as_of" value="{{ $asOfDate }}" class="input-date bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
        <button type="submit" class="btn btn-primary"><i data-lucide="refresh-cw" aria-hidden="true"></i> Update</button>
        <a href="{{ route('reports.balance_sheet.export_csv', ['as_of' => $asOfDate]) }}" class="btn btn-secondary">
            <i data-lucide="file-spreadsheet" aria-hidden="true"></i> Export CSV
        </a>
    </form>

    {{-- Balance Sheet --}}
    <div class="p-8 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm max-w-3xl mx-auto space-y-6">
        {{-- Title Block --}}
        <div class="text-center border-b border-slate-100 dark:border-slate-800 pb-4">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Balance Sheet</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">As of {{ date('M d, Y', strtotime($asOfDate)) }}</p>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 uppercase tracking-wider font-semibold">{{ $company->name ?? 'OpenBooks Enterprise' }}</p>
        </div>

        {{-- ASSETS Section --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Assets</h3>
            </div>

            <div class="p-4 rounded-xl space-y-2.5 text-xs border border-slate-100 dark:border-slate-800 divide-y divide-slate-50 dark:divide-slate-800 bg-white dark:bg-slate-900/60">
                <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider pb-1">Cash & Bank Accounts</div>
                @forelse($bankAccounts as $account)
                    <div class="flex justify-between text-slate-700 dark:text-slate-300 pt-2">
                        <span class="font-medium pl-4">{{ $account->name }} <span class="text-slate-400 dark:text-slate-500 text-[11px]">({{ $account->bank_name ?? $account->type }})</span></span>
                        <span class="font-semibold text-slate-900 dark:text-white tabular-nums text-right min-w-[100px]">{{ $currencySymbol }}{{ number_format($account->current_balance, 2) }}</span>
                    </div>
                @empty
                    <div class="text-slate-400 dark:text-slate-500 text-center py-2 pt-2">No active bank accounts.</div>
                @endforelse
                <div class="flex justify-between text-slate-700 dark:text-slate-300 pt-2 font-semibold">
                    <span class="pl-4">Total Cash & Bank</span>
                    <span class="tabular-nums text-right min-w-[100px]">{{ $currencySymbol }}{{ number_format($totalCashBank, 2) }}</span>
                </div>
            </div>

            <div class="p-4 rounded-xl space-y-2.5 text-xs border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900/60">
                <div class="flex justify-between text-slate-700 dark:text-slate-300">
                    <span class="font-medium pl-4">Accounts Receivable</span>
                    <span class="font-semibold text-slate-900 dark:text-white tabular-nums text-right min-w-[100px]">{{ $currencySymbol }}{{ number_format($accountsReceivable, 2) }}</span>
                </div>
            </div>

            <div class="flex justify-between text-xs font-bold text-slate-900 dark:text-white px-2 pt-1">
                <span>Total Assets</span>
                <span class="text-emerald-700 dark:text-emerald-400 font-extrabold text-sm tabular-nums">{{ $currencySymbol }}{{ number_format($totalAssets, 2) }}</span>
            </div>
        </div>

        {{-- LIABILITIES Section --}}
        <div class="space-y-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-red-700 dark:text-red-400 uppercase tracking-wider">Liabilities</h3>
            </div>

            <div class="p-4 rounded-xl space-y-2.5 text-xs border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900/60">
                <div class="flex justify-between text-slate-700 dark:text-slate-300">
                    <span class="font-medium pl-4">Accounts Payable</span>
                    <span class="font-semibold text-slate-900 dark:text-white tabular-nums text-right min-w-[100px]">{{ $currencySymbol }}{{ number_format($accountsPayable, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-700 dark:text-slate-300 pt-2 border-t border-slate-50 dark:border-slate-800">
                    <span class="font-medium pl-4">GST Payable (net)</span>
                    <span class="font-semibold text-slate-900 dark:text-white tabular-nums text-right min-w-[100px]">{{ $currencySymbol }}{{ number_format($gstPayable, 2) }}</span>
                </div>
            </div>

            <div class="flex justify-between text-xs font-bold text-slate-900 dark:text-white px-2 pt-1">
                <span>Total Liabilities</span>
                <span class="text-red-700 dark:text-red-400 font-extrabold text-sm tabular-nums">{{ $currencySymbol }}{{ number_format($totalLiabilities, 2) }}</span>
            </div>
        </div>

        {{-- EQUITY Section --}}
        <div class="space-y-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">Equity</h3>
            </div>

            <div class="p-4 rounded-xl space-y-2.5 text-xs border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900/60">
                <div class="flex justify-between text-slate-700 dark:text-slate-300">
                    <span class="font-medium pl-4">Owner's Equity</span>
                    <span class="font-semibold text-slate-900 dark:text-white tabular-nums text-right min-w-[100px]">{{ $currencySymbol }}{{ number_format($ownersEquity, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-700 dark:text-slate-300">
                    <span class="font-medium pl-4">Opening Balance Equity</span>
                    <span class="font-semibold text-slate-900 dark:text-white tabular-nums text-right min-w-[100px]">{{ $currencySymbol }}{{ number_format($openingBalanceEquity, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-700 dark:text-slate-300 pt-2 border-t border-slate-50 dark:border-slate-800">
                    <span class="font-medium pl-4">Retained Earnings</span>
                    <span class="font-semibold text-slate-900 dark:text-white tabular-nums text-right min-w-[100px]">{{ $currencySymbol }}{{ number_format($retainedEarnings, 2) }}</span>
                </div>
            </div>

            <div class="flex justify-between text-xs font-bold text-slate-900 dark:text-white px-2 pt-1">
                <span>Total Equity</span>
                <span class="text-indigo-700 dark:text-indigo-400 font-extrabold text-sm tabular-nums">{{ $currencySymbol }}{{ number_format($totalEquity, 2) }}</span>
            </div>
        </div>

        {{-- Total Liabilities + Equity --}}
        <div class="pt-5 border-t-2 border-slate-200 dark:border-slate-700 space-y-3">
            <div class="p-4 rounded-xl border border-emerald-200 dark:border-emerald-500/20 bg-emerald-50/40 dark:bg-emerald-500/5 flex justify-between items-center text-sm font-bold shadow-sm">
                <span class="text-slate-900 dark:text-white">Total Liabilities + Equity</span>
                <span class="text-emerald-700 dark:text-emerald-400 font-black text-lg tabular-nums">{{ $currencySymbol }}{{ number_format($totalLiabilitiesAndEquity, 2) }}</span>
            </div>
            @if(abs($totalAssets - $totalLiabilitiesAndEquity) > 0.01)
                <div class="p-3.5 rounded-xl border border-amber-200 dark:border-amber-500/20 bg-amber-50/40 dark:bg-amber-500/5 flex justify-between items-center text-xs">
                    <span class="text-amber-700 dark:text-amber-400 font-medium">Difference (Assets - Liabilities - Equity)</span>
                    <span class="font-bold text-amber-700 dark:text-amber-400 tabular-nums">{{ $currencySymbol }}{{ number_format($totalAssets - $totalLiabilitiesAndEquity, 2) }}</span>
                </div>
            @else
                <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 flex justify-between items-center text-xs">
                    <span class="text-slate-500 dark:text-slate-400 font-medium">Balance Check</span>
                    <span class="font-bold text-emerald-700 dark:text-emerald-400">Balanced</span>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
