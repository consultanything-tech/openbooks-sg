@props([
    'icon' => null,
    'title' => null,
    'message' => null,
    'actionUrl' => null,
    'actionLabel' => null,
    'filtered' => null,
    'clearUrl' => null,
])

@php
    // Auto-detect whether server-side filters are active (anything in the query
    // string other than search/pagination/sort), so the caller stays DRY.
    if ($filtered === null) {
        $filtered = collect(request()->query())
            ->except(['q', 'page', 'ob_sort', 'ob_dir'])
            ->contains(fn ($v) => $v !== null && $v !== '');
    }
    // When a filter/search returned zero rows, fully override the caller's
    // "create your first record" copy with a context-aware "nothing matches"
    // state so the two situations are never mixed.
    if ($filtered) {
        $icon = 'search-x';
        $title = 'No matching records';
        $message = 'Nothing matches the current filters or search. Try adjusting them or clearing them to see everything.';
    }
    $icon = $icon ?: 'inbox';
@endphp

<div class="flex flex-col items-center justify-center py-12 px-6 text-center">
    <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
        <i data-lucide="{{ $icon }}" class="w-6 h-6 text-slate-400 dark:text-slate-500" aria-hidden="true"></i>
    </div>
    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">{{ $title }}</h3>
    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mb-4">{{ $message }}</p>
    @if($filtered)
        <a href="{{ $clearUrl ?? url()->current() }}" class="btn btn-secondary">
            <i data-lucide="x" aria-hidden="true"></i>
            Clear filters
        </a>
    @elseif($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="btn btn-primary">
            <i data-lucide="plus" aria-hidden="true"></i>
            {{ $actionLabel }}
        </a>
    @endif
</div>
