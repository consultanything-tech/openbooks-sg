@extends('layouts.app')

@section('title', 'Bill ' . $bill->bill_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <x-breadcrumbs :items="[['label' => 'Bills', 'url' => route('bills.index')], ['label' => $bill->bill_number]]" />

    <!-- Action Buttons -->
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('bills.edit', $bill->id) }}" class="btn btn-secondary">
            <i data-lucide="pencil" aria-hidden="true"></i> Edit Bill
        </a>
        @if($bill->status !== 'paid')
            <form action="{{ route('bills.destroy', $bill->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this bill? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger-text">
                    Delete
                </button>
            </form>
        @endif
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{{ $bill->bill_number }}</h1>
                @php
                    $badgeClasses = [
                        'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                        'received' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
                        'partial' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                        'overdue' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
                    ];
                @endphp
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border uppercase {{ $badgeClasses[$bill->status] ?? 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700' }}">
                    {{ $bill->status }}
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Vendor: {{ $bill->vendor->name ?? 'N/A' }} &bull; Category: {{ $bill->category->name ?? 'Expense' }}
                @if(($bill->currency_code ?? 'SGD') !== ($company->currency_code ?? 'SGD'))
                    &bull; <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $bill->currency_code }} @ {{ number_format($bill->exchange_rate, 6) }}</span>
                @endif
            </p>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Last updated {{ $bill->updated_at ? $bill->updated_at->diffForHumans() : '—' }}</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('bills.print', $bill->id) }}" target="_blank" class="btn btn-secondary">
                <i data-lucide="printer" aria-hidden="true"></i> Print
            </a>
            @if($bill->paid_amount < $bill->total)
                <button onclick="document.getElementById('paymentModal').classList.remove('hidden')" class="btn btn-primary">
                    <i data-lucide="banknote" aria-hidden="true"></i> Settle / Pay Bill
                </button>
            @endif
        </div>
    </div>

    <!-- Bill Content Card -->
    <div class="p-8 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 space-y-8">
        <div class="flex justify-between items-start border-b border-slate-100 dark:border-slate-800 pb-6">
            <div>
                <h3 class="text-xs font-medium text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Vendor</h3>
                <h4 class="text-base font-bold text-slate-900 dark:text-white">{{ $bill->vendor->name ?? 'Vendor' }}</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $bill->vendor->address ?? '' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Email: {{ $bill->vendor->email ?? 'N/A' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Phone: {{ $bill->vendor->phone ?? 'N/A' }}</p>
            </div>
            <div class="text-right space-y-1 text-xs">
                <p class="text-slate-500 dark:text-slate-400">Bill Date: <span class="text-slate-900 dark:text-white font-medium">{{ date('M d, Y', strtotime($bill->bill_date)) }}</span></p>
                <p class="text-slate-500 dark:text-slate-400">Due Date: <span class="text-slate-900 dark:text-white font-medium">{{ date('M d, Y', strtotime($bill->due_date)) }}</span></p>
                @if($bill->order_number)
                    <p class="text-slate-500 dark:text-slate-400">Vendor Ref #: <span class="text-slate-900 dark:text-white font-medium">{{ $bill->order_number }}</span></p>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 font-semibold border-y border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="py-3 px-3 w-8">#</th>
                    <th class="py-3 px-3">Item / Service</th>
                    <th class="py-3 px-3 text-center">Qty</th>
                    <th class="py-3 px-3 text-right">Unit Price</th>
                    <th class="py-3 px-3 text-right">Amount</th>
                    <th class="py-3 px-3 text-right">Tax</th>
                    <th class="py-3 px-3 text-right">Line Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                @foreach($bill->items as $idx => $it)
                    <tr>
                        <td class="py-3 px-3 text-slate-400 dark:text-slate-500">{{ $idx + 1 }}</td>
                        <td class="py-3 px-3">
                            <span class="font-medium text-slate-900 dark:text-white">{{ $it->name }}</span>
                            @if($it->description)
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">{{ $it->description }}</p>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-center">{{ $it->quantity }}</td>
                        <td class="py-3 px-3 text-right">{{ $currencySymbol }}{{ number_format($it->price, 2) }}</td>
                        <td class="py-3 px-3 text-right">{{ $currencySymbol }}{{ number_format($it->quantity * $it->price, 2) }}</td>
                        <td class="py-3 px-3 text-right text-slate-500 dark:text-slate-400">
                            <span class="text-[10px]">{{ number_format($it->tax_rate, 0) }}%</span>
                            {{ $currencySymbol }}{{ number_format($it->tax_amount, 2) }}
                        </td>
                        <td class="py-3 px-3 text-right font-semibold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($it->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals & Balances -->
        <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
            <div class="w-64 space-y-2 text-xs">
                <div class="flex justify-between text-slate-500 dark:text-slate-400">
                    <span>Subtotal:</span>
                    <span class="text-slate-900 dark:text-white font-medium">{{ $currencySymbol }}{{ number_format($bill->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-500 dark:text-slate-400">
                    <span>GST (Tax):</span>
                    <span class="text-slate-900 dark:text-white font-medium">{{ $currencySymbol }}{{ number_format($bill->tax_total, 2) }}</span>
                </div>
                @if(($bill->discount_total ?? 0) > 0)
                <div class="flex justify-between text-slate-500 dark:text-slate-400">
                    <span>Discount:</span>
                    <span class="text-red-500">-{{ $currencySymbol }}{{ number_format($bill->discount_total, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between text-sm font-bold text-slate-900 dark:text-white pt-2 border-t border-slate-200 dark:border-slate-800">
                    <span>Bill Amount:</span>
                    <span class="font-extrabold">{{ $currencySymbol }}{{ number_format($bill->total, 2) }}</span>
                </div>
                <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                    <span>Paid to Date:</span>
                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ $currencySymbol }}{{ number_format($bill->paid_amount, 2) }}</span>
                </div>
                <div class="flex justify-between text-base font-bold text-slate-900 dark:text-white pt-2 border-t border-slate-200 dark:border-slate-800">
                    <span>Balance Due:</span>
                    <span class="text-red-600 dark:text-red-400 font-extrabold">{{ $currencySymbol }}{{ number_format($bill->total - $bill->paid_amount, 2) }}</span>
                </div>
                @if(($bill->currency_code ?? 'SGD') !== ($company->currency_code ?? 'SGD'))
                <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400 pt-1">
                    <span>Base Currency ({{ $company->currency_code ?? 'SGD' }}) Equivalent:</span>
                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $company->currency_symbol ?? 'S$' }}{{ number_format($bill->total * $bill->exchange_rate, 2) }}</span>
                </div>
                <div class="flex justify-between text-[10px] text-slate-400 dark:text-slate-500">
                    <span>Exchange Rate:</span>
                    <span>1 {{ $bill->currency_code }} = {{ number_format($bill->exchange_rate, 6) }} {{ $company->currency_code ?? 'SGD' }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Record Payment Modal -->
    <div id="paymentModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Record Bill Payment</h3>
                <button onclick="document.getElementById('paymentModal').classList.add('hidden')" class="btn-icon" aria-label="Close">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </div>

            <form action="{{ route('bills.payment', $bill->id) }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5 block">Payment Amount ($) *</label>
                    <input type="number" name="amount" value="{{ $bill->total - $bill->paid_amount }}" step="0.01" min="0.01" max="{{ $bill->total - $bill->paid_amount }}" required
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition font-bold">
                </div>

                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5 block">Deduct From Bank Account *</label>
                    <select name="bank_account_id" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                        @foreach($bankAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Bal: {{ $company->currency_symbol ?? ($currencySymbol ?? 'S$') }}{{ number_format($acc->current_balance, 2) }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5 block">Payment Date *</label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5 block">Payment Method *</label>
                        <select name="payment_method" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cash">Cash</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Credit Card">Credit Card</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5 block">Reference Number</label>
                    <input type="text" name="reference_number" placeholder="e.g. CHQ-9218 or Bank Ref"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="save" aria-hidden="true"></i> Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>

    <x-activity-timeline model-type="Bill" :model-id="$bill->id" />
</div>
@endsection
