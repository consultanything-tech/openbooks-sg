@extends('layouts.app')

@section('title', 'Recurring Templates')

@section('content')
<div class="space-y-6">
    @if($errors->any())
    <div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-700 dark:text-red-400 text-xs">
        <ul class="list-disc pl-4 space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Recurring Templates</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Automate invoice and bill generation on a schedule</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            @canEdit
            <a href="{{ route('recurring.create', ['type' => 'invoice']) }}" class="btn btn-primary">
                <i data-lucide="plus" aria-hidden="true"></i> New Recurring
            </a>
            @endcanEdit
        </div>
    </div>

    <!-- Search -->
    <div>
        <input type="text" id="searchInput" placeholder="Search by customer or vendor name..." aria-label="Search recurring templates" oninput="filterTable()" class="w-full sm:w-80 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 placeholder-slate-400 dark:placeholder-slate-500">
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="dataTable" class="w-full text-left text-xs responsive-table">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">Type</th>
                        <th class="py-3.5 px-4">Customer / Vendor</th>
                        <th class="py-3.5 px-4">Frequency</th>
                        <th class="py-3.5 px-4">Amount</th>
                        <th class="py-3.5 px-4">Next Due</th>
                        <th class="py-3.5 px-4">Last Generated</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @forelse($templates as $tpl)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td data-label="Type" class="py-3.5 px-4">
                                @if($tpl->type === 'invoice')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wide bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20">Invoice</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wide bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20">Bill</span>
                                @endif
                            </td>
                            <td data-label="Recipient" class="py-3.5 px-4">
                                <p class="text-slate-900 dark:text-white font-bold">{{ $tpl->recipient_name }}</p>
                            </td>
                            <td data-label="Frequency" class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 capitalize">{{ $tpl->frequency }}</span>
                            </td>
                            <td data-label="Amount" class="py-3.5 px-4 font-extrabold text-slate-900 dark:text-white text-[13px]">
                                {{ $currencySymbol }}{{ number_format($tpl->total, 2) }}
                            </td>
                            <td data-label="Next Due" class="py-3.5 px-4 text-slate-500 dark:text-slate-400">{{ $tpl->next_due_date->format('M d, Y') }}</td>
                            <td data-label="Last Generated" class="py-3.5 px-4 text-slate-500 dark:text-slate-400">
                                {{ $tpl->last_generated_at ? $tpl->last_generated_at->format('M d, Y') : 'Never' }}
                            </td>
                            <td data-label="Status" class="py-3.5 px-4">
                                @if($tpl->is_active)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wide bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20">Active</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wide bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700">Paused</span>
                                @endif
                            </td>
                            <td data-label="Actions" class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('recurring.show', $tpl->id) }}" class="btn-icon" title="View Details" aria-label="View Details">
                                        <i data-lucide="eye" aria-hidden="true"></i>
                                    </a>
                                    @canEdit
                                    <a href="{{ route('recurring.edit', $tpl->id) }}" class="btn-icon" title="Edit" aria-label="Edit">
                                        <i data-lucide="pencil" aria-hidden="true"></i>
                                    </a>
                                    <form action="{{ route('recurring.toggle', $tpl->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="btn-icon" title="{{ $tpl->is_active ? 'Pause' : 'Activate' }}" aria-label="{{ $tpl->is_active ? 'Pause' : 'Activate' }}">
                                            <i data-lucide="{{ $tpl->is_active ? 'pause' : 'play' }}" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('recurring.destroy', $tpl->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this recurring template?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete" aria-label="Delete">
                                            <i data-lucide="trash-2" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    @endcanEdit
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                @canEdit
                                <x-empty-state
                                    icon="refresh-cw"
                                    title="No recurring templates yet"
                                    message="Set up automated invoices or bills on a schedule"
                                    :action-url="route('recurring.create', ['type' => 'invoice'])"
                                    action-label="New Recurring" />
                                @else
                                <x-empty-state
                                    icon="refresh-cw"
                                    title="No recurring templates yet"
                                    message="Set up automated invoices or bills on a schedule" />
                                @endcanEdit
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-pagination :paginator="$templates" />
    </div>
</div>
@endsection
