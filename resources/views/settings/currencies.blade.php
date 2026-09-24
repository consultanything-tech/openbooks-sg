@extends('layouts.app')

@section('title', 'Currency Management')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Currency Management</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Manage exchange rates for multi-currency invoices and bills (relative to {{ $company->currency_code ?? 'SGD' }})</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('settings.index') }}" class="text-xs text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition">&larr; Back to Settings</a>
            <button onclick="document.getElementById('addCurrencyModal').classList.remove('hidden')" class="btn btn-primary">
                <i data-lucide="plus" aria-hidden="true"></i> New Currency
            </button>
        </div>
    </div>

    <!-- Base Currency Info -->
    <div class="bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/30 rounded-2xl p-4 flex items-center gap-3">
        <div class="w-8 h-8 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold text-sm">{{ $company->currency_symbol ?? 'S$' }}</div>
        <div>
            <p class="text-xs font-bold text-indigo-700 dark:text-indigo-300">Base Currency: {{ $company->currency_code ?? 'SGD' }} ({{ $company->currency_symbol ?? 'S$' }})</p>
            <p class="text-[10px] text-indigo-600 dark:text-indigo-400">All exchange rates are relative to this base currency. Rate = how many base currency units per 1 foreign unit.</p>
        </div>
    </div>

    <!-- Currency Rates Table -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
        <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Exchange Rates</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">1 foreign currency unit = X {{ $company->currency_code ?? 'SGD' }}</p>

        @if($currencies->isEmpty())
            <p class="text-xs text-slate-400 dark:text-slate-500 py-8 text-center">No currencies configured yet. Click "Add Currency" to get started.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="py-2.5 px-3">Code</th>
                            <th class="py-2.5 px-3">Name</th>
                            <th class="py-2.5 px-3 text-center">Symbol</th>
                            <th class="py-2.5 px-3 text-right">Rate (to {{ $company->currency_code ?? 'SGD' }})</th>
                            <th class="py-2.5 px-3 text-center">Status</th>
                            <th class="py-2.5 px-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($currencies as $cur)
                        <tr>
                            <td class="py-3 px-3 font-mono font-bold text-slate-900 dark:text-white">{{ $cur->currency_code }}</td>
                            <td class="py-3 px-3 text-slate-700 dark:text-slate-300">{{ $cur->currency_name }}</td>
                            <td class="py-3 px-3 text-center font-bold text-slate-900 dark:text-white">{{ $cur->currency_symbol }}</td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">{{ number_format($cur->exchange_rate, 6) }}</td>
                            <td class="py-3 px-3 text-center">
                                @if($cur->is_active)
                                    <span class="px-2 py-0.5 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold border border-emerald-200 dark:border-emerald-500/30">Active</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-[10px] font-bold border border-slate-200 dark:border-slate-700">Inactive</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="openEditModal({{ $cur->id }}, '{{ $cur->currency_code }}', '{{ addslashes($cur->currency_name) }}', '{{ addslashes($cur->currency_symbol) }}', {{ $cur->exchange_rate }}, {{ $cur->is_active ? 'true' : 'false' }})"
                                        class="btn-icon" title="Edit currency" aria-label="Edit currency">
                                        <i data-lucide="pencil" aria-hidden="true"></i>
                                    </button>
                                    <form action="{{ route('settings.currencies.destroy', $cur->id) }}" method="POST" class="inline" onsubmit="return confirm('Remove {{ $cur->currency_code }}? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete currency" aria-label="Delete currency">
                                            <i data-lucide="trash-2" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Add Currency Modal -->
    <div id="addCurrencyModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Add Currency</h3>
                <button onclick="document.getElementById('addCurrencyModal').classList.add('hidden')" class="btn-icon" aria-label="Close">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </div>

            <form action="{{ route('settings.currencies.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1.5">Currency Code *</label>
                        <input type="text" name="currency_code" required placeholder="e.g. USD" maxlength="10"
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white uppercase font-mono font-bold placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1.5">Symbol *</label>
                        <input type="text" name="currency_symbol" required placeholder="e.g. $" maxlength="10"
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-bold placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                </div>
                <div>
                    <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1.5">Currency Name *</label>
                    <input type="text" name="currency_name" required placeholder="e.g. US Dollar"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
                <div>
                    <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1.5">Exchange Rate (to {{ $company->currency_code ?? 'SGD' }}) *</label>
                    <input type="number" name="exchange_rate" required step="0.000001" min="0.000001" placeholder="e.g. 1.350000"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-mono placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">How many {{ $company->currency_code ?? 'SGD' }} for 1 unit of this currency</p>
                </div>
                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('addCurrencyModal').classList.add('hidden')" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="save" aria-hidden="true"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Currency Modal -->
<div id="editCurrencyModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Currency <span id="editCurrencyCode" class="font-mono text-indigo-600 dark:text-indigo-400"></span></h3>
            <button onclick="document.getElementById('editCurrencyModal').classList.add('hidden')" class="btn-icon" aria-label="Close">
                <i data-lucide="x" aria-hidden="true"></i>
            </button>
        </div>

        <form id="editCurrencyForm" method="POST" class="space-y-4 text-xs">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1.5">Currency Name</label>
                    <input type="text" name="currency_name" id="editCurrencyName"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
                <div>
                    <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1.5">Symbol</label>
                    <input type="text" name="currency_symbol" id="editCurrencySymbol"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-bold placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
            </div>
            <div>
                <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1.5">Exchange Rate (to {{ $company->currency_code ?? 'SGD' }}) *</label>
                <input type="number" name="exchange_rate" id="editExchangeRate" required step="0.000001" min="0.000001"
                    class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-mono font-bold placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="editIsActive" value="1" class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                <label for="editIsActive" class="text-xs text-slate-600 dark:text-slate-400 font-medium">Active (available for selection on invoices/bills)</label>
            </div>
            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('editCurrencyModal').classList.add('hidden')" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="save" aria-hidden="true"></i> Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openEditModal(id, code, name, symbol, rate, isActive) {
        document.getElementById('editCurrencyCode').innerText = code;
        document.getElementById('editCurrencyName').value = name;
        document.getElementById('editCurrencySymbol').value = symbol;
        document.getElementById('editExchangeRate').value = rate;
        document.getElementById('editIsActive').checked = isActive;
        document.getElementById('editCurrencyForm').action = '/settings/currencies/' + id;
        document.getElementById('editCurrencyModal').classList.remove('hidden');
    }
</script>
@endsection
