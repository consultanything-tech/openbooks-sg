<!DOCTYPE html>
<html lang="en" class="h-full light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <title>Sign In - OpenBooks SG</title>

    <script>
        // Immediate theme detection (default: light) — matches main layout
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
        } else {
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style type="text/tailwindcss">
        @custom-variant dark (&:where(.dark, .dark *));
        @theme {
            --font-sans: 'Inter', sans-serif;
        }
    </style>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        /* WCAG 2.1 AA: Visible focus indicator (2.4.7) */
        :focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 2px;
            border-radius: 4px;
        }
        :focus:not(:focus-visible) {
            outline: none;
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body class="h-full bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-sm">

            {{-- Card --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm p-8">

                {{-- Logo / Brand --}}
                <div class="flex flex-col items-center mb-8">
                    <img src="{{ asset('logo/mark.svg') }}" alt="OpenBooks SG" class="w-14 h-14 rounded-2xl mb-3 shadow-sm">
                    <h1 class="text-lg font-bold text-slate-900 dark:text-white">OpenBooks SG</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-500 mt-0.5">Open Source Accounting</p>
                </div>

                {{-- Error alert --}}
                @if($errors->any())
                    <div role="alert" aria-live="assertive" class="mb-5 p-3 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-700 dark:text-red-400 text-xs flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                {{-- Form --}}
                <form action="{{ route('login.post') }}" method="POST" class="space-y-4" id="login-form">
                    @csrf
                    {{-- Where to return after a session-expiry re-login (populated from localStorage) --}}
                    <input type="hidden" name="intended" id="login-intended" value="">

                    {{-- Email --}}
                    <div>
                        <label for="login-email" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Email</label>
                        <input type="email" name="email" id="login-email" value="{{ old('email', 'admin@openbooks.sg') }}" required aria-required="true"
                            @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                            placeholder="you@example.com" autocomplete="email">
                        @error('email')<p id="email-error" role="alert" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="login-password" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="login-password" required aria-required="true"
                                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                                class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 pr-10 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                                placeholder="••••••••" autocomplete="current-password">
                            <button type="button" onclick="var i=document.getElementById('login-password'); var s=i.type==='password'; i.type=s?'text':'password'; this.querySelector('.eye-on').classList.toggle('hidden',s); this.querySelector('.eye-off').classList.toggle('hidden',!s); this.setAttribute('aria-pressed',String(s)); this.setAttribute('aria-label', s?'Hide password':'Show password');" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition" aria-label="Show password" aria-pressed="false">
                                <i data-lucide="eye" class="eye-on w-4 h-4" aria-hidden="true"></i>
                                <i data-lucide="eye-off" class="eye-off w-4 h-4 hidden" aria-hidden="true"></i>
                            </button>
                        </div>
                        @error('password')<p id="password-error" role="alert" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    {{-- Remember me --}}
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="remember" id="remember"
                            class="w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-indigo-600 focus:ring-0 focus:ring-offset-0">
                        <label for="remember" class="text-xs text-slate-500 dark:text-slate-400 cursor-pointer select-none">Remember me</label>
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                        class="btn btn-primary w-full justify-center">
                        Sign In
                    </button>
                </form>
            </div>

            {{-- Footer --}}
            <p class="text-center text-xs text-slate-500 dark:text-slate-500 mt-6">
                OpenBooks SG &mdash; Open Source
            </p>

            {{-- Theme toggle --}}
            <div class="flex justify-center mt-4">
                <button onclick="toggleTheme()" id="themeToggle" aria-label="Toggle light and dark mode"
                    class="text-xs text-slate-500 dark:text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition flex items-center gap-1.5">
                    <svg id="themeIconSun" class="w-3.5 h-3.5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <svg id="themeIconMoon" class="w-3.5 h-3.5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <span id="themeLabel"></span>
                </button>
            </div>

        </div>
    </div>

    <script>
        function updateThemeUI() {
            const isDark = document.documentElement.classList.contains('dark');
            document.getElementById('themeIconSun').classList.toggle('hidden', !isDark);
            document.getElementById('themeIconMoon').classList.toggle('hidden', isDark);
            document.getElementById('themeLabel').textContent = isDark ? 'Light mode' : 'Dark mode';
        }
        function toggleTheme() {
            const isDark = document.documentElement.classList.contains('dark');
            if (isDark) {
                document.documentElement.classList.remove('dark');
                document.documentElement.classList.add('light');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
                localStorage.setItem('theme', 'dark');
            }
            updateThemeUI();
        }
        updateThemeUI();
    </script>
    <script>
        // Pre-fill the post-login destination from the last page the user was on
        // before their session lapsed. Only same-origin relative paths are honored.
        (function () {
            var f = document.getElementById('login-intended');
            if (!f) return;
            try {
                var p = localStorage.getItem('ob-return-to');
                if (p && p.charAt(0) === '/' && p.charAt(1) !== '/') { f.value = p; }
            } catch (e) {}
        })();
    </script>
    <script>lucide.createIcons();</script>
</body>
</html>
