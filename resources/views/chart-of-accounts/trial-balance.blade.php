@extends('layouts.app')

@section('title', 'Trial Balance')

@section('content')
<div class="space-y-6">
    @php $currencySymbol = $company->currency_symbol ?? 'S$'; @endphp

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Trial Balance</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Verify that total debits equal total credits across all accounts</p>
        </div>
        <button onclick="window.print()" class="btn btn-secondary">
            <i data-lucide="printer" aria-hidden="true"></i> Print Report
        </button>
    </div>

    <!-- Tab Navigation -->
    <div class="flex items-center gap-1 border-b border-slate-200 dark:border-slate-800">
        <a href="{{ route('accounts.index') }}" class="px-4 py-2.5 text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
            <i data-lucide="git-branch" class="w-4 h-4 mr-1.5"></i>Chart of Accounts
        </a>
        <a href="{{ route('accounts.journal_entries') }}" class="px-4 py-2.5 text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
            <i data-lucide="book" class="w-4 h-4 mr-1.5"></i>Journal Entries
        </a>
        <a href="{{ route('accounts.trial_balance') }}" class="px-4 py-2.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 dark:border-indigo-400 transition">
            <i data-lucide="scale" class="w-4 h-4 mr-1.5"></i>Trial Balance
        </a>
    </div>

    @php
        $grandDebit = $accounts->sum('total_debit');
        $grandCredit = $accounts->sum('total_credit');
        $isBalanced = abs($grandDebit - $grandCredit) < 0.005;

        $typeOrder = ['asset', 'liability', 'equity', 'revenue', 'expense'];
        $typeLabels = ['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity', 'revenue' => 'Revenue', 'expense' => 'Expenses'];
        $grouped = $accounts->groupBy('type');
    @endphp

    <!-- Balance Status -->
    <div class="flex items-center gap-3 p-4 rounded-2xl border {{ $isBalanced ? 'border-emerald-200 dark:border-emerald-500/20 bg-emerald-50 dark:bg-emerald-500/10' : 'border-red-200 dark:border-red-500/20 bg-red-50 dark:bg-red-500/10' }}">
        <div class="w-8 h-8 rounded-xl flex items-center justify-center {{ $isBalanced ? 'bg-emerald-100 dark:bg-emerald-500/20' : 'bg-red-100 dark:bg-red-500/20' }}">
            <i data-lucide="{{ $isBalanced ? 'check-circle' : 'x-circle' }}" class="w-4 h-4 {{ $isBalanced ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}"></i>
        </div>
        <div>
            <p class="text-xs font-bold {{ $isBalanced ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400' }}">
                {{ $isBalanced ? 'Books are balanced' : 'Books are NOT balanced' }}
            </p>
            <p class="text-[10px] {{ $isBalanced ? 'text-emerald-600 dark:text-emerald-500' : 'text-red-600 dark:text-red-500' }}">
                Total Debits: {{ $currencySymbol }}{{ number_format($grandDebit, 2) }} | Total Credits: {{ $currencySymbol }}{{ number_format($grandCredit, 2) }}
                @if(!$isBalanced) | Difference: {{ $currencySymbol }}{{ number_format(abs($grandDebit - $grandCredit), 2) }} @endif
            </p>
        </div>
    </div>

    <!-- Trial Balance Table -->
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ $company->name ?? 'Company' }} &mdash; Trial Balance</h2>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">As of {{ date('F d, Y') }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs responsive-table">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3">Code</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3">Account Name</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Debit</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Credit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @foreach($typeOrder as $type)
                        @php $typeAccounts = $grouped->get($type, collect()); @endphp
                        @if($typeAccounts->isNotEmpty())
                            <!-- Type Header -->
                            <tr class="bg-slate-50/80 dark:bg-slate-800/40">
                                <td colspan="4" class="px-4 py-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                    {{ $typeLabels[$type] }}
                                </td>
                            </tr>
                            @php $typeDebit = 0; $typeCredit = 0; @endphp
                            @foreach($typeAccounts as $account)
                                @php $typeDebit += $account->total_debit; $typeCredit += $account->total_credit; @endphp
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-4 py-2.5 pl-8 font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $account->code }}</td>
                                    <td class="px-4 py-2.5 text-slate-900 dark:text-white font-medium">{{ $account->name }}</td>
                                    <td class="px-4 py-2.5 text-right font-semibold">{{ $account->total_debit ? $currencySymbol . number_format($account->total_debit, 2) : '' }}</td>
                                    <td class="px-4 py-2.5 text-right font-semibold">{{ $account->total_credit ? $currencySymbol . number_format($account->total_credit, 2) : '' }}</td>
                                </tr>
                            @endforeach
                            <!-- Subtotal Row -->
                            <tr class="bg-slate-50/50 dark:bg-slate-800/30 border-b-2 border-slate-200 dark:border-slate-700">
                                <td colspan="2" class="px-4 py-2.5 pl-8 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Subtotal &mdash; {{ $typeLabels[$type] }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-bold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($typeDebit, 2) }}</td>
                                <td class="px-4 py-2.5 text-right font-bold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($typeCredit, 2) }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-100 dark:bg-slate-800 border-t-2 border-slate-300 dark:border-slate-600">
                    <tr>
                        <td colspan="2" class="px-4 py-3.5 text-xs font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">Grand Total</td>
                        <td class="px-4 py-3.5 text-right text-sm font-extrabold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($grandDebit, 2) }}</td>
                        <td class="px-4 py-3.5 text-right text-sm font-extrabold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($grandCredit, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    .rounded-2xl.border.border-slate-200, .rounded-2xl.border { visibility: visible; }
}
</style>
@endsection
