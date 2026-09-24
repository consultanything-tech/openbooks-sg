@props(['title', 'subtitle' => null, 'icon' => null])
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
            @if($icon)<i data-lucide="{{ $icon }}" class="w-5 h-5 text-indigo-500"></i>@endif
            {{ $title }}
        </h1>
        @if($subtitle)<p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $subtitle }}</p>@endif
    </div>
    <div class="flex items-center gap-3">{{ $slot }}</div>
</div>
