@extends('layouts.app')

@section('title', 'General Ledger — ' . $account->code)

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">General Ledger</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                <span class="font-mono">{{ $account->code }}</span> &middot; {{ $account->name }}
                <span class="ml-1 uppercase tracking-wide text-[10px] text-slate-400 dark:text-slate-500">{{ $account->type }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('reports.trial_balance') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Trial Balance
            </a>
        </div>
    </div>

    {{-- Date Filter --}}
    <form method="GET" action="{{ route('reports.general_ledger') }}" class="px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm flex items-center gap-3 text-xs">
        <input type="hidden" name="account" value="{{ $account->code }}">
        <span class="text-slate-500 dark:text-slate-400 font-semibold whitespace-nowrap">Period:</span>
        <input type="date" name="start_date" value="{{ $startDate }}" placeholder="From" class="input-date bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
        <span class="text-slate-400 dark:text-slate-500 font-medium">to</span>
        <input type="date" name="end_date" value="{{ $endDate }}" class="input-date bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
        <button type="submit" class="btn btn-primary"><i data-lucide="refresh-cw" aria-hidden="true"></i> Update</button>
        <a href="{{ route('reports.general_ledger.export_csv', ['account' => $account->code, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-secondary">
            <i data-lucide="file-spreadsheet" aria-hidden="true"></i> Export CSV
        </a>
    </form>

    {{-- Ledger --}}
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 uppercase tracking-wider text-[10px]">
                        <th class="text-left font-bold px-5 py-2.5 whitespace-nowrap">Date</th>
                        <th class="text-left font-bold px-3 py-2.5 whitespace-nowrap">Entry</th>
                        <th class="text-left font-bold px-3 py-2.5">Description</th>
                        <th class="text-left font-bold px-3 py-2.5 whitespace-nowrap">Reference</th>
                        <th class="text-right font-bold px-3 py-2.5 w-28">Debit</th>
                        <th class="text-right font-bold px-3 py-2.5 w-28">Credit</th>
                        <th class="text-right font-bold px-5 py-2.5 w-32">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    @if($startDate)
                        <tr class="text-slate-500 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-800/30 italic">
                            <td class="px-5 py-2.5 whitespace-nowrap">{{ date('M d, Y', strtotime($startDate)) }}</td>
                            <td class="px-3 py-2.5"></td>
                            <td class="px-3 py-2.5" colspan="3">Opening Balance</td>
                            <td class="px-3 py-2.5"></td>
                            <td class="px-5 py-2.5 text-right tabular-nums font-semibold">{{ $currencySymbol }}{{ number_format($opening, 2) }}</td>
                        </tr>
                    @endif

                    @forelse($lines as $l)
                        <tr class="text-slate-700 dark:text-slate-300">
                            <td class="px-5 py-2.5 whitespace-nowrap">{{ date('M d, Y', strtotime($l['date'])) }}</td>
                            <td class="px-3 py-2.5 font-mono text-[10px] text-slate-400 dark:text-slate-500 whitespace-nowrap">{{ $l['entry_number'] }}</td>
                            <td class="px-3 py-2.5">{{ $l['description'] }}</td>
                            <td class="px-3 py-2.5 text-slate-400 dark:text-slate-500 whitespace-nowrap">{{ $l['reference'] }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums">{{ $l['debit'] > 0 ? number_format($l['debit'], 2) : '' }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums">{{ $l['credit'] > 0 ? number_format($l['credit'], 2) : '' }}</td>
                            <td class="px-5 py-2.5 text-right tabular-nums font-semibold text-slate-900 dark:text-white">{{ number_format($l['balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-slate-400 dark:text-slate-500 py-8">No posted ledger activity for this account in the selected period.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-200 dark:border-slate-700 font-extrabold text-slate-900 dark:text-white">
                        <td class="px-5 py-3" colspan="4">Total</td>
                        <td class="px-3 py-3 text-right tabular-nums">{{ $currencySymbol }}{{ number_format($totalDebit, 2) }}</td>
                        <td class="px-3 py-3 text-right tabular-nums">{{ $currencySymbol }}{{ number_format($totalCredit, 2) }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-sm">{{ $currencySymbol }}{{ number_format($closing, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <p class="text-[11px] text-slate-400 dark:text-slate-500 max-w-3xl">
        Balances are shown in this account's normal direction ({{ $normal }}). The closing balance matches the
        <a href="{{ route('reports.trial_balance', ['as_of' => $endDate]) }}" class="text-indigo-600 dark:text-indigo-400 font-semibold underline">Trial Balance</a>
        figure for {{ $account->code }} as of {{ date('M d, Y', strtotime($endDate)) }}.
    </p>
</div>
@endsection
