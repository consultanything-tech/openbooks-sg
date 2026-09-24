@props(['action'])

<form method="GET" action="{{ $action }}" {{ $attributes->merge(['class' => 'flex flex-wrap items-end gap-3']) }}>
    {{ $slot }}
    <div class="flex items-center gap-2">
        <button type="submit" class="btn btn-primary">
            <i data-lucide="filter" aria-hidden="true"></i> Filter
        </button>
        <a href="{{ $action }}" class="text-xs text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 font-medium transition px-2 py-2">
            Clear
        </a>
    </div>
</form>
