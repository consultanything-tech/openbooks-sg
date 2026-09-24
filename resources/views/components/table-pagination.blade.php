@props(['paginator'])

@php
    $total = $paginator->total();
    $from = $paginator->firstItem() ?? 0;
    $to = $paginator->lastItem() ?? 0;
@endphp

@if($total > 0)
    <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
        <p class="text-xs text-slate-500 dark:text-slate-400" aria-live="polite">
            Showing
            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $from }}</span>–<span
                class="font-semibold text-slate-700 dark:text-slate-200">{{ $to }}</span>
            of
            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ number_format($total) }}</span>
        </p>
        @if($paginator->hasPages())
            <div class="ob-paginator">
                {{ $paginator->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endif
