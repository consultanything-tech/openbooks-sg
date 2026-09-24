@extends('layouts.app')

@section('title', 'Activity Log')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Activity Log</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Track who did what and when across all entities</p>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('activity_log.index') }}" class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Action</label>
                <select name="action" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                    <option value="">All Actions</option>
                    @foreach($actions as $a)
                        <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $a)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Entity Type</label>
                <select name="model_type" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                    <option value="">All Types</option>
                    @foreach($modelTypes as $mt)
                        <option value="{{ $mt }}" {{ request('model_type') === $mt ? 'selected' : '' }}>{{ preg_replace('/([a-z])([A-Z])/', '$1 $2', $mt) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">User</label>
                <select name="user_id" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                    <option value="">All Users</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description..." aria-label="Search activity logs" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 placeholder-slate-400 dark:placeholder-slate-500">
            </div>
        </div>
        <div class="flex items-center gap-2 mt-3">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="filter" aria-hidden="true"></i> Apply Filters
            </button>
            <a href="{{ route('activity_log.index') }}" class="btn btn-secondary">
                Clear
            </a>
        </div>
    </form>

    <!-- Activity Log Table -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs responsive-table">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Date / Time</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">User</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Action</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Description</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td data-label="Date / Time" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                {{ $log->created_at->format('d M Y, h:i A') }}
                            </td>
                            <td data-label="User" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">
                                {{ $log->user->name ?? 'System' }}
                            </td>
                            <td data-label="Action" class="px-4 py-3 text-xs">
                                @php
                                    $badgeClasses = match($log->action) {
                                        'created' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
                                        'updated' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400',
                                        'deleted' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
                                        'restored' => 'bg-teal-100 text-teal-700 dark:bg-teal-500/15 dark:text-teal-400',
                                        'payment_recorded' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
                                        'marked_sent' => 'bg-purple-100 text-purple-700 dark:bg-purple-500/15 dark:text-purple-400',
                                        'duplicated' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
                                        'reconciled' => 'bg-teal-100 text-teal-700 dark:bg-teal-500/15 dark:text-teal-400',
                                        'applied' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-400',
                                        'login', 'logout' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
                                        default => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $badgeClasses }}">
                                    {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                                </span>
                            </td>
                            <td data-label="Description" class="px-4 py-3 text-xs text-slate-900 dark:text-white font-medium">
                                {{ $log->description }}
                            </td>
                            <td data-label="IP Address" class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400 font-mono">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state icon="history" title="No activity logs found." message="Try adjusting your filters or date range." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <x-table-pagination :paginator="$logs" />
</div>
@endsection
