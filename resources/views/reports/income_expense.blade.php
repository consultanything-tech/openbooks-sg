@extends('layouts.app')

@section('title', 'Monthly Cash Flow Report')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Monthly Cash Flow Breakdown</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">12-month income vs expense performance</p>
            <a href="{{ route('reports.income_expense.export_csv', ['year' => $year]) }}" class="btn btn-secondary mt-2">
                <i data-lucide="file-spreadsheet" aria-hidden="true"></i> Export CSV
            </a>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.profit_loss') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Profit & Loss</a>
            <a href="{{ route('reports.balance_sheet') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Balance Sheet</a>
            <a href="{{ route('reports.trial_balance') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Trial Balance</a>
            <a href="{{ route('reports.income_expense') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/20 shadow-sm">Monthly Cashflow</a>
            <a href="{{ route('reports.tax_summary') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Tax Summary</a>
            <a href="{{ route('reports.ar_aging') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">AR Aging</a>
            <a href="{{ route('reports.ap_aging') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">AP Aging</a>
            <a href="{{ route('reports.gst_f5') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">GST F5</a>
        </div>
    </div>

    <!-- Annual Table -->
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">Month</th>
                        <th class="py-3.5 px-4 text-right">Income Cash In</th>
                        <th class="py-3.5 px-4 text-right">Expense Cash Out</th>
                        <th class="py-3.5 px-4 text-right">Net Flow</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @foreach($monthlyData as $row)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">{{ $row['month'] }} {{ $year }}</td>
                            <td class="py-3.5 px-4 text-right font-semibold text-emerald-600 dark:text-emerald-400">{{ $currencySymbol }}{{ number_format($row['income'], 2) }}</td>
                            <td class="py-3.5 px-4 text-right font-semibold text-red-600 dark:text-red-400">{{ $currencySymbol }}{{ number_format($row['expense'], 2) }}</td>
                            <td class="py-3.5 px-4 text-right font-bold {{ $row['profit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $currencySymbol }}{{ number_format($row['profit'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
