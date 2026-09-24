<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Customer Portal - Login</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style type="text/tailwindcss">
        @custom-variant dark (&:where(.dark, .dark *));
        @theme {
            --font-sans: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 via-indigo-50 to-slate-100 font-sans antialiased">

    <div class="w-full max-w-md px-4">
        {{-- Company Name --}}
        <div class="text-center mb-8">
            <img src="{{ asset('logo/mark.svg') }}" alt="OpenBooks SG" class="w-16 h-16 rounded-2xl shadow-lg shadow-blue-200 mb-4">
            <h1 class="text-2xl font-bold text-slate-800">
                {{ \App\Models\Company::first()->name ?? 'Company Portal' }}
            </h1>
            <p class="text-slate-500 text-sm mt-1">Customer Self-Service Portal</p>
        </div>

        {{-- Login Card --}}
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-100 p-8">
            <h2 class="text-lg font-semibold text-slate-700 mb-6 flex items-center gap-2">
                <i data-lucide="log-in" class="w-4 h-4 text-indigo-600"></i>
                Sign in to your account
            </h2>

            {{-- Error Display --}}
            @if ($errors->any())
                <div class="mb-5 rounded-lg bg-red-50 border border-red-200 p-4">
                    <div class="flex items-start gap-3">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-medium text-red-800">Unable to sign in</p>
                            @foreach ($errors->all() as $error)
                                <p class="text-sm text-red-600 mt-1">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Login Form --}}
            <form method="POST" action="{{ route('portal.login.post') }}">
                @csrf

                <div class="mb-5">
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Email address
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                        </span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            placeholder="you@example.com"
                            class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-800 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                        >
                    </div>
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Password
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            placeholder="Enter your password"
                            class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-800 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                        >
                        <button type="button" onclick="var i=document.getElementById('password'); var s=i.type==='password'; i.type=s?'text':'password'; this.querySelector('.eye-on').classList.toggle('hidden',s); this.querySelector('.eye-off').classList.toggle('hidden',!s); this.setAttribute('aria-pressed',String(s)); this.setAttribute('aria-label', s?'Hide password':'Show password');" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition" aria-label="Show password" aria-pressed="false">
                            <i data-lucide="eye" class="eye-on w-4 h-4" aria-hidden="true"></i>
                            <i data-lucide="eye-off" class="eye-off w-4 h-4 hidden" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <button type="submit"
                    class="btn btn-primary w-full justify-center">
                    <i data-lucide="log-in" aria-hidden="true"></i>
                    Sign In
                </button>
            </form>
        </div>

        {{-- Footer --}}
        <p class="text-center text-xs text-slate-400 mt-8">
            Powered by <span class="font-semibold text-slate-500">OpenBooks SG</span>
        </p>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
