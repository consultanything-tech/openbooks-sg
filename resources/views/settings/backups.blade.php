@extends('layouts.app')

@section('title', 'Backup & Restore')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('settings.index') }}" class="inline-flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-semibold mb-2 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Settings
            </a>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Backup & Restore</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Create, download, and manage database backups for disaster recovery</p>
        </div>
        <form action="{{ route('settings.backups.create') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i data-lucide="download" aria-hidden="true"></i> Run Backup
            </button>
        </form>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="flex items-center gap-2 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
            <span class="text-xs text-emerald-700 dark:text-emerald-300 font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-2 p-3 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
            <i data-lucide="x-circle" class="w-4 h-4 text-red-600 dark:text-red-400"></i>
            <span class="text-xs text-red-700 dark:text-red-300 font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Backups Table -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
        @if(count($backups) > 0)
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                    <i data-lucide="history" class="w-4 h-4 text-indigo-500 mr-1.5"></i>
                    {{ count($backups) }} Backup{{ count($backups) !== 1 ? 's' : '' }} Available
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="py-3 px-6">Filename</th>
                            <th class="py-3 px-6">Date</th>
                            <th class="py-3 px-6">Size</th>
                            <th class="py-3 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($backups as $backup)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition">
                                <td class="py-3 px-6 font-mono text-slate-700 dark:text-slate-300">{{ $backup['filename'] }}</td>
                                <td class="py-3 px-6 text-slate-500 dark:text-slate-400">{{ \Carbon\Carbon::parse($backup['date'])->format('M d, Y H:i') }}</td>
                                <td class="py-3 px-6">
                                    <span class="px-2 py-0.5 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/30 text-[11px] font-bold">{{ $backup['size'] }}</span>
                                </td>
                                <td class="py-3 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('settings.backups.download', $backup['filename']) }}" class="btn-icon" title="Download backup" aria-label="Download backup">
                                            <i data-lucide="download" aria-hidden="true"></i>
                                        </a>
                                        <form action="{{ route('settings.backups.destroy', $backup['filename']) }}" method="POST" onsubmit="return confirm('Delete this backup permanently?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Delete backup" aria-label="Delete backup">
                                                <i data-lucide="trash-2" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <!-- Empty State -->
            <div class="px-6 py-16 text-center">
                <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                    <i data-lucide="database" class="w-6 h-6 text-slate-400 dark:text-slate-500"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">No Backups Found</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Create your first database backup to get started.</p>
                <form action="{{ route('settings.backups.create') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="plus" aria-hidden="true"></i> New Backup
                    </button>
                </form>
            </div>
        @endif
    </div>

    <!-- Automated Backup Info -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
        <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                <i data-lucide="info" class="w-4 h-4"></i>
            </div>
            <div class="text-xs text-slate-600 dark:text-slate-400 space-y-2">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Automated Backups</h3>
                <p>Backups are automatically created daily at 02:00 via the Laravel task scheduler. The system retains the last 10 backups and automatically removes older ones.</p>
                <p>To ensure scheduled backups run, add this cron entry on your server:</p>
                <pre class="p-3 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-mono text-[11px] text-slate-700 dark:text-slate-300 overflow-x-auto">* * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1</pre>
                <p>You can also create backups manually via the command line:</p>
                <pre class="p-3 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-mono text-[11px] text-slate-700 dark:text-slate-300 overflow-x-auto">php artisan backup:run          # Create a backup
php artisan backup:list         # List all backups
php artisan backup:restore FILE # Restore from backup</pre>
            </div>
        </div>
    </div>
</div>
@endsection
