@extends('layouts.app')

@section('title', 'Credit Note ' . $creditNote->credit_note_number)

@php
    $currencySymbol = $currencySymbol ?? $company->currency_symbol ?? 'S$';
@endphp

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-4">
        <a href="{{ route('credit_notes.index') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">Credit Notes</a>
        <i data-lucide="chevron-right" class="w-3 h-3" aria-hidden="true"></i>
        <span class="text-slate-900 dark:text-white font-medium">{{ $creditNote->credit_note_number }}</span>
    </nav>

    <!-- Action Buttons -->
    <div class="flex items-center gap-2 flex-wrap">
        @if($creditNote->status === 'draft')
            <a href="{{ route('credit_notes.edit', $creditNote->id) }}" class="btn btn-secondary">
                <i data-lucide="pencil" aria-hidden="true"></i> Edit Credit Note
            </a>
        @endif
        @if($creditNote->status === 'issued')
            <form action="{{ route('credit_notes.apply', $creditNote->id) }}" method="POST" class="inline" onsubmit="return confirm('Apply this credit note? This will adjust the linked invoice and customer balance.')">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="check-check" aria-hidden="true"></i> Apply Credit Note
                </button>
            </form>
        @endif
        @if(in_array($creditNote->status, ['draft', 'cancelled']))
            <form action="{{ route('credit_notes.destroy', $creditNote->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this credit note? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger-text">
                    Delete
                </button>
            </form>
        @endif
    </div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{{ $creditNote->credit_note_number }}</h1>
                <a href="{{ route('credit_notes.print', $creditNote->id) }}" target="_blank" class="btn btn-secondary">
                    <i data-lucide="printer" aria-hidden="true"></i> Print
                </a>
                @php
                    $badgeClasses = [
                        'draft' => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-700/40 dark:text-slate-300 dark:border-slate-600',
                        'issued' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
                        'applied' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                        'cancelled' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
                    ];
                @endphp
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border uppercase tracking-wider {{ $badgeClasses[$creditNote->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                    {{ $creditNote->status }}
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Issued on {{ date('M d, Y', strtotime($creditNote->credit_note_date)) }}
                @if($creditNote->invoice)
                    &bull; Ref: <a href="{{ route('invoices.show', $creditNote->invoice_id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $creditNote->invoice->invoice_number }}</a>
                @endif
            </p>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Last updated {{ $creditNote->updated_at ? $creditNote->updated_at->diffForHumans() : '—' }}</p>
        </div>
    </div>

    <!-- Credit Note Card -->
    <div class="p-8 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm space-y-8">
        <!-- Top Metadata & Company Brand -->
        <div class="flex flex-col sm:flex-row justify-between items-start border-b border-slate-100 dark:border-slate-800 pb-8 gap-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    @if(!empty($company->logo_path))
                        <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="h-10 w-auto">
                    @else
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400 flex items-center justify-center font-bold">
                            <i data-lucide="coins" class="w-4 h-4"></i>
                        </div>
                    @endif
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ $company->name ?? 'OpenBooks Enterprise' }}</h2>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $company->address ?? 'Corporate Headquarters' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $company->city ?? '' }}, {{ $company->country ?? '' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Email: {{ $company->email ?? 'billing@openbooks.sg' }}</p>
                @if(!empty($company->tax_number))
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">Tax ID: {{ $company->tax_number }}</p>
                @endif
            </div>

            <div class="text-left sm:text-right">
                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase">CREDIT NOTE</h3>
                <p class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ $creditNote->credit_note_number }}</p>
                @if($creditNote->invoice)
                    <p class="text-xs text-slate-500 mt-0.5">Ref Invoice: {{ $creditNote->invoice->invoice_number }}</p>
                @endif
            </div>
        </div>

        <!-- Issued To Details -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
            <div>
                <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Issued To</h4>
                <h5 class="text-sm font-bold text-slate-900 dark:text-white">{{ $creditNote->customer->name ?? 'Valued Customer' }}</h5>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $creditNote->customer->address ?? '' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $creditNote->customer->city ?? '' }}, {{ $creditNote->customer->country ?? '' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Email: {{ $creditNote->customer->email ?? 'N/A' }}</p>
                @if($creditNote->customer->phone ?? false)
                    <p class="text-xs text-slate-500 dark:text-slate-400">Phone: {{ $creditNote->customer->phone }}</p>
                @endif
            </div>

            <div class="space-y-2 text-xs sm:text-right">
                <div class="flex justify-between sm:justify-end sm:gap-6">
                    <span class="text-slate-500 dark:text-slate-400">Credit Note Date:</span>
                    <span class="text-slate-900 dark:text-white font-medium">{{ date('M d, Y', strtotime($creditNote->credit_note_date)) }}</span>
                </div>
                @if($creditNote->invoice)
                <div class="flex justify-between sm:justify-end sm:gap-6">
                    <span class="text-slate-500 dark:text-slate-400">Original Invoice:</span>
                    <span class="text-slate-900 dark:text-white font-medium">{{ $creditNote->invoice->invoice_number }}</span>
                </div>
                @endif
                <div class="flex justify-between sm:justify-end sm:gap-6 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <span class="text-slate-700 dark:text-slate-300 font-bold">Credit Amount:</span>
                    <span class="text-indigo-600 dark:text-indigo-400 font-extrabold text-sm">{{ $currencySymbol }}{{ number_format($creditNote->total, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Reason -->
        @if($creditNote->reason)
        <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
            <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Reason</h4>
            <p class="text-xs text-slate-600 dark:text-slate-400">{{ $creditNote->reason }}</p>
        </div>
        @endif

        <!-- Line Items Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 font-semibold border-y border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-3">Description</th>
                        <th class="py-3 px-3 text-center">Qty</th>
                        <th class="py-3 px-3 text-right">Unit Price</th>
                        <th class="py-3 px-3 text-right">Tax</th>
                        <th class="py-3 px-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @foreach($creditNote->items as $it)
                        <tr>
                            <td class="py-3.5 px-3 font-medium text-slate-900 dark:text-white">{{ $it->name }}</td>
                            <td class="py-3.5 px-3 text-center">{{ $it->quantity }}</td>
                            <td class="py-3.5 px-3 text-right">{{ $currencySymbol }}{{ number_format($it->price, 2) }}</td>
                            <td class="py-3.5 px-3 text-right text-slate-500">{{ $currencySymbol }}{{ number_format($it->tax_amount, 2) }}</td>
                            <td class="py-3.5 px-3 text-right font-bold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($it->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
            <div class="w-72 space-y-2 text-xs">
                <div class="flex justify-between text-slate-500 dark:text-slate-400">
                    <span>Subtotal:</span>
                    <span class="text-slate-900 dark:text-white font-medium">{{ $currencySymbol }}{{ number_format($creditNote->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-500 dark:text-slate-400">
                    <span>Taxes (GST):</span>
                    <span class="text-slate-900 dark:text-white font-medium">{{ $currencySymbol }}{{ number_format($creditNote->tax_total, 2) }}</span>
                </div>
                <div class="flex justify-between text-base font-bold text-slate-900 dark:text-white pt-2 border-t border-slate-200 dark:border-slate-800">
                    <span>Credit Total:</span>
                    <span class="text-indigo-600 dark:text-indigo-400 font-black">{{ $currencySymbol }}{{ number_format($creditNote->total, 2) }}</span>
                </div>
            </div>
        </div>

        @if($creditNote->notes)
            <div class="pt-6 border-t border-slate-100 dark:border-slate-800">
                <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Notes</h4>
                <p class="text-xs text-slate-600 dark:text-slate-400 whitespace-pre-line">{{ $creditNote->notes }}</p>
            </div>
        @endif
    </div>

    <x-activity-timeline model-type="CreditNote" :model-id="$creditNote->id" />
</div>
@endsection
