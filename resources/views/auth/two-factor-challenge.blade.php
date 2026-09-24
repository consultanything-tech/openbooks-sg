<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Two-Factor Verification - OpenBooks SG</title>

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
        }
    </style>
</head>
<body class="h-full min-h-screen bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950 flex items-center justify-center px-4">

    <div class="w-full max-w-sm">

        {{-- Card --}}
        <div class="bg-white/[0.07] backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl p-8">

            {{-- Shield Icon --}}
            <div class="flex flex-col items-center mb-8">
                <div class="w-14 h-14 rounded-2xl bg-indigo-500/15 border border-indigo-500/25 flex items-center justify-center mb-4">
                    <i data-lucide="shield" class="w-5 h-5 text-indigo-400"></i>
                </div>
                <h1 class="text-lg font-bold text-white tracking-tight">Two-Factor Authentication</h1>
                <p class="text-xs text-slate-400 mt-1.5 text-center leading-relaxed">
                    Enter the 6-digit verification code from your authenticator app, or use a recovery code.
                </p>
            </div>

            {{-- Error Message --}}
            @if(session('error'))
                <div class="mb-5 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-xs flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-xs flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            {{-- Verification Form --}}
            <form action="{{ route('2fa.challenge.verify') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1.5 uppercase tracking-wider">Verification Code</label>
                    <input type="text" name="code" id="codeInput" required autofocus autocomplete="one-time-code" inputmode="numeric" placeholder="000000" maxlength="32"
                        class="w-full bg-white/[0.06] border border-white/10 rounded-xl px-3.5 py-3 text-white placeholder-slate-600 text-center text-xl font-mono tracking-[0.4em] focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    <p class="text-[10px] text-slate-500 mt-2 text-center">You may also enter a recovery code</p>
                </div>

                <button type="submit"
                    class="btn btn-primary w-full justify-center">
                    <i data-lucide="log-in" aria-hidden="true"></i>
                    <span>Verify</span>
                </button>
            </form>
        </div>

        {{-- Back to Login --}}
        <div class="flex justify-center mt-6">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-300 transition font-medium">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                Back to login
            </a>
        </div>

        {{-- Footer --}}
        <p class="text-center text-[10px] text-slate-600 mt-4">
            OpenBooks SG &mdash; Secure Authentication
        </p>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
