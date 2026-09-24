@props(['status'])

@php
    $colorMap = [
        'draft'     => 'slate',
        'sent'      => 'blue',
        'partial'   => 'amber',
        'paid'      => 'green',
        'overdue'   => 'red',
        'cancelled' => 'slate',
        'submitted' => 'blue',
        'approved'  => 'emerald',
        'rejected'  => 'red',
        'converted' => 'purple',
        'accepted'  => 'emerald',
        'declined'  => 'red',
        'expired'   => 'slate',
    ];

    $color = $colorMap[strtolower($status)] ?? 'slate';

    $classes = match($color) {
        'slate'   => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-700/30 dark:text-slate-300 dark:border-slate-600',
        'blue'    => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:border-blue-500/30',
        'amber'   => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:border-amber-500/30',
        'green'   => 'bg-green-50 text-green-700 border-green-200 dark:bg-green-500/10 dark:text-green-300 dark:border-green-500/30',
        'red'     => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-300 dark:border-red-500/30',
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/30',
        'purple'  => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-500/10 dark:text-purple-300 dark:border-purple-500/30',
        default   => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-700/30 dark:text-slate-300 dark:border-slate-600',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] uppercase font-bold border {$classes}"]) }}>
    {{ $status }}
</span>
