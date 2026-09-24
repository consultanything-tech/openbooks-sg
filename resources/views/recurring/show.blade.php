@extends('layouts.app')

@section('title', 'Recurring Template #' . $template->id)

@php
    $currencySymbol = $currencySymbol ?? $company->currency_symbol ?? 'S$';
@endphp

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-4">
        <a href="{{ route('recurring.index') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">Recurring</a>
        <i data-lucide="chevron-right" class="w-4 h-4"></i>
        <span class="text-slate-900 dark:text-white font-medium">Template #{{ $template->id }}</span>
    </nav>

    <!-- Action Buttons -->
    <div class="flex items-center gap-2 flex-wrap">
        @canEdit
        <a href="{{ route('recurring.edit', $template->id) }}" class="btn btn-secondary">
            <i data-lucide="pencil" aria-hidden="true"></i> Edit Template
        </a>
        <form action="{{ route('recurring.toggle', $template->id) }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="btn btn-secondary">
                <i data-lucide="{{ $template->is_active ? 'pause' : 'play' }}" aria-hidden="true"></i> {{ $template->is_active ? 'Pause' : 'Activate' }}
            </button>
        </form>
        @if($template->is_active)
        <form action="{{ route('recurring.generate_now', $template->id) }}" method="POST" class="inline" onsubmit="return confirm('Generate the next {{ $template->type }} now?')">
            @csrf
            <button type="submit" class="btn btn-success">
                <i data-lucide="zap" aria-hidden="true"></i> Generate Now
            </button>
        </form>
        @endif
        <form action="{{ route('recurring.destroy', $template->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this recurring template? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger-text">
                Delete
            </button>
        </form>
        @endcanEdit
    </div>

    <!-- Title Header -->
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Template #{{ $template->id }}</h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ ucfirst($template->type) }} &bull; {{ $template->recipient_name }} &bull; {{ ucfirst($template->frequency) }}</p>
        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Last updated {{ $template->updated_at ? $template->updated_at->diffForHumans() : '—' }}</p>
    </div>

    <!-- Template Details Card -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-5">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Template Details</h2>
            <div class="flex items-center gap-2">
                @if($template->type === 'invoice')
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wide bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20">Invoice</span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wide bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20">Bill</span>
                @endif
                @if($template->is_active)
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wide bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20">Active</span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wide bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700">Paused</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
            <div>
                <p class="text-slate-500 dark:text-slate-400 mb-1">{{ $template->type === 'invoice' ? 'Customer' : 'Vendor' }}</p>
                <p class="text-slate-900 dark:text-white font-bold">{{ $template->recipient_name }}</p>
            </div>
            <div>
                <p class="text-slate-500 dark:text-slate-400 mb-1">Frequency</p>
                <p class="text-slate-900 dark:text-white font-bold capitalize">{{ $template->frequency }}</p>
            </div>
            <div>
                <p class="text-slate-500 dark:text-slate-400 mb-1">Next Due Date</p>
                <p class="text-slate-900 dark:text-white font-bold">{{ $template->next_due_date->format('M d, Y') }}</p>
            </div>
            <div>
                <p class="text-slate-500 dark:text-slate-400 mb-1">Last Generated</p>
                <p class="text-slate-900 dark:text-white font-bold">{{ $template->last_generated_at ? $template->last_generated_at->format('M d, Y') : 'Never' }}</p>
            </div>
        </div>

        @if($template->notes)
        <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
            <p class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Notes</p>
            <p class="text-xs text-slate-700 dark:text-slate-300">{{ $template->notes }}</p>
        </div>
        @endif

        @if($template->terms)
        <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
            <p class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Terms</p>
            <p class="text-xs text-slate-700 dark:text-slate-300">{{ $template->terms }}</p>
        </div>
        @endif
    </div>

    <!-- Line Items -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
        <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Line Items</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="py-2.5 px-3">Item</th>
                        <th class="py-2.5 px-3 w-20">Qty</th>
                        <th class="py-2.5 px-3 w-28">Price</th>
                        <th class="py-2.5 px-3 w-20">Tax %</th>
                        <th class="py-2.5 px-3 w-28 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($template->items as $item)
                    <tr>
                        <td class="py-2.5 px-3">
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $item->name }}</p>
                            @if($item->description)
                                <p class="text-[10px] text-slate-500">{{ $item->description }}</p>
                            @endif
                        </td>
                        <td class="py-2.5 px-3">{{ number_format($item->quantity, 2) }}</td>
                        <td class="py-2.5 px-3">{{ $currencySymbol }}{{ number_format($item->price, 2) }}</td>
                        <td class="py-2.5 px-3">{{ number_format($item->tax_rate, 2) }}%</td>
                        <td class="py-2.5 px-3 text-right font-bold">{{ $currencySymbol }}{{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800 mt-4">
            <div class="w-64 space-y-2 text-xs">
                <div class="flex justify-between text-slate-500 dark:text-slate-400">
                    <span>Subtotal:</span>
                    <span class="text-slate-900 dark:text-white font-bold">{{ $currencySymbol }}{{ number_format($template->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-500 dark:text-slate-400">
                    <span>Tax:</span>
                    <span class="text-slate-900 dark:text-white font-bold">{{ $currencySymbol }}{{ number_format($template->tax_total, 2) }}</span>
                </div>
                @if($template->discount_total > 0)
                <div class="flex justify-between text-slate-500 dark:text-slate-400">
                    <span>Discount:</span>
                    <span class="text-slate-900 dark:text-white font-bold">-{{ $currencySymbol }}{{ number_format($template->discount_total, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between text-base font-bold text-slate-900 dark:text-white pt-2 border-t border-slate-200 dark:border-slate-700">
                    <span>Total:</span>
                    <span class="text-indigo-700 dark:text-indigo-400 font-black">{{ $currencySymbol }}{{ number_format($template->total, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Generated History -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
        <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Generated History</h2>
        @if($generated->isEmpty())
            <div class="py-8 text-center">
                <p class="text-xs text-slate-500 dark:text-slate-400">No {{ $template->type }}s have been generated from this template yet.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="py-2.5 px-3">{{ $template->type === 'invoice' ? 'Invoice #' : 'Bill #' }}</th>
                            <th class="py-2.5 px-3">Date</th>
                            <th class="py-2.5 px-3">Amount</th>
                            <th class="py-2.5 px-3">Status</th>
                            <th class="py-2.5 px-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($generated as $item)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="py-2.5 px-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                @if($template->type === 'invoice')
                                    <a href="{{ route('invoices.show', $item->id) }}" class="hover:underline">{{ $item->invoice_number }}</a>
                                @else
                                    <a href="{{ route('bills.show', $item->id) }}" class="hover:underline">{{ $item->bill_number }}</a>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-slate-500 dark:text-slate-400">
                                {{ $template->type === 'invoice' ? date('M d, Y', strtotime($item->invoice_date)) : date('M d, Y', strtotime($item->bill_date)) }}
                            </td>
                            <td class="py-2.5 px-3 font-bold">{{ $currencySymbol }}{{ number_format($item->total, 2) }}</td>
                            <td class="py-2.5 px-3">
                                @php
                                    $badgeClasses = [
                                        'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                                        'sent' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
                                        'received' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
                                        'draft' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
                                        'overdue' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
                                        'partial' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                                    ];
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wide {{ $badgeClasses[$item->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right">
                                @if($template->type === 'invoice')
                                    <a href="{{ route('invoices.show', $item->id) }}" class="btn-icon" title="View" aria-label="View">
                                        <i data-lucide="eye" aria-hidden="true"></i>
                                    </a>
                                @else
                                    <a href="{{ route('bills.show', $item->id) }}" class="btn-icon" title="View" aria-label="View">
                                        <i data-lucide="eye" aria-hidden="true"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <x-activity-timeline model-type="RecurringTemplate" :model-id="$template->id" />
</div>
@endsection
