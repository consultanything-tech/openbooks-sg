@extends('layouts.app')

@section('title', 'Ledger Transactions')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Financial Ledger</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Complete historical journal of all debits, credits, and balance updates</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="document.getElementById('importModal').showModal()" class="btn btn-secondary">
                <i data-lucide="file-input" aria-hidden="true"></i> Import File
            </button>
            <a href="{{ route('banking.transactions.export_csv') }}" class="btn btn-secondary">
                <i data-lucide="file-spreadsheet" aria-hidden="true"></i> Export CSV
            </a>
            <a href="{{ route('banking.index') }}" class="text-xs text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">&larr; Back to Banking</a>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('banking.transactions') }}" class="glass-card p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex flex-wrap items-center gap-4 text-xs">
        <div class="min-w-[180px]">
            <select name="bank_account_id" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-slate-900 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                <option value="">All Bank Accounts</option>
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}" {{ request('bank_account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->account_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[140px]">
            <select name="type" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-slate-900 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                <option value="">All Types</option>
                <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Income (+)</option>
                <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Expense (-)</option>
            </select>
        </div>
        <div>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-slate-900 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
        </div>
        <div>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-slate-900 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
        </div>
        <button type="submit" class="btn btn-secondary">
            <i data-lucide="filter" aria-hidden="true"></i> Filter
        </button>
        @if(request()->hasAny(['bank_account_id', 'type', 'start_date', 'end_date']))
            <a href="{{ route('banking.transactions') }}" class="text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">Reset</a>
        @endif
    </form>

    <!-- Ledger Table -->
    <div class="glass-card rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs responsive-table">
                <thead class="bg-slate-50 dark:bg-slate-900/90 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">Date</th>
                        <th class="py-3.5 px-4">Account</th>
                        <th class="py-3.5 px-4">Category</th>
                        <th class="py-3.5 px-4">Type</th>
                        <th class="py-3.5 px-4">Method / Ref</th>
                        <th class="py-3.5 px-4">Description</th>
                        <th class="py-3.5 px-4 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td data-label="Date" class="py-3.5 px-4 text-slate-500 dark:text-slate-400">{{ date('M d, Y', strtotime($tx->transaction_date)) }}</td>
                            <td data-label="Account" class="py-3.5 px-4 font-medium text-slate-900 dark:text-white">{{ $tx->bankAccount->account_name ?? 'N/A' }}</td>
                            <td data-label="Category" class="py-3.5 px-4 text-slate-500 dark:text-slate-400">{{ $tx->category->name ?? 'General' }}</td>
                            <td data-label="Type" class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border uppercase {{ $tx->type === 'income' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20' }}">
                                    {{ $tx->type }}
                                </span>
                            </td>
                            <td data-label="Method" class="py-3.5 px-4 font-mono text-slate-500 dark:text-slate-400">
                                <p>{{ $tx->payment_method ?? 'Bank' }}</p>
                                @if($tx->reference_number)
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500">{{ $tx->reference_number }}</p>
                                @endif
                            </td>
                            <td data-label="Description" class="py-3.5 px-4 text-slate-700 dark:text-slate-300 max-w-xs truncate">
                                {{ $tx->description }}
                                @if($tx->is_reconciled)
                                    <span class="ml-1.5 inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[9px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20" title="Reconciled on {{ $tx->reconciled_at ? $tx->reconciled_at->format('M d, Y') : '' }}">
                                        <i data-lucide="check-circle" class="w-3 h-3"></i> Reconciled
                                    </span>
                                @endif
                            </td>
                            <td data-label="Amount" class="py-3.5 px-4 text-right font-bold {{ $tx->type === 'income' ? 'text-emerald-400' : 'text-slate-900 dark:text-slate-200' }}">
                                {{ $tx->type === 'income' ? '+' : '-' }}{{ $currencySymbol }}{{ number_format($tx->amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-empty-state
                                    icon="landmark"
                                    title="No transactions recorded"
                                    message="Transactions will appear here once they are recorded in the ledger." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-pagination :paginator="$transactions" />
    </div>

    <!-- Import File Modal -->
    <dialog id="importModal" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl p-6 max-w-lg w-full shadow-xl backdrop:bg-black/50 backdrop:backdrop-blur-sm">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Import Transactions</h3>
                <button onclick="document.getElementById('importModal').close()" class="btn-icon" aria-label="Close">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </div>

            <form action="{{ route('banking.transactions.import_csv') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">Bank Account *</label>
                    <select name="bank_account_id" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        <option value="">Select Bank Account</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->account_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">Bank File (CSV, OFX, QFX, or QBO)</label>
                    <input type="file" name="csv_file" accept=".csv,.txt,.ofx,.qfx,.qbo" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-slate-200 focus:outline-none focus:border-indigo-500 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-indigo-500/10 file:text-indigo-400">
                </div>

                <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 space-y-2">
                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Supported Formats:</p>
                    <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                        <div class="flex items-center gap-1.5"><i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-400"></i> <span><strong class="text-slate-700 dark:text-slate-300">CSV</strong> - Date, Type, Description, Amount, Category, Payment Method, Reference</span></div>
                        <div class="flex items-center gap-1.5"><i data-lucide="landmark" class="w-4 h-4 text-blue-400"></i> <span><strong class="text-slate-700 dark:text-slate-300">OFX / QFX</strong> - Direct bank export (OFX 1.x &amp; 2.x)</span></div>
                        <div class="flex items-center gap-1.5"><i data-lucide="file-text" class="w-4 h-4 text-amber-400"></i> <span><strong class="text-slate-700 dark:text-slate-300">QBO</strong> - QuickBooks Online CSV export</span></div>
                    </div>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Bank balance is updated automatically. Max file size: 5 MB.</p>
                </div>

                <div class="pt-2 flex items-center justify-between">
                    <a href="{{ route('banking.transactions.import_template') }}" class="btn btn-secondary">
                        <i data-lucide="download" aria-hidden="true"></i> Download Template
                    </a>
                    <div class="flex gap-3">
                        <button type="button" onclick="document.getElementById('importModal').close()" class="btn btn-ghost">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i data-lucide="upload" aria-hidden="true"></i> Import</button>
                    </div>
                </div>
            </form>
        </div>
    </dialog>
</div>
@endsection
