@extends('layouts.app')

@section('title', 'Expense Claims')

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
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Expense Claims</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Submit and track employee expense reimbursement claims</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            @canEdit
            <a href="{{ route('receipt_ocr.scan') }}" class="btn btn-secondary">
                <i data-lucide="camera" aria-hidden="true"></i> Scan Receipt
            </a>
            <a href="{{ route('expense_claims.create') }}" class="btn btn-primary">
                <i data-lucide="plus" aria-hidden="true"></i> New Claim
            </a>
            @endcanEdit
        </div>
    </div>

    <!-- Search -->
    <div>
        <input type="text" id="searchInput" placeholder="Search by claim number, employee, or title..." aria-label="Search expense claims" oninput="filterTable()" class="w-full sm:w-80 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 placeholder-slate-400 dark:placeholder-slate-500">
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('expense_claims.index') }}" class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm flex flex-wrap items-center gap-4 text-xs">
        <div class="min-w-[150px]">
            <select name="status" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                <option value="">All Statuses</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">
            <i data-lucide="filter" aria-hidden="true"></i> Filter
        </button>
        @if(request()->hasAny(['status']))
            <a href="{{ route('expense_claims.index') }}" class="text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">Clear</a>
        @endif
    </form>

    <!-- Claims Table -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="dataTable" class="w-full text-left text-xs responsive-table">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Claim #</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Employee</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Date</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Title</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Amount</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Category</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Status</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($claims as $claim)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td data-label="Claim #" class="px-4 py-3 text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                <a href="{{ route('expense_claims.show', $claim->id) }}" class="hover:underline">{{ $claim->claim_number }}</a>
                            </td>
                            <td data-label="Employee" class="px-4 py-3 text-xs font-medium text-slate-900 dark:text-white">{{ $claim->employee_name ?? ($claim->user->name ?? 'N/A') }}</td>
                            <td data-label="Date" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">{{ date('M d, Y', strtotime($claim->claim_date)) }}</td>
                            <td data-label="Title" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">{{ Str::limit($claim->title, 40) }}</td>
                            <td data-label="Amount" class="px-4 py-3 text-xs font-bold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($claim->amount, 2) }}</td>
                            <td data-label="Category" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">{{ $claim->category ?? 'General' }}</td>
                            <td data-label="Status" class="px-4 py-3 text-xs">
                                @php
                                    $badgeClasses = [
                                        'draft'     => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700',
                                        'submitted' => 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20',
                                        'approved'  => 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20',
                                        'rejected'  => 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-500/20',
                                        'paid'      => 'bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400 border border-purple-200 dark:border-purple-500/20',
                                    ];
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase {{ $badgeClasses[$claim->status] ?? 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700' }}">
                                    {{ $claim->status }}
                                </span>
                            </td>
                            <td data-label="Actions" class="px-4 py-3 text-xs text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('expense_claims.show', $claim->id) }}" class="btn-icon" title="View" aria-label="View">
                                        <i data-lucide="eye" aria-hidden="true"></i>
                                    </a>
                                    @if($claim->status !== 'paid')
                                        <form action="{{ route('expense_claims.destroy', $claim->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this expense claim? This cannot be undone.')" class="inline">
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
                            <td colspan="8">
                                @canEdit
                                <x-empty-state
                                    icon="file-text"
                                    title="No expense claims yet"
                                    message="Submit your first expense claim for reimbursement"
                                    :action-url="route('expense_claims.create')"
                                    action-label="New Claim" />
                                @else
                                <x-empty-state
                                    icon="file-text"
                                    title="No expense claims yet"
                                    message="Submit your first expense claim for reimbursement" />
                                @endcanEdit
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-pagination :paginator="$claims" />
    </div>
</div>
@endsection
