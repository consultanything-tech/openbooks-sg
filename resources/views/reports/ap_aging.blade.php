@extends('layouts.app')

@section('title', 'Accounts Payable Aging')

@section('content')
<div class="space-y-6">
    <!-- Header + Report Subnav -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Accounts Payable Aging</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Outstanding vendor bills grouped by aging buckets &bull; As of {{ $today->format('M d, Y') }}</p>
            <a href="{{ route('reports.ap_aging.export_csv') }}" class="btn btn-secondary mt-2">
                <i data-lucide="file-spreadsheet" aria-hidden="true"></i> Export CSV
            </a>
        </div>
        <!-- Report Subnav -->
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('reports.profit_loss') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Profit & Loss</a>
            <a href="{{ route('reports.balance_sheet') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Balance Sheet</a>
            <a href="{{ route('reports.trial_balance') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Trial Balance</a>
            <a href="{{ route('reports.income_expense') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Monthly Cashflow</a>
            <a href="{{ route('reports.tax_summary') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Tax Summary</a>
            <a href="{{ route('reports.ar_aging') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">AR Aging</a>
            <a href="{{ route('reports.ap_aging') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/20 shadow-sm">AP Aging</a>
            <a href="{{ route('reports.gst_f5') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">GST F5</a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="p-4 rounded-2xl border border-emerald-200 dark:border-emerald-500/20 bg-emerald-50/50 dark:bg-emerald-500/5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Current</p>
            <p class="text-base font-extrabold text-emerald-700 dark:text-emerald-400 mt-1">{{ $currencySymbol }}{{ number_format($buckets['current'], 2) }}</p>
        </div>
        <div class="p-4 rounded-2xl border border-yellow-200 dark:border-yellow-500/20 bg-yellow-50/50 dark:bg-yellow-500/5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-yellow-600 dark:text-yellow-400">1 - 30 Days</p>
            <p class="text-base font-extrabold text-yellow-700 dark:text-yellow-400 mt-1">{{ $currencySymbol }}{{ number_format($buckets['1_30'], 2) }}</p>
        </div>
        <div class="p-4 rounded-2xl border border-orange-200 dark:border-orange-500/20 bg-orange-50/50 dark:bg-orange-500/5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-orange-600 dark:text-orange-400">31 - 60 Days</p>
            <p class="text-base font-extrabold text-orange-700 dark:text-orange-400 mt-1">{{ $currencySymbol }}{{ number_format($buckets['31_60'], 2) }}</p>
        </div>
        <div class="p-4 rounded-2xl border border-red-200 dark:border-red-500/20 bg-red-50/50 dark:bg-red-500/5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-red-600 dark:text-red-400">61 - 90 Days</p>
            <p class="text-base font-extrabold text-red-700 dark:text-red-400 mt-1">{{ $currencySymbol }}{{ number_format($buckets['61_90'], 2) }}</p>
        </div>
        <div class="p-4 rounded-2xl border border-red-300 dark:border-red-500/30 bg-red-50/80 dark:bg-red-500/10 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-red-700 dark:text-red-400">90+ Days</p>
            <p class="text-base font-extrabold text-red-800 dark:text-red-400 mt-1">{{ $currencySymbol }}{{ number_format($buckets['90_plus'], 2) }}</p>
        </div>
        <div class="p-4 rounded-2xl border border-indigo-200 dark:border-indigo-500/20 bg-indigo-50/50 dark:bg-indigo-500/5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Grand Total</p>
            <p class="text-base font-extrabold text-indigo-700 dark:text-indigo-400 mt-1">{{ $currencySymbol }}{{ number_format($grandTotal, 2) }}</p>
        </div>
    </div>

    <!-- Aging Table -->
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Vendor Aging Breakdown</h2>
            <span class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">{{ count($vendorBreakdown) }} vendor(s) with outstanding balances</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60">
                        <th class="text-left px-6 py-3 font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[10px]">Vendor</th>
                        <th class="text-right px-4 py-3 font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider text-[10px]">Current</th>
                        <th class="text-right px-4 py-3 font-bold text-yellow-600 dark:text-yellow-400 uppercase tracking-wider text-[10px]">1-30</th>
                        <th class="text-right px-4 py-3 font-bold text-orange-600 dark:text-orange-400 uppercase tracking-wider text-[10px]">31-60</th>
                        <th class="text-right px-4 py-3 font-bold text-red-600 dark:text-red-400 uppercase tracking-wider text-[10px]">61-90</th>
                        <th class="text-right px-4 py-3 font-bold text-red-700 dark:text-red-400 uppercase tracking-wider text-[10px]">90+</th>
                        <th class="text-right px-6 py-3 font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[10px]">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    @forelse($vendorBreakdown as $row)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <td class="px-6 py-3 font-semibold text-slate-900 dark:text-white">{{ $row['name'] }}</td>
                            <td class="px-4 py-3 text-right font-medium {{ $row['current'] > 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-300 dark:text-slate-600' }}">
                                {{ $row['current'] > 0 ? $currencySymbol . number_format($row['current'], 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium {{ $row['1_30'] > 0 ? 'text-yellow-700 dark:text-yellow-400' : 'text-slate-300 dark:text-slate-600' }}">
                                {{ $row['1_30'] > 0 ? $currencySymbol . number_format($row['1_30'], 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium {{ $row['31_60'] > 0 ? 'text-orange-700 dark:text-orange-400' : 'text-slate-300 dark:text-slate-600' }}">
                                {{ $row['31_60'] > 0 ? $currencySymbol . number_format($row['31_60'], 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium {{ $row['61_90'] > 0 ? 'text-red-700 dark:text-red-400' : 'text-slate-300 dark:text-slate-600' }}">
                                {{ $row['61_90'] > 0 ? $currencySymbol . number_format($row['61_90'], 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium {{ $row['90_plus'] > 0 ? 'text-red-800 dark:text-red-400 font-bold' : 'text-slate-300 dark:text-slate-600' }}">
                                {{ $row['90_plus'] > 0 ? $currencySymbol . number_format($row['90_plus'], 2) : '—' }}
                            </td>
                            <td class="px-6 py-3 text-right font-bold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($row['total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500">
                                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 mb-2"></i>
                                <p class="font-medium">No outstanding payables</p>
                                <p class="text-[11px] mt-0.5">All vendor bills are fully paid.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($vendorBreakdown) > 0)
                <tfoot>
                    <tr class="border-t-2 border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/80">
                        <td class="px-6 py-3 font-bold text-slate-900 dark:text-white text-xs uppercase tracking-wider">Totals</td>
                        <td class="px-4 py-3 text-right font-bold text-emerald-700 dark:text-emerald-400">{{ $currencySymbol }}{{ number_format($buckets['current'], 2) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-yellow-700 dark:text-yellow-400">{{ $currencySymbol }}{{ number_format($buckets['1_30'], 2) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-orange-700 dark:text-orange-400">{{ $currencySymbol }}{{ number_format($buckets['31_60'], 2) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-red-700 dark:text-red-400">{{ $currencySymbol }}{{ number_format($buckets['61_90'], 2) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-red-800 dark:text-red-400">{{ $currencySymbol }}{{ number_format($buckets['90_plus'], 2) }}</td>
                        <td class="px-6 py-3 text-right font-extrabold text-indigo-700 dark:text-indigo-400 text-sm">{{ $currencySymbol }}{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
