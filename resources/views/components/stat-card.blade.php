@props(['label', 'value', 'icon', 'color' => 'indigo', 'trend' => null])

@php
    $colorClasses = match($color) {
        'indigo'  => 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-200 dark:border-indigo-500/20',
        'blue'    => 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-500/20',
        'green'   => 'bg-green-50 dark:bg-green-500/10 text-green-600 dark:text-green-400 border-green-200 dark:border-green-500/20',
        'emerald' => 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20',
        'amber'   => 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-500/20',
        'red'     => 'bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border-red-200 dark:border-red-500/20',
        'purple'  => 'bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-200 dark:border-purple-500/20',
        default   => 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-200 dark:border-indigo-500/20',
    };

    $trendColor = null;
    if ($trend !== null) {
        $trendColor = str_starts_with((string) $trend, '-') ? 'text-red-500' : 'text-green-600 dark:text-green-400';
    }
@endphp

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-5']) }}>
    <div class="flex items-center justify-between mb-3">
        <div class="w-9 h-9 rounded-xl {{ $colorClasses }} border flex items-center justify-center">
            <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
        </div>
        @if($trend !== null)
            <span class="text-[10px] font-bold {{ $trendColor }}">
                <i data-lucide="{{ str_starts_with((string) $trend, '-') ? 'trending-down' : 'trending-up' }}" class="w-3 h-3 inline-block mr-0.5"></i>
                {{ $trend }}
            </span>
        @endif
    </div>
    <div class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">{{ $value }}</div>
    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 font-medium">{{ $label }}</div>
</div>
