@extends('layouts.app')

@section('title', 'Credit Notes')

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
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Credit Notes</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Issue and manage credit notes for returns, corrections, or adjustments</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('credit_notes.create') }}" class="btn btn-primary">
                <i data-lucide="plus" aria-hidden="true"></i> New Credit Note
            </a>
        </div>
    </div>

    <!-- Search -->
    <div>
        <input type="text" id="searchInput" placeholder="Search by credit note number or customer name..." aria-label="Search credit notes" oninput="filterTable()" class="w-full sm:w-80 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 placeholder-slate-400 dark:placeholder-slate-500">
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('credit_notes.index') }}" class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm flex flex-wrap items-center gap-4 text-xs">
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
                <option value="issued" {{ request('status') == 'issued' ? 'selected' : '' }}>Issued</option>
                <option value="applied" {{ request('status') == 'applied' ? 'selected' : '' }}>Applied</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">
            <i data-lucide="filter" aria-hidden="true"></i> Filter
        </button>
        @if(request()->hasAny(['customer_id', 'status']))
            <a href="{{ route('credit_notes.index') }}" class="text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 text-xs">Clear</a>
        @endif
    </form>

    <!-- Credit Notes Table -->
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="dataTable" class="w-full text-left text-xs responsive-table">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">Credit Note #</th>
                        <th class="py-3.5 px-4">Customer</th>
                        <th class="py-3.5 px-4">Invoice</th>
                        <th class="py-3.5 px-4">Date</th>
                        <th class="py-3.5 px-4">Amount</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @forelse($creditNotes as $cn)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td data-label="Credit Note #" class="py-3.5 px-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                <a href="{{ route('credit_notes.show', $cn->id) }}" class="hover:underline">{{ $cn->credit_note_number }}</a>
                            </td>
                            <td data-label="Customer" class="py-3.5 px-4">
                                <p class="text-slate-900 dark:text-white font-bold">{{ $cn->customer->name ?? 'N/A' }}</p>
                                <p class="text-[10px] text-slate-500">{{ $cn->customer->email ?? '' }}</p>
                            </td>
                            <td data-label="Invoice" class="py-3.5 px-4 text-slate-500 dark:text-slate-400">
                                @if($cn->invoice)
                                    <a href="{{ route('invoices.show', $cn->invoice_id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-mono text-[11px]">{{ $cn->invoice->invoice_number }}</a>
                                @else
                                    <span class="text-slate-400">--</span>
                                @endif
                            </td>
                            <td data-label="Date" class="py-3.5 px-4 text-slate-500 dark:text-slate-400">{{ date('M d, Y', strtotime($cn->credit_note_date)) }}</td>
                            <td data-label="Amount" class="py-3.5 px-4 font-extrabold text-slate-900 dark:text-white text-[13px]">
                                {{ $currencySymbol }}{{ number_format($cn->total, 2) }}
                            </td>
                            <td data-label="Status" class="py-3.5 px-4">
                                @php
                                    $badgeClasses = [
                                        'draft' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
                                        'issued' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
                                        'applied' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                                        'cancelled' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
                                    ];
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wide {{ $badgeClasses[$cn->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                    {{ $cn->status }}
                                </span>
                            </td>
                            <td data-label="Actions" class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('credit_notes.show', $cn->id) }}" class="btn-icon" title="View Details" aria-label="View Details">
                                        <i data-lucide="eye" aria-hidden="true"></i>
                                    </a>
                                    @if($cn->status === 'draft')
                                    <a href="{{ route('credit_notes.edit', $cn->id) }}" class="btn-icon" title="Edit" aria-label="Edit">
                                        <i data-lucide="pencil" aria-hidden="true"></i>
                                    </a>
                                    @endif
                                    @if(in_array($cn->status, ['draft', 'cancelled']))
                                    <form action="{{ route('credit_notes.destroy', $cn->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this credit note? This cannot be undone.')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete" aria-label="Delete">
                                            <i data-lucide="trash-2" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-empty-state
                                    icon="file-minus"
                                    title="No credit notes yet"
                                    message="Create your first credit note for returns or adjustments"
                                    :action-url="route('credit_notes.create')"
                                    action-label="New Credit Note" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-pagination :paginator="$creditNotes" />
    </div>
</div>
@endsection
