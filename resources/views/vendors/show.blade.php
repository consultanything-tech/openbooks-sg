@extends('layouts.app')

@section('title', $vendor->name . ' - Vendor Profile')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <x-breadcrumbs :items="[['label' => 'Vendors', 'url' => route('vendors.index')], ['label' => $vendor->name]]" />

    <!-- Action Buttons -->
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('vendors.edit', $vendor->id) }}" class="btn btn-secondary">
            <i data-lucide="pencil" aria-hidden="true"></i> Edit
        </a>
        @if($vendor->bills->isEmpty())
            <form action="{{ route('vendors.destroy', $vendor->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this vendor? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger-text">
                    Delete
                </button>
            </form>
        @endif
        <a href="{{ route('bills.create') }}?vendor_id={{ $vendor->id }}" class="btn btn-primary">
            <i data-lucide="plus" aria-hidden="true"></i> New Purchase Bill
        </a>
    </div>

    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{{ $vendor->name }}</h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Vendor expense history, contact information, and payable obligations</p>
        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Last updated {{ $vendor->updated_at ? $vendor->updated_at->diffForHumans() : '—' }}</p>
    </div>

    <!-- Vendor Profile Details Card -->
    <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80">
        <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4 border-b border-slate-100 dark:border-slate-800 pb-2 flex items-center gap-2">
            <i data-lucide="contact" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
            <span>Supplier Information & Contact Details</span>
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5 block">Business / Company Name</span>
                <p class="text-slate-900 dark:text-white font-bold mt-0.5">{{ $vendor->company_name ?: $vendor->name }}</p>
            </div>
            <div>
                <span class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5 block">Email Address</span>
                <p class="text-slate-900 dark:text-white font-semibold mt-0.5">
                    @if($vendor->email)
                        <a href="mailto:{{ $vendor->email }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $vendor->email }}</a>
                    @else
                        <span class="text-slate-400 dark:text-slate-500">—</span>
                    @endif
                </p>
            </div>
            <div>
                <span class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5 block">Phone Number</span>
                <p class="text-slate-900 dark:text-white font-semibold mt-0.5">{{ $vendor->phone ?: '—' }}</p>
            </div>
            <div>
                <span class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5 block">UEN / Tax ID</span>
                <p class="text-slate-900 dark:text-white font-mono font-bold mt-0.5 uppercase">{{ $vendor->tax_number ?: 'Not Registered' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
            <div class="md:col-span-2">
                <span class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5 block">Billing Address</span>
                <p class="text-slate-800 dark:text-slate-300 mt-0.5">{{ $vendor->address ?: 'No address registered' }}</p>
            </div>
            <div>
                <span class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5 block">City & Location</span>
                <p class="text-slate-800 dark:text-slate-300 mt-0.5">{{ $vendor->city ? ($vendor->city . ', ' . ($vendor->country ?? 'India')) : ($vendor->country ?? '—') }}</p>
            </div>
            <div>
                <span class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5 block">Status</span>
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20 mt-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active Supplier
                </span>
            </div>
        </div>
    </div>

    <!-- Overview Financial Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80">
            <span class="text-xs text-slate-600 dark:text-slate-400 uppercase font-semibold">Total Invoiced / Billed</span>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-1">{{ $currencySymbol }}{{ number_format($vendor->bills->sum('total'), 2) }}</h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Across {{ count($vendor->bills) }} recorded bills</p>
        </div>
        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80">
            <span class="text-xs text-slate-600 dark:text-slate-400 uppercase font-semibold">Total Settled / Paid</span>
            <h3 class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $currencySymbol }}{{ number_format($vendor->bills->sum('paid_amount'), 2) }}</h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Payments made to supplier</p>
        </div>
        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80">
            <span class="text-xs text-slate-600 dark:text-slate-400 uppercase font-semibold">Payable Outstanding Due</span>
            <h3 class="text-xl font-bold {{ $vendor->outstanding_balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-800 dark:text-slate-200' }} mt-1">
                {{ $currencySymbol }}{{ number_format($vendor->outstanding_balance, 2) }}
            </h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Current pending obligations</p>
        </div>
    </div>

    <!-- Bills List -->
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Purchase Bills History</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Itemized bills and expenses received from {{ $vendor->name }}</p>
            </div>
            <a href="{{ route('bills.create') }}?vendor_id={{ $vendor->id }}" class="btn btn-primary btn-sm"><i data-lucide="plus" aria-hidden="true"></i> Add Bill</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Bill #</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Due Date</th>
                        <th class="py-3 px-4 text-right">Total Amount</th>
                        <th class="py-3 px-4 text-right">Paid</th>
                        <th class="py-3 px-4 text-right">Balance Due</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @forelse($vendor->bills as $b)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                <a href="{{ route('bills.show', $b->id) }}">{{ $b->bill_number }}</a>
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400">{{ date('M d, Y', strtotime($b->bill_date)) }}</td>
                            <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400">{{ date('M d, Y', strtotime($b->due_date)) }}</td>
                            <td class="py-3.5 px-4 text-right font-bold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($b->total, 2) }}</td>
                            <td class="py-3.5 px-4 text-right font-semibold text-emerald-600 dark:text-emerald-400">{{ $currencySymbol }}{{ number_format($b->paid_amount, 2) }}</td>
                            <td class="py-3.5 px-4 text-right font-bold text-red-600 dark:text-red-400">{{ $currencySymbol }}{{ number_format($b->due_amount, 2) }}</td>
                            <td class="py-3.5 px-4">
                                @php
                                    $badgeClasses = [
                                        'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                                        'received' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
                                        'partial' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                                        'overdue' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
                                    ];
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border uppercase {{ $badgeClasses[$b->status] ?? 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700' }}">
                                    {{ $b->status }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('bills.show', $b->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 font-semibold">View &rarr;</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 dark:text-slate-500">No purchase bills recorded for this supplier yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-activity-timeline model-type="Vendor" :model-id="$vendor->id" />
</div>
@endsection
