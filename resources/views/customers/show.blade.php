@extends('layouts.app')

@section('title', $customer->name . ' - Customer Profile')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <x-breadcrumbs :items="[['label' => 'Customers', 'url' => route('customers.index')], ['label' => $customer->name]]" />

    <!-- Action Buttons -->
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-secondary">
            <i data-lucide="pencil" aria-hidden="true"></i> Edit
        </a>
        @if($customer->invoices->isEmpty())
            <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this customer? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger-text">
                    Delete
                </button>
            </form>
        @endif
    </div>

    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{{ $customer->name }}</h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Customer account ledger and billing history</p>
        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Last updated {{ $customer->updated_at ? $customer->updated_at->diffForHumans() : '—' }}</p>
    </div>

    <!-- Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80">
            <span class="text-xs text-slate-600 dark:text-slate-400 uppercase font-semibold">Total Invoiced</span>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-1">{{ $currencySymbol }}{{ number_format($customer->invoices->sum('total_amount'), 2) }}</h3>
        </div>
        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80">
            <span class="text-xs text-slate-600 dark:text-slate-400 uppercase font-semibold">Total Paid</span>
            <h3 class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $currencySymbol }}{{ number_format($customer->invoices->sum('paid_amount'), 2) }}</h3>
        </div>
        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80">
            <span class="text-xs text-slate-600 dark:text-slate-400 uppercase font-semibold">Outstanding Due</span>
            <h3 class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ $currencySymbol }}{{ number_format($customer->outstanding_balance, 2) }}</h3>
        </div>
    </div>

    <!-- Invoices List -->
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Invoices History</h3>
            <a href="{{ route('invoices.create') }}?customer_id={{ $customer->id }}" class="btn btn-primary"><i data-lucide="plus" aria-hidden="true"></i> New Invoice</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Invoice #</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Due Date</th>
                        <th class="py-3 px-4">Amount</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @forelse($customer->invoices as $inv)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $inv->invoice_number }}</td>
                            <td class="py-3 px-4 text-slate-500 dark:text-slate-400">{{ date('M d, Y', strtotime($inv->invoice_date)) }}</td>
                            <td class="py-3 px-4 text-slate-500 dark:text-slate-400">{{ date('M d, Y', strtotime($inv->due_date)) }}</td>
                            <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($inv->total_amount, 2) }}</td>
                            <td class="py-3 px-4">
                                @php
                                    $badgeClasses = [
                                        'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                                        'sent' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
                                        'draft' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
                                        'overdue' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
                                        'partial' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                                    ];
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border uppercase {{ $badgeClasses[$inv->status] ?? 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700' }}">
                                    {{ $inv->status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('invoices.show', $inv->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 font-semibold">View &rarr;</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400 dark:text-slate-500">No invoices issued for this customer.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-activity-timeline model-type="Customer" :model-id="$customer->id" />
</div>
@endsection
