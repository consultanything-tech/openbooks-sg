<!DOCTYPE html>
<html lang="en" class="h-full">
<!-- Dark mode auto-detect -->
<script>if(window.matchMedia('(prefers-color-scheme: dark)').matches)document.documentElement.classList.add('dark');</script>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <title>OpenBooks SG - Installation Guide</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style type="text/tailwindcss">
        @custom-variant dark (&:where(.dark, .dark *));
        @theme {
            --font-sans: 'Inter', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            --color-neon: #2563eb;
            --color-brand: #0f172a;
        }
    </style>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-border {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.4), rgba(255, 255, 255, 0.05), rgba(99, 102, 241, 0.2));
        }
    </style>
</head>
<body class="min-h-full bg-slate-100 dark:bg-[#090d16] text-slate-900 dark:text-slate-100 flex flex-col justify-between selection:bg-indigo-500 selection:text-white">

    <!-- Top Glow Background (dark only) -->
    <div class="hidden dark:block fixed top-0 left-1/2 -translate-x-1/2 w-[800px] h-[350px] bg-indigo-500/10 rounded-full blur-[140px] pointer-events-none -z-10"></div>
    <div class="hidden dark:block fixed bottom-0 right-0 w-[500px] h-[300px] bg-blue-500/10 rounded-full blur-[120px] pointer-events-none -z-10"></div>

    <!-- Top Header -->
    <header class="border-b border-slate-200 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/60 backdrop-blur-xl sticky top-0 z-40">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900 border border-indigo-300 dark:border-indigo-500/30 flex items-center justify-center shadow-lg shadow-indigo-500/10">
                    <span class="text-indigo-600 dark:text-indigo-400 font-black text-xl">O</span>
                </div>
                <div>
                    <h1 class="text-base font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        OpenBooks SG <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/30">Installer</span>
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Open Source Accounting for Singapore</p>
                </div>
            </div>

        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-4xl w-full mx-auto px-4 sm:px-6 py-8 md:py-12">

        <!-- Wizard Card -->
        <div class="bg-white dark:bg-slate-900/90 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden backdrop-blur-xl">

            <!-- Step Indicator Bar -->
            <div class="border-b border-slate-200 dark:border-slate-800/80 bg-slate-50 dark:bg-slate-950/40 p-4 sm:p-6">
                <div class="grid grid-cols-3 gap-2 sm:gap-3 text-center">
                    <!-- Step 1: Requirements -->
                    <div id="tab-step-0" class="step-tab flex flex-col sm:flex-row items-center justify-center gap-2 p-2.5 sm:p-3 rounded-2xl bg-indigo-50 dark:bg-slate-800/80 border border-indigo-200 dark:border-indigo-500/40 text-indigo-600 dark:text-indigo-400 transition-all">
                        <span class="w-6 h-6 rounded-full bg-indigo-500 text-white text-xs font-black flex items-center justify-center shrink-0">1</span>
                        <span class="text-xs font-bold text-slate-900 dark:text-white truncate">Requirements</span>
                    </div>

                    <!-- Step 2: Database & Details -->
                    <div id="tab-step-1" class="step-tab flex flex-col sm:flex-row items-center justify-center gap-2 p-2.5 sm:p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 transition-all">
                        <span class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs font-bold flex items-center justify-center shrink-0">2</span>
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 truncate">Database & Details</span>
                    </div>

                    <!-- Step 3: Finished -->
                    <div id="tab-step-2" class="step-tab flex flex-col sm:flex-row items-center justify-center gap-2 p-2.5 sm:p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 transition-all">
                        <span class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs font-bold flex items-center justify-center shrink-0">3</span>
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 truncate">Finished</span>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════
                 STEP 1: SYSTEM REQUIREMENTS CHECK
            ══════════════════════════════════════════ -->
            <div id="step-0-content" class="step-content p-6 sm:p-10 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800/80 pb-4">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <span>1. System Requirements & Directory Permissions</span>
                            @if($allPassed)
                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[10px] uppercase tracking-wider font-bold">All Passed</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30 text-[10px] uppercase tracking-wider font-bold">Action Needed</span>
                            @endif
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Verify that your hosting environment meets the required PHP version, PHP extensions, and writable folders.
                        </p>
                    </div>
                    <a href="{{ url('/install') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold shrink-0 border border-slate-300 dark:border-slate-700 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Re-check
                    </a>
                </div>

                <!-- PHP Version Box -->
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/30 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-xs">
                            PHP
                        </div>
                        <div>
                            <span class="text-xs font-bold text-slate-900 dark:text-white block">PHP Version</span>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400">Required: PHP &gt;= 8.2 &bull; Current: <strong class="font-mono text-slate-800 dark:text-slate-200">{{ $requirements['php_version'] }}</strong></span>
                        </div>
                    </div>
                    @if($requirements['list']['PHP >= 8.2'])
                        <span class="px-3 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-bold text-xs flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Supported
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 font-bold text-xs flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Upgrade to PHP 8.2+
                        </span>
                    @endif
                </div>

                <!-- PHP Extensions Grid -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Required PHP Extensions</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                        @foreach($requirements['list'] as $reqName => $passed)
                            @if($reqName !== 'PHP >= 8.2')
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border {{ $passed ? 'border-slate-200 dark:border-slate-800' : 'border-red-300 dark:border-red-500/40 bg-red-50 dark:bg-red-950/20' }} flex items-center justify-between text-xs">
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $reqName }}</span>
                                @if($passed)
                                    <span class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xs shrink-0">✓</span>
                                @else
                                    <span class="w-5 h-5 rounded-full bg-red-500/20 text-red-400 flex items-center justify-center font-bold text-xs shrink-0">✕</span>
                                @endif
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Directory Permissions Grid -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Directory Permissions</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        @foreach($permissions['list'] as $dir => $isWritable)
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border {{ $isWritable ? 'border-slate-200 dark:border-slate-800' : 'border-red-300 dark:border-red-500/40 bg-red-50 dark:bg-red-950/20' }} flex items-center justify-between text-xs">
                                <span class="font-mono text-slate-700 dark:text-slate-300">{{ $dir }}</span>
                                @if($isWritable)
                                    <span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 text-[10px] font-bold border border-emerald-500/30">Writable</span>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-red-500/10 text-red-400 text-[10px] font-bold border border-red-500/30">Not Writable</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Step 0 Action Buttons -->
                <div class="pt-4">
                    @if($allPassed)
                        <button type="button" id="btn-next-step-1" class="w-full py-4 px-6 rounded-2xl bg-indigo-500 hover:bg-indigo-400 text-black font-black uppercase tracking-widest text-xs sm:text-sm flex items-center justify-center gap-2 shadow-xl shadow-indigo-500/20 transition-all cursor-pointer">
                            <span>System Requirements Verified &bull; Continue to Step 2</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    @else
                        <div class="space-y-3">
                            <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs">
                                Some requirements or permissions are not fulfilled. Please fix permissions or enable extensions in your hosting control panel (cPanel / aaPanel / Hostinger).
                            </div>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <a href="{{ url('/install') }}" class="sm:w-1/2 py-3 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-900 dark:text-white font-bold text-xs uppercase tracking-wider text-center border border-slate-300 dark:border-slate-700">
                                    Re-check System
                                </a>
                                <button type="button" id="btn-next-step-1" class="sm:w-1/2 py-3 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-black text-xs uppercase tracking-wider flex items-center justify-center gap-2 cursor-pointer">
                                    <span>Continue Anyway</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- ══════════════════════════════════════════
                 STEP 2: YOUR DETAILS & DATABASE CONFIGURATION
            ══════════════════════════════════════════ -->
            <div id="step-1-content" class="step-content hidden p-6 sm:p-10 space-y-6">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">2. Your Details & Database Configuration</h2>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Enter your details and MySQL database credentials. If the database does not exist, the installer will automatically create it for you.
                    </p>
                </div>

                <div id="step-1-alert" class="hidden p-4 rounded-xl text-xs font-medium border"></div>

                <!-- Your Details Section -->
                <div class="space-y-4 pb-4 border-b border-slate-200 dark:border-slate-800/80">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Your Details</h3>
                    </div>

                    <!-- Full Name -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                            Full Name <span class="text-indigo-400">*</span>
                        </label>
                        <input type="text" name="name" id="name" required placeholder="e.g. John Doe" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-4 py-3 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium">
                    </div>

                    <!-- Phone with Country Code Dropdown -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                            Phone Number <span class="text-indigo-400">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                            <div class="sm:col-span-5">
                                <select name="phone_country_code" id="phone_country_code" required class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-3 text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 font-medium">
                                    @foreach($countries as $c)
                                        <option value="{{ $c['code'] }}" {{ $c['code'] === '+65' ? 'selected' : '' }}>
                                            {{ $c['flag'] }} {{ $c['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-7">
                                <input type="tel" name="phone_number" id="phone_number" required placeholder="e.g. 9854209873" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-4 py-3 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all font-mono font-medium">
                            </div>
                        </div>
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                            Email Address <span class="text-indigo-400">*</span>
                        </label>
                        <input type="email" name="email" id="email" required placeholder="e.g. yourname@example.com" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-4 py-3 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium">
                    </div>

                    <!-- Optional Fields Row: Profession, Country, City -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                                Profession <span class="text-slate-600 font-normal lowercase">(optional)</span>
                            </label>
                            <input type="text" name="profession" id="profession" placeholder="e.g. Accounting Broker" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:border-slate-500 font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                                Country <span class="text-slate-600 font-normal lowercase">(optional)</span>
                            </label>
                            <input type="text" name="country" id="country" placeholder="e.g. Singapore" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:border-slate-500 font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                                City <span class="text-slate-600 font-normal lowercase">(optional)</span>
                            </label>
                            <input type="text" name="city" id="city" placeholder="e.g. Singapore" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:border-slate-500 font-medium">
                        </div>
                    </div>
                </div>

                <div id="step-2-alert" class="hidden p-4 rounded-xl text-xs font-medium border"></div>

                <form id="database-form" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                        <!-- Host -->
                        <div class="sm:col-span-8">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                Database Host <span class="text-indigo-400">*</span>
                            </label>
                            <input type="text" name="db_host" id="db_host" required value="127.0.0.1" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-4 py-3 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 font-mono font-medium">
                        </div>

                        <!-- Port -->
                        <div class="sm:col-span-4">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                Port <span class="text-indigo-400">*</span>
                            </label>
                            <input type="number" name="db_port" id="db_port" required value="3306" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-4 py-3 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 font-mono font-medium">
                        </div>
                    </div>

                    <!-- Database Name -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                            Database Name <span class="text-indigo-400">*</span>
                        </label>
                        <input type="text" name="db_name" id="db_name" required value="accounting" placeholder="accounting" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-4 py-3 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 font-mono font-bold">
                        <p class="text-[11px] text-slate-500 mt-1">If this database does not exist in MySQL, it will be created automatically.</p>
                    </div>

                    <!-- Username & Password -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                Database Username <span class="text-indigo-400">*</span>
                            </label>
                            <input type="text" name="db_user" id="db_user" required value="root" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-4 py-3 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 font-mono font-medium">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                Database Password
                            </label>
                            <input type="password" name="db_pass" id="db_pass" placeholder="(Leave blank if XAMPP root without password)" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-4 py-3 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 font-mono font-medium">
                        </div>
                    </div>

                    <!-- App URL -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                            Application URL
                        </label>
                        <input type="text" name="app_url" id="app_url" value="{{ url('/') }}" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-4 py-3 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 font-mono text-xs font-medium">
                    </div>

                    <!-- Company & Super Admin Credentials -->
                    <div class="pt-4 border-t border-slate-200 dark:border-slate-800/80 space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Company &amp; Super Admin Credentials</h3>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                Company / Business Name
                            </label>
                            <input type="text" name="company_name" id="company_name" placeholder="e.g. My Business Enterprise" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-4 py-3 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:border-indigo-500 font-medium">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                    Admin Login Email
                                </label>
                                <input type="email" name="admin_email" id="admin_email" placeholder="admin@example.com" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-4 py-3 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:border-indigo-500 font-medium">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                    Admin Password <span class="text-indigo-400">*</span>
                                </label>
                                <input type="password" name="admin_password" id="admin_password" placeholder="Enter a strong password (min 8 characters)" required minlength="8" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-4 py-3 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:border-indigo-500 font-mono font-medium">
                            </div>
                        </div>
                    </div>

                    <!-- Buttons Row -->
                    <div class="pt-6 flex flex-col sm:flex-row gap-3">
                        <button type="button" id="btn-back-step-0" class="sm:w-1/4 py-3.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs uppercase tracking-wider transition-all border border-slate-300 dark:border-slate-700 flex items-center justify-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            <span>Back</span>
                        </button>

                        <button type="button" id="btn-test-db" class="sm:w-1/3 py-3.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-900 dark:text-white font-bold text-xs uppercase tracking-wider transition-all border border-slate-300 dark:border-slate-700 flex items-center justify-center gap-2 cursor-pointer">
                            <span>Test Connection</span>
                        </button>

                        <button type="submit" id="btn-run-install" class="flex-1 py-3.5 px-6 rounded-xl bg-indigo-500 hover:bg-indigo-400 text-black font-black uppercase tracking-widest text-xs flex items-center justify-center gap-2 shadow-xl shadow-indigo-500/20 transition-all cursor-pointer">
                            <span>Start Installation & Migrate</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ══════════════════════════════════════════
                 STEP 3: INSTALLATION PROGRESS
            ══════════════════════════════════════════ -->
            <div id="step-2-loading" class="step-content hidden p-10 sm:p-16 text-center space-y-6">
                <div class="w-20 h-20 rounded-3xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/30 mx-auto flex items-center justify-center relative">
                    <div class="w-12 h-12 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                </div>

                <div>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white">Installing OpenBooks SG...</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto">
                        Executing database migrations, generating application keys, and seeding financial charts &amp; ledger accounts. Please wait.
                    </p>
                </div>

                <div class="w-full max-w-sm mx-auto bg-slate-100 dark:bg-slate-950 h-2 rounded-full overflow-hidden border border-slate-200 dark:border-slate-800">
                    <div class="bg-indigo-500 h-full w-2/3 animate-pulse"></div>
                </div>
            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 dark:border-slate-800/80 py-6 text-center text-xs text-slate-500">
        <p>OpenBooks SG Accounting Platform &copy; {{ date('Y') }} OpenBooks. All rights reserved.</p>
    </footer>

    <!-- Installer JavaScript Logic -->
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const step0Content = document.getElementById('step-0-content');
        const step1Content = document.getElementById('step-1-content');
        const step2Loading = document.getElementById('step-2-loading');

        const tabStep0 = document.getElementById('tab-step-0');
        const tabStep1 = document.getElementById('tab-step-1');
        const tabStep2 = document.getElementById('tab-step-2');

        const tabs = [tabStep0, tabStep1, tabStep2];

        function setTab(activeIndex) {
            tabs.forEach((tab, idx) => {
                if (!tab) return;
                const badge = tab.querySelector('span:first-child');
                const label = tab.querySelector('span:last-child');
                if (idx === activeIndex) {
                    tab.className = 'step-tab flex flex-col sm:flex-row items-center justify-center gap-2 p-2.5 sm:p-3 rounded-2xl bg-indigo-50 dark:bg-slate-800/80 border border-indigo-200 dark:border-indigo-500/40 text-indigo-600 dark:text-indigo-400 transition-all';
                    if (badge) badge.className = 'w-6 h-6 rounded-full bg-indigo-500 text-white text-xs font-black flex items-center justify-center shrink-0';
                    if (label) label.className = 'text-xs font-bold text-slate-900 dark:text-white truncate';
                } else if (idx < activeIndex) {
                    tab.className = 'step-tab flex flex-col sm:flex-row items-center justify-center gap-2 p-2.5 sm:p-3 rounded-2xl bg-emerald-50 dark:bg-slate-950/60 border border-emerald-200 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 transition-all';
                    if (badge) badge.className = 'w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center justify-center shrink-0';
                    if (label) label.className = 'text-xs font-bold text-slate-700 dark:text-slate-300 truncate';
                } else {
                    tab.className = 'step-tab flex flex-col sm:flex-row items-center justify-center gap-2 p-2.5 sm:p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 transition-all';
                    if (badge) badge.className = 'w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs font-bold flex items-center justify-center shrink-0';
                    if (label) label.className = 'text-xs font-bold text-slate-500 dark:text-slate-400 truncate';
                }
            });
        }

        const step1Alert = document.getElementById('step-1-alert');
        const step2Alert = document.getElementById('step-2-alert');

        let verifiedCustomer = null;

        function showAlert(elem, type, message) {
            const isDark = document.documentElement.classList.contains('dark');
            elem.classList.remove('hidden',
                'bg-red-50', 'border-red-200', 'text-red-700',
                'bg-red-950/60', 'border-red-500/40', 'text-red-300',
                'bg-emerald-50', 'border-emerald-200', 'text-emerald-700',
                'bg-emerald-950/60', 'border-emerald-500/40', 'text-emerald-300');
            if (type === 'error') {
                elem.classList.add(isDark ? 'bg-red-950/60' : 'bg-red-50', isDark ? 'border-red-500/40' : 'border-red-200', isDark ? 'text-red-300' : 'text-red-700');
            } else {
                elem.classList.add(isDark ? 'bg-emerald-950/60' : 'bg-emerald-50', isDark ? 'border-emerald-500/40' : 'border-emerald-200', isDark ? 'text-emerald-300' : 'text-emerald-700');
            }
            elem.innerHTML = message;
        }

        // STEP 1 NAVIGATION: Continue from Requirements to Database & Details
        const btnNextStep1 = document.getElementById('btn-next-step-1');
        if (btnNextStep1) {
            btnNextStep1.addEventListener('click', () => {
                step0Content.classList.add('hidden');
                step1Content.classList.remove('hidden');
                setTab(1);
            });
        }

        // STEP 2 NAVIGATION: Back to Requirements
        const btnBackStep0 = document.getElementById('btn-back-step-0');
        if (btnBackStep0) {
            btnBackStep0.addEventListener('click', () => {
                step1Content.classList.add('hidden');
                step0Content.classList.remove('hidden');
                setTab(0);
            });
        }

        // Test Database Connection
        document.getElementById('btn-test-db').addEventListener('click', async () => {
            const btn = document.getElementById('btn-test-db');
            btn.disabled = true;
            btn.innerText = 'Testing...';

            const payload = {
                db_host: document.getElementById('db_host').value,
                db_port: document.getElementById('db_port').value,
                db_name: document.getElementById('db_name').value,
                db_user: document.getElementById('db_user').value,
                db_pass: document.getElementById('db_pass').value,
            };

            try {
                const res = await fetch("{{ url('/install/test-db') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showAlert(step2Alert, 'success', data.message);
                } else {
                    showAlert(step2Alert, 'error', data.message || 'Database test failed.');
                }
            } catch (e) {
                showAlert(step2Alert, 'error', 'Error connecting to server.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Test Connection';
            }
        });

        // Execute Install: First save customer details, then run installation
        document.getElementById('database-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-run-install');
            btn.disabled = true;

            // First, save customer details to session
            const customerPayload = {
                name: document.getElementById('name').value,
                phone_country_code: document.getElementById('phone_country_code').value,
                phone_number: document.getElementById('phone_number').value,
                email: document.getElementById('email').value,
                profession: document.getElementById('profession').value,
                country: document.getElementById('country').value,
                city: document.getElementById('city').value,
            };

            try {
                const verifyRes = await fetch("{{ url('/install/verify-customer') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(customerPayload)
                });
                const verifyData = await verifyRes.json();
                if (!verifyRes.ok || !verifyData.success) {
                    showAlert(step1Alert, 'error', verifyData.message || 'Please fill in all required details.');
                    btn.disabled = false;
                    return;
                }
                verifiedCustomer = verifyData.customer;
            } catch (err) {
                showAlert(step1Alert, 'error', 'Network error saving your details. Please try again.');
                btn.disabled = false;
                return;
            }

            const payload = {
                db_host: document.getElementById('db_host').value,
                db_port: document.getElementById('db_port').value,
                db_name: document.getElementById('db_name').value,
                db_user: document.getElementById('db_user').value,
                db_pass: document.getElementById('db_pass').value,
                app_url: document.getElementById('app_url').value,
                company_name: document.getElementById('company_name')?.value || '',
                company_email: verifiedCustomer?.email || 'admin@openbooks.sg',
                admin_email: document.getElementById('admin_email')?.value || verifiedCustomer?.email || 'admin@openbooks.sg',
                admin_password: document.getElementById('admin_password')?.value || '',
            };

            // Switch to loading view
            step1Content.classList.add('hidden');
            step2Loading.classList.remove('hidden');
            setTab(2);

            try {
                const res = await fetch("{{ url('/install/process') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    window.location.href = data.redirect || "{{ url('/install/complete') }}";
                } else {
                    step2Loading.classList.add('hidden');
                    step1Content.classList.remove('hidden');
                    setTab(1);
                    showAlert(step2Alert, 'error', data.message || 'Installation encountered an error. Please verify credentials.');
                    btn.disabled = false;
                }
            } catch (err) {
                step2Loading.classList.add('hidden');
                step1Content.classList.remove('hidden');
                setTab(1);
                showAlert(step2Alert, 'error', 'Installation interrupted by server error: ' + err.message);
                btn.disabled = false;
            }
        });
    </script>
    <script>lucide.createIcons();</script>
</body>
</html>
