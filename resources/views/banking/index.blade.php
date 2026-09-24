@extends('layouts.app')

@section('title', 'Banking & Accounts')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Banking & Accounts</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage bank accounts, cash drawers, and fund transfers</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('banking.transfer') }}" class="btn btn-secondary">
                <i data-lucide="refresh-cw" aria-hidden="true"></i>
                Transfer Funds
            </a>
            <button onclick="document.getElementById('addAccountModal').classList.remove('hidden')" class="btn btn-primary">
                <i data-lucide="plus" aria-hidden="true"></i>
                New Bank Account
            </button>
        </div>
    </div>

    <!-- Total Balance Banner -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Total Liquid Reserves</span>
            <h2 class="text-3xl font-black text-slate-900 dark:text-white mt-1">{{ $currencySymbol }}{{ number_format($totalBalance, 2) }}</h2>
            <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-1 flex items-center gap-1.5">
                <i data-lucide="shield" class="w-4 h-4"></i> Synced with {{ count($accounts) }} operational bank & cash accounts
            </p>
        </div>
        <div class="hidden sm:block">
            <a href="{{ route('banking.transactions') }}" class="btn btn-secondary">
                <i data-lucide="list-checks" aria-hidden="true"></i> View Full Ledger
            </a>
        </div>
    </div>

    <!-- Accounts Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        @foreach($accounts as $acc)
            <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ $acc->name }}</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ $acc->bank_name }}</p>
                    </div>
                    <span class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm border border-indigo-200 dark:border-indigo-500/30">
                        <i data-lucide="landmark" class="w-4 h-4"></i>
                    </span>
                </div>
                <div class="mt-4">
                    <span class="text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-wider font-semibold">Available Balance</span>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-0.5">{{ $currencySymbol }}{{ number_format($acc->current_balance, 2) }}</h3>
                    <div class="mt-2 space-y-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                        <p class="font-mono flex items-center gap-1.5">A/C: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $acc->account_number }}</span>
                            <button type="button" class="btn-icon" data-copy="{{ $acc->account_number }}" title="Copy" aria-label="Copy"><i data-lucide="copy" aria-hidden="true"></i></button>
                        </p>
                        @if($acc->ifsc_code)
                            <p class="font-mono">IFSC: <span class="font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-200 dark:border-emerald-500/30">{{ $acc->ifsc_code }}</span></p>
                        @endif
                        @if($acc->branch_name)
                            <p class="truncate">Branch: {{ $acc->branch_name }}</p>
                        @endif
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center text-[11px] text-slate-500 dark:text-slate-400">
                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-medium">{{ $acc->account_type ?: 'Current' }}</span>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('banking.reconcile', $acc->id) }}" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-semibold flex items-center gap-1">
                            <i data-lucide="check-check" class="w-3.5 h-3.5"></i>
                            <span>Reconcile</span>
                        </a>
                        <a href="{{ route('banking.transactions') }}?bank_account_id={{ $acc->id }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-semibold flex items-center gap-1">
                            <span>Ledger</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Add Account Modal -->
    <div id="addAccountModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold border border-indigo-200 dark:border-indigo-500/30">
                        <i data-lucide="landmark" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Add Bank Account (Singapore)</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Register banking ledger account</p>
                    </div>
                </div>
                <button onclick="document.getElementById('addAccountModal').classList.add('hidden')" class="btn-icon" aria-label="Close">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </div>

            <form action="{{ route('banking.store') }}" method="POST" class="space-y-3.5 text-xs">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Account Holder / Name *</label>
                        <input type="text" name="account_name" placeholder="e.g. OpenBooks Current A/C" required
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Bank Name *</label>
                        <input type="text" name="bank_name" placeholder="e.g. DBS Bank, OCBC, UOB" required
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Account Number *</label>
                        <input type="text" name="account_number" placeholder="e.g. 30891283741" required
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-mono placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Account Type *</label>
                        <select name="account_type" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white appearance-none cursor-pointer focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                            <option value="Current Account" selected>Current Account</option>
                            <option value="Savings Account">Savings Account</option>
                            <option value="Cash Credit (CC)">Cash Credit (CC)</option>
                            <option value="Overdraft (OD)">Overdraft (OD)</option>
                            <option value="Cash in Hand / Drawer">Cash in Hand / Drawer</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">IFSC Code *</label>
                        <input type="text" name="ifsc_code" placeholder="e.g. SBIN0001234" required
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-mono uppercase font-bold placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Branch Name</label>
                        <input type="text" name="branch_name" placeholder="e.g. Raffles Place Branch"
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">PayNow ID / VPA (Optional)</label>
                        <input type="text" name="upi_id" placeholder="e.g. openbooks@sbi"
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Opening Balance ({{ $currencySymbol }}) *</label>
                        <input type="number" name="opening_balance" value="0.00" step="0.01" min="0" required
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-bold placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                </div>

                <input type="hidden" name="currency" value="INR">

                <div class="pt-3 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('addAccountModal').classList.add('hidden')"
                        class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save" aria-hidden="true"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
