@if($activities->isNotEmpty())
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <i data-lucide="history" class="w-4 h-4 text-indigo-500" aria-hidden="true"></i>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">{{ $title }}</h2>
            </div>
            @if($hasMore && \Illuminate\Support\Facades\Route::has('activity_log.index'))
                <a href="{{ route('activity_log.index', ['model_type' => $modelType]) }}"
                   class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                    View full log
                </a>
            @endif
        </div>

        <ol class="px-5 py-4">
            @foreach($activities as $index => $log)
                @php
                    $icon = match ($log->action) {
                        'created' => 'plus-circle',
                        'updated' => 'pencil',
                        'deleted' => 'trash-2',
                        'restored' => 'rotate-ccw',
                        'payment_recorded' => 'banknote',
                        'marked_sent', 'sent' => 'send',
                        'approved' => 'check-circle',
                        'rejected' => 'x-circle',
                        'paid' => 'badge-check',
                        'duplicated' => 'copy',
                        'applied' => 'file-check',
                        'reconciled' => 'git-merge',
                        default => 'circle-dot',
                    };
                    $tone = match ($log->action) {
                        'created', 'approved', 'paid', 'payment_recorded', 'restored' => 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
                        'updated', 'applied', 'duplicated' => 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400',
                        'deleted', 'rejected' => 'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400',
                        'marked_sent', 'sent' => 'bg-violet-50 dark:bg-violet-500/10 text-violet-600 dark:text-violet-400',
                        default => 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400',
                    };
                @endphp
                <li class="relative flex gap-3 pb-4 last:pb-0">
                    @if(! $loop->last)
                        <span class="absolute left-[13px] top-7 bottom-0 w-px bg-slate-200 dark:bg-slate-800" aria-hidden="true"></span>
                    @endif
                    <span class="relative z-10 w-7 h-7 rounded-full flex items-center justify-center shrink-0 {{ $tone }}">
                        <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0 pt-0.5">
                        <p class="text-xs font-medium text-slate-900 dark:text-white leading-relaxed">{{ $log->description }}</p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                            <span class="font-semibold capitalize">{{ str_replace('_', ' ', $log->action) }}</span>
                            &middot; {{ $log->user->name ?? 'System' }}
                            &middot; <time datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->diffForHumans() }}</time>
                        </p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
@endif
