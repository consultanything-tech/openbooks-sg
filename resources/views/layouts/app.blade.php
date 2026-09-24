<!DOCTYPE html>
<html lang="en" class="h-full light">
<head>
    @include('partials.head')
</head>
<body class="h-full antialiased flex overflow-hidden">
    {{-- Skip to main content link (WCAG 2.1 AA - 2.4.1 Bypass Blocks) --}}
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-[9999] focus:px-4 focus:py-2 focus:bg-indigo-600 focus:text-white focus:rounded-lg focus:outline-none">
      Skip to main content
    </a>

    @include('partials.sidebar')

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        @include('partials.header')
        @include('partials.flash-messages')

        <main id="main-content" class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </main>
    </div>

    {{-- Toast notifications (flash messages render here) --}}
    <div id="obToasts" aria-label="Notifications"></div>

    {{-- Back to top --}}
    <button type="button" id="backToTop" aria-label="Back to top" title="Back to top">
        <i data-lucide="arrow-up" class="w-4 h-4" aria-hidden="true"></i>
    </button>

    {{-- Global confirmation dialog (replaces native confirm()) --}}
    <dialog id="obConfirmDialog" aria-labelledby="obConfirmTitle"
        class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl p-0 w-[calc(100%-2rem)] max-w-sm">
        <div class="p-5">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-500/10 flex items-center justify-center shrink-0">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600 dark:text-rose-400" aria-hidden="true"></i>
                </div>
                <div class="min-w-0">
                    <h2 id="obConfirmTitle" class="text-sm font-bold text-slate-900 dark:text-white">Are you sure?</h2>
                    <p id="obConfirmMessage" class="text-xs text-slate-500 dark:text-slate-400 mt-1"></p>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" id="obConfirmCancel" class="btn btn-ghost">Cancel</button>
                <button type="button" id="obConfirmOk" class="btn btn-danger">Confirm</button>
            </div>
        </div>
    </dialog>

    {{-- Screen reader live region for dynamic announcements --}}
    <div id="sr-announcements" aria-live="polite" aria-atomic="true" class="sr-only"></div>

    @include('partials.scripts')
    @include('partials.ai-assistant')
    @include('partials.keyboard-shortcuts')
    @include('partials.command-palette')
    @auth
        @include('partials.session-guard')
    @endauth
</body>
</html>
