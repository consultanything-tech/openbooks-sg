@props(['items' => []])

@if(count($items))
    <nav aria-label="Breadcrumb" class="mb-3">
        <ol class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 flex-wrap">
            @foreach($items as $crumb)
                @php
                    $isLast = $loop->last;
                    $url = $crumb['url'] ?? null;
                    $label = $crumb['label'] ?? '';
                @endphp
                <li class="flex items-center gap-1.5">
                    @if(!$loop->first)
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-300 dark:text-slate-600 shrink-0" aria-hidden="true"></i>
                    @endif
                    @if($url && !$isLast)
                        <a href="{{ $url }}" class="hover:text-slate-800 dark:hover:text-slate-200 transition">{{ $label }}</a>
                    @else
                        <span class="font-semibold text-slate-800 dark:text-slate-200 truncate max-w-[16rem]"
                            @if($isLast) aria-current="page" @endif>{{ $label }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
