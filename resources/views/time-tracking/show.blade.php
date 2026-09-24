@extends('layouts.app')

@section('title', 'Time Entry Details')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-4">
        <a href="{{ route('time_tracking.index') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">Time Tracking</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-slate-900 dark:text-white font-medium">Entry #{{ $entry->id }}</span>
    </nav>

    <!-- Action Buttons -->
    <div class="flex items-center gap-2 flex-wrap">
        @canEdit
        @if(!$entry->is_invoiced)
            <a href="{{ route('time_tracking.edit', $entry->id) }}" class="btn btn-secondary">
                <i data-lucide="pencil" aria-hidden="true"></i> Edit
            </a>
            <form action="{{ route('time_tracking.destroy', $entry->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this time entry? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger-text">
                    Delete
                </button>
            </form>
        @endif
        @endcanEdit
        <a href="{{ route('time_tracking.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left" aria-hidden="true"></i> Back to List
        </a>
    </div>

    <!-- Title & Badges -->
    <div>
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Time Entry #{{ $entry->id }}</h1>
            @if($entry->is_billable)
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border uppercase bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20">Billable</span>
            @else
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border uppercase bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700">Non-Billable</span>
            @endif
            @if($entry->is_invoiced)
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border uppercase bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20">Invoiced</span>
            @else
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border uppercase bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20">Not Invoiced</span>
            @endif
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            {{ date('M d, Y', strtotime($entry->entry_date)) }} &bull; {{ $entry->customer->name ?? 'N/A' }}
        </p>
        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Last updated {{ $entry->updated_at ? $entry->updated_at->diffForHumans() : '—' }}</p>
    </div>

    <!-- Details Card -->
    <div class="p-8 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-b border-slate-100 dark:border-slate-800 pb-6">
            <div class="space-y-4">
                <div>
                    <h3 class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Customer</h3>
                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $entry->customer->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <h3 class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Project</h3>
                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $entry->project ?? '-' }}</p>
                </div>
                <div>
                    <h3 class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Date</h3>
                    <p class="text-sm text-slate-700 dark:text-slate-300">{{ date('M d, Y', strtotime($entry->entry_date)) }}</p>
                </div>
            </div>
            <div class="space-y-4">
                <div>
                    <h3 class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Hours</h3>
                    <p class="text-sm font-bold text-slate-900 dark:text-white font-mono">{{ number_format($entry->hours, 2) }}</p>
                </div>
                <div>
                    <h3 class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Rate</h3>
                    <p class="text-sm text-slate-700 dark:text-slate-300 font-mono">{{ $currencySymbol }}{{ number_format($entry->rate, 2) }}</p>
                </div>
                <div>
                    <h3 class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Amount</h3>
                    <p class="text-xl font-extrabold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($entry->amount, 2) }}</p>
                </div>
            </div>
        </div>

        @if($entry->description)
        <div class="border-b border-slate-100 dark:border-slate-800 pb-6">
            <h3 class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Description</h3>
            <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ $entry->description }}</p>
        </div>
        @endif

        <!-- Billable / Invoiced Status -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 rounded-xl {{ $entry->is_billable ? 'bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20' : 'bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700' }}">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg {{ $entry->is_billable ? 'bg-emerald-100 dark:bg-emerald-500/20' : 'bg-slate-100 dark:bg-slate-700' }} flex items-center justify-center shrink-0">
                        <i data-lucide="banknote" class="w-4 h-4 {{ $entry->is_billable ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold {{ $entry->is_billable ? 'text-emerald-800 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-400' }}">Billable Status</h4>
                        <p class="text-[10px] {{ $entry->is_billable ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}">{{ $entry->is_billable ? 'This entry is billable to the customer' : 'This entry is not billable' }}</p>
                    </div>
                </div>
            </div>
            <div class="p-4 rounded-xl {{ $entry->is_invoiced ? 'bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20' : 'bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20' }}">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg {{ $entry->is_invoiced ? 'bg-blue-100 dark:bg-blue-500/20' : 'bg-amber-100 dark:bg-amber-500/20' }} flex items-center justify-center shrink-0">
                        <i data-lucide="file-text" class="w-4 h-4 {{ $entry->is_invoiced ? 'text-blue-600 dark:text-blue-400' : 'text-amber-600 dark:text-amber-400' }}"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold {{ $entry->is_invoiced ? 'text-blue-800 dark:text-blue-400' : 'text-amber-800 dark:text-amber-400' }}">Invoice Status</h4>
                        <p class="text-[10px] {{ $entry->is_invoiced ? 'text-blue-600 dark:text-blue-400' : 'text-amber-600 dark:text-amber-400' }}">{{ $entry->is_invoiced ? 'This entry has been invoiced' : 'This entry has not been invoiced yet' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Linked Invoice -->
        @if($entry->invoice_id)
        <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-500/20 flex items-center justify-center shrink-0">
                    <i data-lucide="link" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-blue-800 dark:text-blue-400 mb-0.5">Linked Invoice</h4>
                    <p class="text-[10px] text-blue-600 dark:text-blue-400">
                        This time entry is linked to invoice
                        <a href="{{ route('invoices.show', $entry->invoice_id) }}" class="underline font-semibold hover:text-blue-800 dark:hover:text-blue-200 transition">#{{ $entry->invoice_id }}</a>.
                    </p>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
