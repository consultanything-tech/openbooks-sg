<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <title>Installation Complete — OpenBooks SG</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4.1.11"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/lucide@1.47.0/dist/umd/lucide.min.js"></script>
    <style type="text/tailwindcss">
        @custom-variant dark (&:where(.dark, .dark *));
        @theme {
            --font-sans: 'Inter', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            --color-neon: #2563eb;
            --color-brand: #0f172a;
        }
    </style>
    <script>
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="min-h-full bg-slate-100 dark:bg-[#090d16] text-slate-900 dark:text-slate-100 flex flex-col justify-between selection:bg-indigo-500 selection:text-white">

    <div class="hidden dark:block fixed top-0 left-1/2 -translate-x-1/2 w-[800px] h-[350px] bg-indigo-500/10 rounded-full blur-[140px] pointer-events-none -z-10"></div>

    <!-- Header -->
    <header class="border-b border-slate-200 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/60 backdrop-blur-xl py-4">
        <div class="max-w-4xl mx-auto px-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 border border-indigo-300 dark:border-indigo-500/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold shadow-lg shadow-indigo-500/10">
                    <i data-lucide="coins" class="w-5 h-5"></i>
                </div>
                <h1 class="text-base font-black text-slate-900 dark:text-white">OpenBooks SG</h1>
            </div>
            <span class="text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/30 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span> Live & Installed
            </span>
        </div>
    </header>

    <!-- Content -->
    <main class="flex-1 max-w-2xl w-full mx-auto px-6 py-12 flex flex-col justify-center">
        <div class="bg-white dark:bg-slate-900/90 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 sm:p-10 shadow-2xl backdrop-blur-xl text-center space-y-8">

            <!-- Success Icon -->
            <div class="w-20 h-20 rounded-3xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/40 text-indigo-600 dark:text-indigo-400 mx-auto flex items-center justify-center shadow-2xl shadow-indigo-500/20">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>

            <div>
                <span class="text-indigo-600 dark:text-indigo-400 font-mono text-xs uppercase font-bold tracking-widest">Setup Successful</span>
                <h2 class="text-3xl font-black text-slate-900 dark:text-white mt-1">OpenBooks SG is Ready!</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
                    Your financial database has been migrated and accounting chart & ledger accounts initialized. You are ready to go!
                </p>
            </div>

            <!-- Admin Credentials Card -->
            <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 text-left space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Super Admin Account</span>
                    <span class="text-[10px] font-mono text-indigo-600 dark:text-indigo-400 font-bold uppercase px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/30">Role: ADMIN</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-mono">
                    <div>
                        <span class="text-slate-500 text-[10px] uppercase font-bold block">Login Email</span>
                        <span class="text-slate-900 dark:text-white font-bold text-sm select-all">{{ $installedInfo['admin_email'] ?? 'admin@example.com' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 text-[10px] uppercase font-bold block">Password</span>
                        <span class="text-slate-900 dark:text-white font-bold text-sm select-all">(as set during installation)</span>
                    </div>
                </div>

                <p class="text-[11px] text-slate-500 font-sans italic pt-1 border-t border-slate-200 dark:border-slate-800/50">
                    * You can change your password anytime under Profile Settings inside the Dashboard.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 pt-2">
                <a href="{{ route('dashboard') }}" class="flex-1 py-4 px-6 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-black text-xs uppercase tracking-widest transition-all shadow-xl shadow-indigo-600/20 flex items-center justify-center gap-2">
                    <span>Go to Accounting Dashboard</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>

            <!-- Support Note -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                Need any help or custom feature development? Contact your system administrator for support.
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 dark:border-slate-800/80 py-6 text-center text-xs text-slate-500">
        <p>OpenBooks SG Platform &copy; {{ date('Y') }} OpenBooks SG.</p>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
