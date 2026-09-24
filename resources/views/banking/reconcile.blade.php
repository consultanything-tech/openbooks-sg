@extends('layouts.app')

@section('title', 'Reconcile: ' . $account->name)

@section('content')
<div class="space-y-6">

    <x-sticky-form-bar :cancel-url="route('banking.index')" submit-id="reconcileBtn" save-label="Mark as Reconciled">
        <x-slot:title>
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Reconcile: {{ $account->name }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Match transactions against your bank statement</p>
            </div>
        </x-slot:title>
    </x-sticky-form-bar>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 rounded-xl p-4 text-sm text-emerald-700 dark:text-emerald-300 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 rounded-xl p-4 text-sm text-amber-700 dark:text-amber-300 flex items-center gap-2">
            <i data-lucide="alert-triangle" class="w-4 h-4"></i> {{ session('warning') }}
        </div>
    @endif

    {{-- Account Info & Statement Balance --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        {{-- Account Card --}}
        <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
            <div class="flex items-center gap-3 mb-3">
                <span class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm border border-indigo-200 dark:border-indigo-500/30">
                    <i data-lucide="landmark" class="w-4 h-4"></i>
                </span>
                <div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ $account->name }}</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $account->bank_name }}</p>
                </div>
            </div>
            <div class="space-y-1 text-xs text-slate-500 dark:text-slate-400">
                <p class="font-mono">A/C: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $account->account_number }}</span></p>
                <p>Opening Balance: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $currencySymbol }}{{ number_format($account->opening_balance, 2) }}</span></p>
                <p>Current Balance: <span class="font-bold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($account->current_balance, 2) }}</span></p>
            </div>
        </div>

        {{-- Statement Balance Input --}}
        <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Statement Balance</label>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mb-3">Enter the closing balance from your bank statement</p>
            <input type="number" id="statementBalance" step="0.01" placeholder="0.00"
                class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white font-bold placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
        </div>

        {{-- Reconciliation Summary --}}
        <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Reconciliation Summary</label>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-500 dark:text-slate-400">Reconciled Balance</span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ $currencySymbol }}<span id="reconciledBalanceDisplay">{{ number_format($reconciledBalance, 2) }}</span></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 dark:text-slate-400">+ Selected Total</span>
                    <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $currencySymbol }}<span id="selectedTotalDisplay">0.00</span></span>
                </div>
                <div class="border-t border-slate-200 dark:border-slate-700 pt-2 flex justify-between">
                    <span class="font-semibold text-slate-700 dark:text-slate-200">Difference</span>
                    <span class="font-black text-lg" id="differenceDisplay">{{ $currencySymbol }}0.00</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Unreconciled Transactions Form --}}
    <form action="{{ route('banking.reconcile.process', $account->id) }}" method="POST" id="reconcileForm">
        @csrf
        <input type="hidden" name="statement_balance" id="statementBalanceHidden" value="">

        <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Unreconciled Transactions</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Select transactions that match your bank statement</p>
                </div>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $unreconciledTransactions->count() }} pending</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-900/90 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4 w-10">
                                <input type="checkbox" id="selectAll" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-800">
                            </th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Description</th>
                            <th class="py-3 px-4">Reference</th>
                            <th class="py-3 px-4">Type</th>
                            <th class="py-3 px-4 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300">
                        @forelse($unreconciledTransactions as $tx)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3 px-4">
                                    <input type="checkbox" name="transaction_ids[]" value="{{ $tx->id }}"
                                        data-amount="{{ $tx->amount }}" data-type="{{ $tx->type }}"
                                        class="tx-checkbox rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-800">
                                </td>
                                <td class="py-3 px-4 text-slate-500 dark:text-slate-400">{{ date('M d, Y', strtotime($tx->transaction_date)) }}</td>
                                <td class="py-3 px-4 font-medium text-slate-900 dark:text-white max-w-xs truncate">{{ $tx->description }}</td>
                                <td class="py-3 px-4 font-mono text-slate-400 dark:text-slate-500">{{ $tx->reference_number ?: '-' }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border uppercase {{ $tx->type === 'income' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20' : 'bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border-red-200 dark:border-red-500/20' }}">
                                        {{ $tx->type }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-bold {{ $tx->type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-slate-200' }}">
                                    {{ $tx->type === 'income' ? '+' : '-' }}{{ $currencySymbol }}{{ number_format($tx->amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 mb-2"></i>
                                    <p>All transactions have been reconciled.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($unreconciledTransactions->count() > 0)
                <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        <span id="selectedCount">0</span> transaction(s) selected
                        &middot; Net: <span class="font-bold text-slate-900 dark:text-white" id="selectedNetDisplay">{{ $currencySymbol }}0.00</span>
                    </div>
                    <button type="submit" id="reconcileBtn" disabled
                        class="btn btn-success disabled:bg-slate-300 dark:disabled:bg-slate-700 disabled:cursor-not-allowed">
                        <i data-lucide="check-check" aria-hidden="true"></i> Mark as Reconciled
                    </button>
                </div>
            @endif
        </div>
    </form>

    {{-- Already Reconciled Transactions (Collapsible) --}}
    @if($reconciledTransactions->count() > 0)
        <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
            <button type="button" onclick="document.getElementById('reconciledSection').classList.toggle('hidden'); this.querySelector('.chevron-icon').classList.toggle('rotate-180')"
                class="w-full px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i>
                        Reconciled Transactions
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $reconciledTransactions->count() }} transactions already reconciled</p>
                </div>
                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 dark:text-slate-500 transition-transform duration-200 chevron-icon"></i>
            </button>

            <div id="reconciledSection" class="hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-900/90 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4">Description</th>
                                <th class="py-3 px-4">Reference</th>
                                <th class="py-3 px-4">Type</th>
                                <th class="py-3 px-4 text-right">Amount</th>
                                <th class="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300">
                            @foreach($reconciledTransactions as $tx)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 text-[10px] font-semibold">
                                            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Reconciled
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-500 dark:text-slate-400">{{ date('M d, Y', strtotime($tx->transaction_date)) }}</td>
                                    <td class="py-3 px-4 font-medium text-slate-900 dark:text-white max-w-xs truncate">{{ $tx->description }}</td>
                                    <td class="py-3 px-4 font-mono text-slate-400 dark:text-slate-500">{{ $tx->reference_number ?: '-' }}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border uppercase {{ $tx->type === 'income' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20' : 'bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border-red-200 dark:border-red-500/20' }}">
                                            {{ $tx->type }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold {{ $tx->type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-slate-200' }}">
                                        {{ $tx->type === 'income' ? '+' : '-' }}{{ $currencySymbol }}{{ number_format($tx->amount, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <form action="{{ route('banking.unreconcile', $tx->id) }}" method="POST" class="inline" onsubmit="return confirm('Un-reconcile this transaction?')">
                                            @csrf
                                            <button type="submit" class="btn btn-danger-text">
                                                Undo
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = document.querySelectorAll('.tx-checkbox');
    const selectAll = document.getElementById('selectAll');
    const selectedCountEl = document.getElementById('selectedCount');
    const selectedTotalEl = document.getElementById('selectedTotalDisplay');
    const selectedNetEl = document.getElementById('selectedNetDisplay');
    const differenceEl = document.getElementById('differenceDisplay');
    const statementInput = document.getElementById('statementBalance');
    const statementHidden = document.getElementById('statementBalanceHidden');
    const reconcileBtn = document.getElementById('reconcileBtn');
    const reconciledBalance = {{ $reconciledBalance }};
    const currencySymbol = '{{ $currencySymbol }}';

    function formatNumber(n) {
        return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function updateTotals() {
        let selectedIncome = 0;
        let selectedExpense = 0;
        let count = 0;

        checkboxes.forEach(function (cb) {
            if (cb.checked) {
                count++;
                const amount = parseFloat(cb.dataset.amount);
                if (cb.dataset.type === 'income') {
                    selectedIncome += amount;
                } else {
                    selectedExpense += amount;
                }
            }
        });

        const selectedNet = selectedIncome - selectedExpense;
        const adjustedBalance = reconciledBalance + selectedNet;
        const statementBal = parseFloat(statementInput.value) || 0;
        const difference = statementBal - adjustedBalance;

        if (selectedCountEl) selectedCountEl.textContent = count;
        if (selectedTotalEl) selectedTotalEl.textContent = formatNumber(Math.abs(selectedNet));
        if (selectedNetEl) selectedNetEl.textContent = (selectedNet >= 0 ? '' : '-') + currencySymbol + formatNumber(Math.abs(selectedNet));

        if (differenceEl) {
            differenceEl.textContent = (difference >= 0 ? '' : '-') + currencySymbol + formatNumber(Math.abs(difference));
            differenceEl.className = 'font-black text-lg ' + (
                difference === 0 ? 'text-emerald-600 dark:text-emerald-400' :
                'text-red-600 dark:text-red-400'
            );
        }

        if (statementHidden) statementHidden.value = statementInput.value;
        if (reconcileBtn) reconcileBtn.disabled = count === 0;
    }

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', function () {
            updateTotals();
            if (selectAll) {
                selectAll.checked = document.querySelectorAll('.tx-checkbox:checked').length === checkboxes.length;
            }
        });
    });

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
            updateTotals();
        });
    }

    if (statementInput) {
        statementInput.addEventListener('input', updateTotals);
    }

    updateTotals();
});
</script>
@endsection
