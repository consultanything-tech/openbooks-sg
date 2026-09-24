@extends('layouts.app')

@section('title', 'Quote ' . $quote->quote_number)

@php
    $currencySymbol = $currencySymbol ?? $company->currency_symbol ?? 'S$';
@endphp

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <x-breadcrumbs :items="[['label' => 'Quotations', 'url' => route('quotes.index')], ['label' => $quote->quote_number]]" />

    <!-- Action Buttons -->
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('quotes.edit', $quote->id) }}" class="btn btn-secondary">
            <i data-lucide="pencil" aria-hidden="true"></i> Edit Quote
        </a>
        @if($quote->status === 'draft')
            <form action="{{ route('quotes.mark_sent', $quote->id) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn btn-secondary">
                    <i data-lucide="send" aria-hidden="true"></i> Mark as Sent
                </button>
            </form>
        @endif
        @if(in_array($quote->status, ['sent', 'draft']))
            <form action="{{ route('quotes.mark_accepted', $quote->id) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i data-lucide="check-circle" aria-hidden="true"></i> Mark Accepted
                </button>
            </form>
            <form action="{{ route('quotes.mark_declined', $quote->id) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn btn-secondary">
                    <i data-lucide="x-circle" aria-hidden="true"></i> Mark Declined
                </button>
            </form>
        @endif
        @if(in_array($quote->status, ['accepted', 'sent']))
            <form action="{{ route('quotes.convert', $quote->id) }}" method="POST" class="inline" onsubmit="return confirm('Convert this quotation to an invoice? A new invoice will be created from these line items.')">
                @csrf
                <button type="submit" class="btn btn-secondary">
                    <i data-lucide="file-text" aria-hidden="true"></i> Convert to Invoice
                </button>
            </form>
        @endif
        @if(!in_array($quote->status, ['converted']))
            <form action="{{ route('quotes.destroy', $quote->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this quotation? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger-text">
                    Delete
                </button>
            </form>
        @endif
    </div>

    <!-- Header with Status -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{{ $quote->quote_number }}</h1>
                @php
                    $badgeClasses = [
                        'draft' => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-700/40 dark:text-slate-300 dark:border-slate-600',
                        'sent' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
                        'accepted' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                        'declined' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
                        'expired' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                        'converted' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/20',
                    ];
                @endphp
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border uppercase tracking-wider {{ $badgeClasses[$quote->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                    {{ $quote->status }}
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Issued on {{ date('M d, Y', strtotime($quote->quote_date)) }} &bull; Valid until {{ date('M d, Y', strtotime($quote->expiry_date)) }}
                @if(($quote->currency_code ?? 'SGD') !== ($company->currency_code ?? 'SGD'))
                    &bull; <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $quote->currency_code }} @ {{ number_format($quote->exchange_rate, 6) }}</span>
                @endif
            </p>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Last updated {{ $quote->updated_at ? $quote->updated_at->diffForHumans() : '—' }}</p>
            @if($quote->status === 'converted' && $quote->invoice_id)
                <p class="text-xs mt-1">
                    <span class="text-purple-600 dark:text-purple-400 font-semibold">
                        <i data-lucide="arrow-right" class="w-4 h-4 mr-1"></i>Converted to Invoice:
                        <a href="{{ route('invoices.show', $quote->invoice_id) }}" class="underline hover:text-purple-700 dark:hover:text-purple-300">{{ $quote->invoice->invoice_number ?? 'View Invoice' }}</a>
                    </span>
                </p>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('quotes.print', $quote->id) }}" target="_blank" class="btn btn-secondary">
                <i data-lucide="printer" aria-hidden="true"></i> Print
            </a>
            <a href="{{ route('quotes.public', $quote->public_token) }}" target="_blank" class="btn btn-secondary">
                <i data-lucide="external-link" aria-hidden="true"></i> Client Link
            </a>
            <button type="button" class="btn-icon" data-copy="{{ route('quotes.public', $quote->public_token) }}" title="Copy" aria-label="Copy"><i data-lucide="copy" aria-hidden="true"></i></button>
        </div>
    </div>

    <!-- Printable Style Quote Card -->
    <div class="p-8 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm space-y-8">
        <!-- Top Metadata & Company Brand -->
        <div class="flex flex-col sm:flex-row justify-between items-start border-b border-slate-100 dark:border-slate-800 pb-8 gap-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    @if(($company->show_logo_on_documents ?? true) && !empty($company->logo_path))
                        <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="h-10 w-auto">
                    @else
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center font-bold" style="background-color: {{ $company->accent_color ?? '#2563eb' }}10; color: {{ $company->accent_color ?? '#2563eb' }};">
                            <i data-lucide="coins" class="w-4 h-4"></i>
                        </div>
                    @endif
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ $company->name ?? 'OpenBooks Enterprise' }}</h2>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $company->address ?? 'Corporate Headquarters' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $company->city ?? '' }}, {{ $company->country ?? '' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Email: {{ $company->email ?? 'billing@openbooks.sg' }}</p>
                @if(($company->show_phone_on_documents ?? true) && !empty($company->phone))
                    <p class="text-xs text-slate-500 dark:text-slate-400">Phone: {{ $company->phone }}</p>
                @endif
                @if(($company->show_tax_number_on_documents ?? true) && !empty($company->tax_number))
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">Tax ID: {{ $company->tax_number }}</p>
                @endif
            </div>

            <div class="text-left sm:text-right">
                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase">QUOTATION</h3>
                <p class="text-xs font-mono font-bold mt-1" style="color: {{ $company->accent_color ?? '#2563eb' }};">{{ $quote->quote_number }}</p>
                @if($quote->reference_number ?? false)
                    <p class="text-xs text-slate-500 mt-0.5">Ref #: {{ $quote->reference_number }}</p>
                @endif
            </div>
        </div>

        <!-- Quoted To Details -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
            <div>
                <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Quoted To</h4>
                <h5 class="text-sm font-bold text-slate-900 dark:text-white">{{ $quote->customer->name ?? 'Valued Customer' }}</h5>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $quote->customer->address ?? '' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $quote->customer->city ?? '' }}, {{ $quote->customer->country ?? '' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Email: {{ $quote->customer->email ?? 'N/A' }}</p>
                @if($quote->customer->phone ?? false)
                    <p class="text-xs text-slate-500 dark:text-slate-400">Phone: {{ $quote->customer->phone }}</p>
                @endif
            </div>

            <div class="space-y-2 text-xs sm:text-right">
                <div class="flex justify-between sm:justify-end sm:gap-6">
                    <span class="text-slate-500 dark:text-slate-400">Quote Date:</span>
                    <span class="text-slate-900 dark:text-white font-medium">{{ date('M d, Y', strtotime($quote->quote_date)) }}</span>
                </div>
                <div class="flex justify-between sm:justify-end sm:gap-6">
                    <span class="text-slate-500 dark:text-slate-400">Valid Until:</span>
                    <span class="text-slate-900 dark:text-white font-medium">{{ date('M d, Y', strtotime($quote->expiry_date)) }}</span>
                </div>
                <div class="flex justify-between sm:justify-end sm:gap-6 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <span class="text-slate-700 dark:text-slate-300 font-bold">Quote Total:</span>
                    <span class="font-extrabold text-sm" style="color: {{ $company->accent_color ?? '#2563eb' }};">{{ $currencySymbol }}{{ number_format($quote->total, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 font-semibold border-y border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-3 w-8">#</th>
                        <th class="py-3 px-3">Description</th>
                        <th class="py-3 px-3 text-center">Qty</th>
                        <th class="py-3 px-3 text-right">Unit Price</th>
                        <th class="py-3 px-3 text-right">Amount</th>
                        <th class="py-3 px-3 text-right">Tax</th>
                        <th class="py-3 px-3 text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @foreach($quote->items as $idx => $it)
                        <tr>
                            <td class="py-3.5 px-3 text-slate-400 dark:text-slate-500">{{ $idx + 1 }}</td>
                            <td class="py-3.5 px-3">
                                <span class="font-medium text-slate-900 dark:text-white">{{ $it->name }}</span>
                                @if($it->description)
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">{{ $it->description }}</p>
                                @endif
                            </td>
                            <td class="py-3.5 px-3 text-center">{{ $it->quantity }}</td>
                            <td class="py-3.5 px-3 text-right">{{ $currencySymbol }}{{ number_format($it->price, 2) }}</td>
                            <td class="py-3.5 px-3 text-right">{{ $currencySymbol }}{{ number_format($it->quantity * $it->price, 2) }}</td>
                            <td class="py-3.5 px-3 text-right text-slate-500 dark:text-slate-400">
                                <span class="text-[10px]">{{ number_format($it->tax_rate, 0) }}%</span>
                                {{ $currencySymbol }}{{ number_format($it->tax_amount, 2) }}
                            </td>
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
                    <span class="text-slate-900 dark:text-white font-medium">{{ $currencySymbol }}{{ number_format($quote->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-500 dark:text-slate-400">
                    <span>GST (Tax):</span>
                    <span class="text-slate-900 dark:text-white font-medium">{{ $currencySymbol }}{{ number_format($quote->tax_total, 2) }}</span>
                </div>
                @if(($quote->discount ?? 0) > 0)
                    <div class="flex justify-between text-slate-500 dark:text-slate-400">
                        <span>Discount:</span>
                        <span class="text-red-500">-{{ $currencySymbol }}{{ number_format($quote->discount, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-base font-bold text-slate-900 dark:text-white pt-2 border-t border-slate-200 dark:border-slate-800">
                    <span>Quote Total:</span>
                    <span class="font-black" style="color: {{ $company->accent_color ?? '#2563eb' }};">{{ $currencySymbol }}{{ number_format($quote->total, 2) }}</span>
                </div>
                @if(($quote->currency_code ?? 'SGD') !== ($company->currency_code ?? 'SGD'))
                <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400 pt-1">
                    <span>Base Currency ({{ $company->currency_code ?? 'SGD' }}) Equivalent:</span>
                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $company->currency_symbol ?? 'S$' }}{{ number_format($quote->total * $quote->exchange_rate, 2) }}</span>
                </div>
                <div class="flex justify-between text-[10px] text-slate-400 dark:text-slate-500">
                    <span>Exchange Rate:</span>
                    <span>1 {{ $quote->currency_code }} = {{ number_format($quote->exchange_rate, 6) }} {{ $company->currency_code ?? 'SGD' }}</span>
                </div>
                @endif
            </div>
        </div>

        @if($quote->notes)
            <div class="pt-6 border-t border-slate-100 dark:border-slate-800">
                <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Notes</h4>
                <p class="text-xs text-slate-600 dark:text-slate-400 whitespace-pre-line">{{ $quote->notes }}</p>
            </div>
        @endif

        @if($quote->terms)
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Terms & Conditions</h4>
                <p class="text-xs text-slate-600 dark:text-slate-400 whitespace-pre-line">{{ $quote->terms }}</p>
            </div>
        @endif
    </div>

    <x-activity-timeline model-type="Quote" :model-id="$quote->id" />
</div>
@endsection
