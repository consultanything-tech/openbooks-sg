@extends('layouts.app')

@section('title', 'Journal Entries')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="mb-4 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-xs">
        <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i>{{ session('success') }}
    </div>
    @endif

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Journal Entries</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Record and manage double-entry bookkeeping transactions</p>
        </div>
        <a href="{{ route('accounts.journal_entries.create') }}" class="btn btn-primary">
            <i data-lucide="plus" aria-hidden="true"></i>
            New Journal Entry
        </a>
    </div>

    <!-- Tab Navigation -->
    <div class="flex items-center gap-1 border-b border-slate-200 dark:border-slate-800">
        <a href="{{ route('accounts.index') }}" class="px-4 py-2.5 text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
            <i data-lucide="git-branch" class="w-4 h-4 mr-1.5"></i>Chart of Accounts
        </a>
        <a href="{{ route('accounts.journal_entries') }}" class="px-4 py-2.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 dark:border-indigo-400 transition">
            <i data-lucide="book" class="w-4 h-4 mr-1.5"></i>Journal Entries
        </a>
        <a href="{{ route('accounts.trial_balance') }}" class="px-4 py-2.5 text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
            <i data-lucide="scale" class="w-4 h-4 mr-1.5"></i>Trial Balance
        </a>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('accounts.journal_entries') }}" class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm flex flex-wrap items-end gap-4 text-xs">
        <div class="min-w-[150px]">
            <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600">
        </div>
        <div class="min-w-[150px]">
            <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600">
        </div>
        <button type="submit" class="btn btn-secondary">
            <i data-lucide="filter" aria-hidden="true"></i> Filter
        </button>
        @if(request()->hasAny(['date_from', 'date_to']))
            <a href="{{ route('accounts.journal_entries') }}" class="text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 text-xs pb-2">Clear</a>
        @endif
    </form>

    <!-- Entries Table -->
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs responsive-table">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3">Entry #</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3">Date</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3">Description</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Debit Total</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Credit Total</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-center">Status</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @forelse($entries as $entry)
                        @php
                            $debitTotal = $entry->lines->sum('debit');
                            $creditTotal = $entry->lines->sum('credit');
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $entry->entry_number }}</td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ date('M d, Y', strtotime($entry->entry_date)) }}</td>
                            <td class="px-4 py-3">
                                <span class="font-semibold text-slate-900 dark:text-white">{{ $entry->description }}</span>
                                @if($entry->reference)
                                    <span class="ml-1 text-[10px] text-slate-400">({{ $entry->reference }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">{{ number_format($debitTotal, 2) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">{{ number_format($creditTotal, 2) }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($entry->is_posted)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20 uppercase">Posted</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20 uppercase">Draft</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button onclick="toggleLines({{ $entry->id }})" class="btn-icon" title="View Lines" aria-label="View Lines">
                                    <i data-lucide="list" aria-hidden="true"></i>
                                </button>
                            </td>
                        </tr>
                        <!-- Expandable Lines -->
                        <tr id="lines-{{ $entry->id }}" class="hidden">
                            <td colspan="7" class="px-4 py-3 bg-slate-50/50 dark:bg-slate-800/30">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            <th class="py-2 px-3 text-left">Account</th>
                                            <th class="py-2 px-3 text-right">Debit</th>
                                            <th class="py-2 px-3 text-right">Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                        @foreach($entry->lines as $line)
                                        <tr>
                                            <td class="py-2 px-3">
                                                <span class="font-mono text-indigo-600 dark:text-indigo-400 font-bold">{{ $line->account->code ?? '' }}</span>
                                                <span class="ml-2 text-slate-700 dark:text-slate-300">{{ $line->account->name ?? 'Unknown' }}</span>
                                            </td>
                                            <td class="py-2 px-3 text-right font-semibold">{{ $line->debit ? number_format($line->debit, 2) : '-' }}</td>
                                            <td class="py-2 px-3 text-right font-semibold">{{ $line->credit ? number_format($line->credit, 2) : '-' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-empty-state
                                    icon="book-open"
                                    title="No journal entries yet"
                                    message="Create your first journal entry to record transactions"
                                    :action-url="route('accounts.journal_entries.create')"
                                    action-label="New Journal Entry" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-pagination :paginator="$entries" />
    </div>
</div>

<script>
function toggleLines(entryId) {
    const row = document.getElementById('lines-' + entryId);
    row.classList.toggle('hidden');
}
</script>
@endsection
