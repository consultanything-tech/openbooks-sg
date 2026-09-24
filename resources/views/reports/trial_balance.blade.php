@extends('layouts.app')

@section('title', 'Trial Balance')

@section('content')
<div class="space-y-6">
    {{-- Header and Subnav --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Financial Reports</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Comprehensive accounting statements, audit ledgers, and tax liabilities</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('reports.profit_loss') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Profit &amp; Loss</a>
            <a href="{{ route('reports.balance_sheet') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Balance Sheet</a>
            <a href="{{ route('reports.trial_balance') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/20 shadow-sm">Trial Balance</a>
            <a href="{{ route('reports.income_expense') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Monthly Cashflow</a>
            <a href="{{ route('reports.gst_f5') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">GST F5</a>
        </div>
    </div>

    {{-- Date Filter --}}
    <form method="GET" action="{{ route('reports.trial_balance') }}" class="px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm flex items-center gap-3 text-xs">
        <span class="text-slate-500 dark:text-slate-400 font-semibold whitespace-nowrap">As of Date:</span>
        <input type="date" name="as_of" value="{{ $asOfDate }}" class="input-date bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
        <button type="submit" class="btn btn-primary"><i data-lucide="refresh-cw" aria-hidden="true"></i> Update</button>
        <a href="{{ route('reports.trial_balance.export_csv', ['as_of' => $asOfDate]) }}" class="btn btn-secondary">
            <i data-lucide="file-spreadsheet" aria-hidden="true"></i> Export CSV
        </a>
    </form>

    {{-- Trial Balance --}}
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm max-w-3xl mx-auto overflow-hidden">
        <div class="text-center border-b border-slate-100 dark:border-slate-800 p-6 pb-4">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Trial Balance</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">As of {{ date('M d, Y', strtotime($asOfDate)) }}</p>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 uppercase tracking-wider font-semibold">{{ $company->name ?? 'OpenBooks Enterprise' }}</p>
        </div>

        <table class="w-full text-xs">
            <thead>
                <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 uppercase tracking-wider text-[10px]">
                    <th class="text-left font-bold px-6 py-2.5">Account</th>
                    <th class="text-right font-bold px-4 py-2.5 w-32">Debit</th>
                    <th class="text-right font-bold px-6 py-2.5 w-32">Credit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                @forelse($accounts as $a)
                    <tr class="text-slate-700 dark:text-slate-300 hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                        <td class="px-6 py-2.5">
                            <a href="{{ route('reports.general_ledger', ['account' => $a['code'], 'end_date' => $asOfDate]) }}" class="group inline-flex items-baseline gap-2 text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition" title="View General Ledger for {{ $a['code'] }}">
                                <span class="font-mono text-[11px] text-slate-400 dark:text-slate-500 group-hover:text-indigo-500 dark:group-hover:text-indigo-400">{{ $a['code'] }}</span>
                                <span class="font-medium">{{ $a['name'] }}</span>
                                <i data-lucide="arrow-up-right" class="w-3 h-3 opacity-0 group-hover:opacity-60 transition"></i>
                            </a>
                            <span class="ml-2 text-[10px] uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ $a['type'] }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-slate-900 dark:text-white">
                            {{ $a['debit'] > 0 ? $currencySymbol . number_format($a['debit'], 2) : '' }}
                        </td>
                        <td class="px-6 py-2.5 text-right tabular-nums font-semibold text-slate-900 dark:text-white">
                            {{ $a['credit'] > 0 ? $currencySymbol . number_format($a['credit'], 2) : '' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-slate-400 dark:text-slate-500 py-8">
                            No posted ledger activity as of this date.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-slate-200 dark:border-slate-700 font-extrabold text-slate-900 dark:text-white">
                    <td class="px-6 py-3 text-xs font-bold">Total</td>
                    <td class="px-4 py-3 text-right tabular-nums text-sm">{{ $currencySymbol }}{{ number_format($totalDebit, 2) }}</td>
                    <td class="px-6 py-3 text-right tabular-nums text-sm">{{ $currencySymbol }}{{ number_format($totalCredit, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
            @if($balanced)
                <div class="p-3.5 rounded-xl border border-emerald-200 dark:border-emerald-500/20 bg-emerald-50/40 dark:bg-emerald-500/5 flex justify-between items-center text-xs">
                    <span class="text-slate-600 dark:text-slate-300 font-medium">Debits equal credits — the ledger is in balance</span>
                    <span class="font-bold text-emerald-700 dark:text-emerald-400">Balanced</span>
                </div>
            @else
                <div class="p-3.5 rounded-xl border border-amber-200 dark:border-amber-500/20 bg-amber-50/40 dark:bg-amber-500/5 flex justify-between items-center text-xs">
                    <span class="text-amber-700 dark:text-amber-400 font-medium">Out of balance (Debits &minus; Credits)</span>
                    <span class="font-bold text-amber-700 dark:text-amber-400 tabular-nums">{{ $currencySymbol }}{{ number_format($totalDebit - $totalCredit, 2) }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
