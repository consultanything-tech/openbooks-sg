@extends('layouts.app')

@section('title', 'Bills & Expenses')

@section('content')
<div class="space-y-6">
    @if($errors->any())
    <div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-700 dark:text-red-400 text-xs">
        <ul class="list-disc pl-4 space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Bills & Expenses</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Track purchase bills, operational expenses, and vendor disbursements</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('bills.export_csv') }}" class="btn btn-secondary">
                <i data-lucide="file-spreadsheet" aria-hidden="true"></i> Export CSV
            </a>
            <a href="{{ route('bills.create') }}" class="btn btn-primary">
                <i data-lucide="plus" aria-hidden="true"></i> New Bill
            </a>
        </div>
    </div>

    <!-- Search -->
    <div>
        <input type="text" id="searchInput" placeholder="Search by bill number or vendor name..." aria-label="Search bills" oninput="filterTable()" class="w-full sm:w-80 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 placeholder-slate-400 dark:placeholder-slate-500">
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('bills.index') }}" class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm flex flex-wrap items-center gap-4 text-xs">
        <div class="flex-1 min-w-[200px]">
            <select data-combobox name="vendor_id" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                <option value="">All Vendors</option>
                @foreach($vendors as $v)
                    <option value="{{ $v->id }}" {{ request('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[150px]">
            <select name="status" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                <option value="">All Statuses</option>
                <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
                <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">
            <i data-lucide="filter" aria-hidden="true"></i> Filter
        </button>
        @if(request()->hasAny(['vendor_id', 'status']))
            <a href="{{ route('bills.index') }}" class="text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">Clear</a>
        @endif
    </form>

    <!-- Bills Table -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="ob-bulk-bar" data-bulk-delete="{{ route('bills.bulk_delete') }}">
            <span class="ob-bulk-count">0 selected</span>
            <div class="ob-bulk-actions">
                <button type="button" class="btn btn-danger" data-bulk="delete">
                    <i data-lucide="trash-2" aria-hidden="true"></i> Delete selected
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table id="dataTable" class="w-full text-left text-xs responsive-table">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th scope="col" class="ob-check-col px-4 py-3">
                            <input type="checkbox" id="selectAll" aria-label="Select all bills"
                                class="w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-0 focus:ring-offset-0 cursor-pointer">
                        </th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Bill #</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Vendor</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Bill Date</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Due Date</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Total</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Paid</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Status</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($bills as $b)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="ob-check-col px-4 py-3" data-label="">
                                <input type="checkbox" class="ob-row-check w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-0 focus:ring-offset-0 cursor-pointer"
                                    value="{{ $b->id }}" aria-label="Select bill {{ $b->bill_number }}">
                            </td>
                            <td data-label="Bill #" class="px-4 py-3 text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                <a href="{{ route('bills.show', $b->id) }}" class="hover:underline">{{ $b->bill_number }}</a>
                            </td>
                            <td data-label="Vendor" class="px-4 py-3 text-xs font-medium text-slate-900 dark:text-white">{{ $b->vendor->name ?? 'N/A' }}</td>
                            <td data-label="Bill Date" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">{{ date('M d, Y', strtotime($b->bill_date)) }}</td>
                            <td data-label="Due Date" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">{{ date('M d, Y', strtotime($b->due_date)) }}</td>
                            <td data-label="Total" class="px-4 py-3 text-xs font-bold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($b->total, 2) }}</td>
                            <td data-label="Paid" class="px-4 py-3 text-xs font-semibold text-emerald-700 dark:text-emerald-400">{{ $currencySymbol }}{{ number_format($b->paid_amount, 2) }}</td>
                            <td data-label="Status" class="px-4 py-3 text-xs">
                                @php
                                    $badgeClasses = [
                                        'paid' => 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20',
                                        'received' => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700',
                                        'partial' => 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20',
                                        'overdue' => 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-500/20',
                                    ];
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase {{ $badgeClasses[$b->status] ?? 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700' }}">
                                    {{ $b->status }}
                                </span>
                            </td>
                            <td data-label="Actions" class="px-4 py-3 text-xs text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('bills.show', $b->id) }}" class="btn-icon" title="View & Pay" aria-label="View & Pay">
                                        <i data-lucide="eye" aria-hidden="true"></i>
                                    </a>
                                    <a href="{{ route('bills.edit', $b->id) }}" class="btn-icon" title="Edit Bill" aria-label="Edit Bill">
                                        <i data-lucide="pencil" aria-hidden="true"></i>
                                    </a>
                                    <form action="{{ route('bills.destroy', $b->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this bill? This cannot be undone.')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete Bill" aria-label="Delete Bill">
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
                                    title="No vendor bills yet"
                                    message="Add your first bill to start tracking vendor expenses"
                                    :action-url="route('bills.create')"
                                    action-label="New Bill" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-pagination :paginator="$bills" />
    </div>
</div>
@endsection
