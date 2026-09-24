@extends('layouts.app')

@section('title', 'Quotations & Estimates')

@section('content')
<div class="space-y-6">
    @if($errors->any())
    <div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-700 dark:text-red-400 text-xs">
        <ul class="list-disc pl-4 space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <!-- Header with Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Quotations & Estimates</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Create, send, and track customer quotations and estimates</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('quotes.export_csv') }}" class="btn btn-secondary">
                <i data-lucide="file-spreadsheet" aria-hidden="true"></i> Export CSV
            </a>
            <a href="{{ route('quotes.create') }}" class="btn btn-primary">
                <i data-lucide="plus" aria-hidden="true"></i> New Quote
            </a>
        </div>
    </div>

    <!-- Search -->
    <div>
        <input type="text" id="searchInput" placeholder="Search by quote number or customer name..." aria-label="Search quotes" oninput="filterTable()" class="w-full sm:w-80 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 placeholder-slate-400 dark:placeholder-slate-500">
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('quotes.index') }}" class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm flex flex-wrap items-center gap-4 text-xs">
        <div class="flex-1 min-w-[200px]">
            <select data-combobox name="customer_id" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-indigo-600 shadow-sm">
                <option value="">All Customers</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[150px]">
            <select name="status" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-indigo-600 shadow-sm">
                <option value="">All Statuses</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                <option value="accepted" {{ request('status') == 'accepted' ? 'selected' : '' }}>Accepted</option>
                <option value="declined" {{ request('status') == 'declined' ? 'selected' : '' }}>Declined</option>
                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="converted" {{ request('status') == 'converted' ? 'selected' : '' }}>Converted</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">
            <i data-lucide="filter" aria-hidden="true"></i> Filter
        </button>
        @if(request()->hasAny(['customer_id', 'status']))
            <a href="{{ route('quotes.index') }}" class="text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 text-xs">Clear</a>
        @endif
    </form>

    <!-- Quotes Table -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="ob-bulk-bar" data-bulk-delete="{{ route('quotes.bulk_delete') }}">
            <span class="ob-bulk-count">0 selected</span>
            <div class="ob-bulk-actions">
                <button type="button" class="btn btn-danger" data-bulk="delete">
                    <i data-lucide="trash-2" aria-hidden="true"></i> Delete selected
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table id="dataTable" class="w-full text-left text-xs responsive-table">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th scope="col" class="ob-check-col py-3.5 px-4">
                            <input type="checkbox" id="selectAll" aria-label="Select all quotes"
                                class="w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-0 focus:ring-offset-0 cursor-pointer">
                        </th>
                        <th class="py-3.5 px-4">Quote #</th>
                        <th class="py-3.5 px-4">Customer</th>
                        <th class="py-3.5 px-4">Date</th>
                        <th class="py-3.5 px-4">Expiry</th>
                        <th class="py-3.5 px-4">Total</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @forelse($quotes as $quote)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="ob-check-col py-3.5 px-4" data-label="">
                                <input type="checkbox" class="ob-row-check w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-0 focus:ring-offset-0 cursor-pointer"
                                    value="{{ $quote->id }}" aria-label="Select quote {{ $quote->quote_number }}">
                            </td>
                            <td data-label="Quote #" class="py-3.5 px-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                <a href="{{ route('quotes.show', $quote->id) }}" class="hover:underline">{{ $quote->quote_number }}</a>
                            </td>
                            <td data-label="Customer" class="py-3.5 px-4">
                                <p class="text-slate-900 dark:text-white font-bold">{{ $quote->customer->name ?? 'N/A' }}</p>
                                <p class="text-[10px] text-slate-500">{{ $quote->customer->email ?? '' }}</p>
                            </td>
                            <td data-label="Date" class="py-3.5 px-4 text-slate-500 dark:text-slate-400">{{ date('M d, Y', strtotime($quote->quote_date)) }}</td>
                            <td data-label="Expiry" class="py-3.5 px-4 text-slate-500 dark:text-slate-400">{{ date('M d, Y', strtotime($quote->expiry_date)) }}</td>
                            <td data-label="Total" class="py-3.5 px-4 font-extrabold text-slate-900 dark:text-white text-[13px]">
                                {{ $currencySymbol }}{{ number_format($quote->total, 2) }}
                            </td>
                            <td data-label="Status" class="py-3.5 px-4">
                                @php
                                    $badgeClasses = [
                                        'draft' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
                                        'sent' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
                                        'accepted' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                                        'declined' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
                                        'expired' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                                        'converted' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/20',
                                    ];
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wide {{ $badgeClasses[$quote->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                    {{ $quote->status }}
                                </span>
                            </td>
                            <td data-label="Actions" class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('quotes.show', $quote->id) }}" class="btn-icon" title="View Details" aria-label="View quote {{ $quote->quote_number }}">
                                        <i data-lucide="eye" aria-hidden="true"></i>
                                    </a>
                                    <a href="{{ route('quotes.edit', $quote->id) }}" class="btn-icon" title="Edit Quote" aria-label="Edit quote {{ $quote->quote_number }}">
                                        <i data-lucide="pencil" aria-hidden="true"></i>
                                    </a>
                                    <a href="{{ route('quotes.print', $quote->id) }}" target="_blank" class="btn-icon" title="Print Quote" aria-label="Print quote {{ $quote->quote_number }}">
                                        <i data-lucide="printer" aria-hidden="true"></i>
                                    </a>
                                    <a href="{{ route('quotes.public', $quote->public_token) }}" target="_blank" class="btn-icon" title="Public Shareable Client Link" aria-label="Open public link for quote {{ $quote->quote_number }}">
                                        <i data-lucide="external-link" aria-hidden="true"></i>
                                    </a>
                                    <form action="{{ route('quotes.destroy', $quote->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this quotation? This cannot be undone.')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete Quote" aria-label="Delete quote {{ $quote->quote_number }}">
                                            <i data-lucide="trash-2" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-empty-state
                                    icon="clipboard-list"
                                    title="No quotations yet"
                                    message="Create your first quotation to start sending estimates to customers"
                                    :action-url="route('quotes.create')"
                                    action-label="New Quote" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-pagination :paginator="$quotes" />
    </div>
</div>
@endsection
