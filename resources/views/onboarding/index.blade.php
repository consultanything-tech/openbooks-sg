@extends('layouts.app')

@section('title', 'Getting Started')

@section('content')
<div class="max-w-2xl mx-auto py-8">

    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="w-16 h-16 rounded-2xl bg-indigo-100 dark:bg-indigo-500/15 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="rocket" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Welcome to OpenBooks SG</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Complete these steps to set up your Singapore accounting system</p>
    </div>

    {{-- Progress Bar --}}
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 mb-6 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Setup Progress</span>
            <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ $completed }}/{{ $total }} complete</span>
        </div>
        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2.5">
            <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-500 ease-out" style="width: {{ $total > 0 ? round(($completed / $total) * 100) : 0 }}%"></div>
        </div>
        @if($completed === $total)
            <div class="mt-4 flex items-center gap-2 text-emerald-600 dark:text-emerald-400">
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                <span class="text-xs font-semibold">All set! You can now skip to your dashboard.</span>
            </div>
        @endif
    </div>

    {{-- Checklist Card --}}
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        @foreach($steps as $index => $step)
            <div class="flex items-center gap-4 px-6 py-4 {{ $index < count($steps) - 1 ? 'border-b border-slate-100 dark:border-slate-800' : '' }} hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                {{-- Status Icon --}}
                @if($step['done'])
                    <div class="w-9 h-9 rounded-full bg-emerald-100 dark:bg-emerald-500/15 flex items-center justify-center shrink-0">
                        <i data-lucide="check" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                    </div>
                @else
                    <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-600 flex items-center justify-center shrink-0">
                        <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500">{{ $index + 1 }}</span>
                    </div>
                @endif

                {{-- Step Content --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold {{ $step['done'] ? 'text-slate-400 dark:text-slate-500 line-through' : 'text-slate-900 dark:text-white' }}">
                        {{ $step['label'] }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $step['description'] }}</p>
                </div>

                {{-- Action Link --}}
                @if(!$step['done'])
                    <a href="{{ $step['link'] }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition shrink-0">
                        <i data-lucide="{{ $step['icon'] }}" class="w-3.5 h-3.5"></i>
                        Set up
                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </a>
                @else
                    <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 px-2.5 py-1 rounded-full shrink-0">Done</span>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Skip Button --}}
    <div class="text-center mt-8">
        <form action="{{ route('onboarding.dismiss') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-ghost">
                <i data-lucide="forward" aria-hidden="true"></i>
                Skip onboarding &mdash; go to dashboard
            </button>
        </form>
    </div>

    {{-- Help Footer --}}
    <div class="text-center mt-6">
        <p class="text-[11px] text-slate-400 dark:text-slate-500">
            <i data-lucide="info" class="w-4 h-4 mr-1"></i>
            You can revisit this checklist anytime from the sidebar.
        </p>
    </div>
</div>
@endsection
