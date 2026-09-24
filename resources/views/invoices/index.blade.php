@extends('layouts.app')

@section('title', 'Invoices')

@section('content')
<div class="space-y-6">
    @if($errors->any())
    <div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-700 dark:text-red-400 text-xs" role="alert" aria-live="assertive">
        <ul class="list-disc pl-4 space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <!-- Header with Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Invoices</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Generate, track, and collect customer invoices</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('invoices.export_csv') }}" class="btn btn-secondary">
                <i data-lucide="file-spreadsheet" aria-hidden="true"></i> Export CSV
            </a>
            <a href="{{ route('invoices.create') }}" class="btn btn-primary">
                <i data-lucide="plus" aria-hidden="true"></i> New Invoice
            </a>
        </div>
    </div>

    <!-- Search -->
    <div>
        <input type="text" id="searchInput" placeholder="Search by invoice number or customer name..." aria-label="Search invoices" oninput="filterTable()" class="w-full sm:w-80 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 placeholder-slate-400 dark:placeholder-slate-500">
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('invoices.index') }}" aria-label="Filter invoices" class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm flex flex-wrap items-center gap-4 text-xs">
        <div class="flex-1 min-w-[200px]">
            <label for="filter_customer" class="sr-only">Filter by customer</label>
            <select data-combobox name="customer_id" id="filter_customer" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-indigo-600 shadow-sm">
                <option value="">All Customers</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[150px]">
            <label for="filter_status" class="sr-only">Filter by status</label>
            <select name="status" id="filter_status" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-indigo-600 shadow-sm">
                <option value="">All Statuses</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent / Issued</option>
                <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">
            <i data-lucide="filter" aria-hidden="true"></i> Filter
        </button>
        @if(request()->hasAny(['customer_id', 'status']))
            <a href="{{ route('invoices.index') }}" class="text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 text-xs">Clear</a>
        @endif
    </form>

    <!-- Invoices Table -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="ob-bulk-bar" data-bulk-delete="{{ route('invoices.bulk_delete') }}">
            <span class="ob-bulk-count">0 selected</span>
            <div class="ob-bulk-actions">
                <button type="button" class="btn btn-danger" data-bulk="delete">
                    <i data-lucide="trash-2" aria-hidden="true"></i> Delete selected
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table id="dataTable" class="w-full text-left text-xs responsive-table">
                <caption class="sr-only">List of invoices with customer, date, amount, status, and actions</caption>
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th scope="col" class="ob-check-col py-3.5 px-4">
                            <input type="checkbox" id="selectAll" aria-label="Select all invoices"
                                class="w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-0 focus:ring-offset-0 cursor-pointer">
                        </th>
                        <th scope="col" class="py-3.5 px-4">Invoice #</th>
                        <th scope="col" class="py-3.5 px-4">Customer</th>
                        <th scope="col" class="py-3.5 px-4">Date</th>
                        <th scope="col" class="py-3.5 px-4">Due Date</th>
                        <th scope="col" class="py-3.5 px-4">Invoice Amount</th>
                        <th scope="col" class="py-3.5 px-4">Paid Amount</th>
                        <th scope="col" class="py-3.5 px-4">Status</th>
                        <th scope="col" class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @forelse($invoices as $inv)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="ob-check-col py-3.5 px-4" data-label="">
                                <input type="checkbox" class="ob-row-check w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-0 focus:ring-offset-0 cursor-pointer"
                                    value="{{ $inv->id }}" aria-label="Select invoice {{ $inv->invoice_number }}">
                            </td>
                            <td data-label="Invoice #" class="py-3.5 px-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                <a href="{{ route('invoices.show', $inv->id) }}" class="hover:underline">{{ $inv->invoice_number }}</a>
                            </td>
                            <td data-label="Customer" class="py-3.5 px-4">
                                <p class="text-slate-900 dark:text-white font-bold">{{ $inv->customer->name ?? 'N/A' }}</p>
                                <p class="text-[10px] text-slate-500">{{ $inv->customer->email ?? '' }}</p>
                            </td>
                            <td data-label="Date" class="py-3.5 px-4 text-slate-500 dark:text-slate-400">{{ date('M d, Y', strtotime($inv->invoice_date)) }}</td>
                            <td data-label="Due Date" class="py-3.5 px-4 text-slate-500 dark:text-slate-400">{{ date('M d, Y', strtotime($inv->due_date)) }}</td>
                            <td data-label="Amount" class="py-3.5 px-4 font-extrabold text-slate-900 dark:text-white text-[13px]">
                                {{ $currencySymbol }}{{ number_format($inv->total, 2) }}
                            </td>
                            <td data-label="Paid" class="py-3.5 px-4 font-semibold text-indigo-600 dark:text-indigo-400">
                                {{ $currencySymbol }}{{ number_format($inv->paid_amount, 2) }}
                            </td>
                            <td data-label="Status" class="py-3.5 px-4">
                                @php
                                    $badgeClasses = [
                                        'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                                        'sent' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
                                        'draft' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
                                        'overdue' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
                                        'partial' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                                    ];
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wide {{ $badgeClasses[$inv->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                    {{ $inv->status }}
                                </span>
                            </td>
                            <td data-label="Actions" class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('invoices.show', $inv->id) }}" class="btn-icon" title="View Details" aria-label="View invoice {{ $inv->invoice_number }}">
                                        <i data-lucide="eye" aria-hidden="true"></i>
                                    </a>
                                    <a href="{{ route('invoices.edit', $inv->id) }}" class="btn-icon" title="Edit Invoice" aria-label="Edit invoice {{ $inv->invoice_number }}">
                                        <i data-lucide="pencil" aria-hidden="true"></i>
                                    </a>
                                    <a href="{{ route('invoices.print', $inv->id) }}" target="_blank" class="btn-icon" title="Print Invoice" aria-label="Print invoice {{ $inv->invoice_number }}">
                                        <i data-lucide="printer" aria-hidden="true"></i>
                                    </a>
                                    <a href="{{ route('invoices.public', $inv->public_token) }}" target="_blank" class="btn-icon" title="Public Shareable Client Link" aria-label="Open public link for invoice {{ $inv->invoice_number }}">
                                        <i data-lucide="external-link" aria-hidden="true"></i>
                                    </a>
                                    <form action="{{ route('invoices.destroy', $inv->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this invoice? This cannot be undone.')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete Invoice" aria-label="Delete invoice {{ $inv->invoice_number }}">
                                            <i data-lucide="trash-2" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <x-empty-state
                                    icon="file-text"
                                    title="No invoices yet"
                                    message="Create your first invoice to start billing customers."
                                    :action-url="route('invoices.create')"
                                    action-label="New Invoice" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-pagination :paginator="$invoices" />
    </div>
</div>
@endsection
