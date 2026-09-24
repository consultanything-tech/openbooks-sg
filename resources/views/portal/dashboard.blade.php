<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Customer Portal - Dashboard</title>
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
<body class="min-h-screen bg-slate-50 font-sans antialiased">

    {{-- HEADER --}}
    <header class="bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('logo/mark.svg') }}" alt="OpenBooks SG" class="w-9 h-9 rounded-lg">
                    <div>
                        <span class="text-base font-bold text-slate-800">{{ $company->name ?? 'Company' }}</span>
                        <span class="hidden sm:inline text-slate-400 mx-2">|</span>
                        <span class="hidden sm:inline text-sm text-slate-500">{{ $customer->name }}</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('portal.logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-red-600 transition font-medium">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span class="hidden sm:inline">Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- NAVIGATION TABS --}}
    <nav class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex gap-1 -mb-px">
                <a href="{{ route('portal.dashboard') }}" class="px-4 py-3 text-sm font-semibold border-b-2 border-indigo-600 text-indigo-600">
                    <i data-lucide="gauge" class="w-4 h-4 mr-1.5"></i>Dashboard
                </a>
                <a href="{{ route('portal.invoices') }}" class="px-4 py-3 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 transition">
                    <i data-lucide="file-text" class="w-4 h-4 mr-1.5"></i>Invoices
                </a>
                <a href="{{ route('portal.statement') }}" class="px-4 py-3 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 transition">
                    <i data-lucide="download" class="w-4 h-4 mr-1.5"></i>Download Statement
                </a>
            </div>
        </div>
    </nav>

    {{-- MAIN CONTENT --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- SUMMARY CARDS --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
            {{-- Total Outstanding --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl {{ $totalOutstanding > 0 ? 'bg-red-50' : 'bg-green-50' }} flex items-center justify-center">
                        <i data-lucide="clock" class="w-5 h-5 {{ $totalOutstanding > 0 ? 'text-red-500' : 'text-green-500' }}"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-medium">Total Outstanding</p>
                        <p class="text-2xl font-bold {{ $totalOutstanding > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $currencySymbol }}{{ number_format($totalOutstanding, 2) }}
                        </p>
                    </div>
                </div>
            </div>
            {{-- Total Paid --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-medium">Total Paid</p>
                        <p class="text-2xl font-bold text-green-600">
                            {{ $currencySymbol }}{{ number_format($totalPaid, 2) }}
                        </p>
                    </div>
                </div>
            </div>
            {{-- Invoice Count --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:col-span-2 lg:col-span-1">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center">
                        <i data-lucide="file-text" class="w-5 h-5 text-indigo-500"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-medium">Invoices</p>
                        <p class="text-2xl font-bold text-slate-800">
                            {{ $recentInvoices->count() }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- RECENT INVOICES TABLE --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-8">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-800">
                    <i data-lucide="file-text" class="w-4 h-4 text-indigo-500 mr-2"></i>Recent Invoices
                </h2>
                <a href="{{ route('portal.invoices') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                    View all <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                            <th class="px-6 py-3">Invoice #</th>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Total</th>
                            <th class="px-6 py-3 text-right">Due Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($recentInvoices as $inv)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-3.5">
                                    <a href="{{ route('portal.invoices.show', $inv->id) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">
                                        {{ $inv->invoice_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-3.5 text-slate-600">{{ $inv->invoice_date->format('d M Y') }}</td>
                                <td class="px-6 py-3.5">
                                    @php
                                        $badgeColors = [
                                            'draft' => 'bg-slate-100 text-slate-700',
                                            'sent' => 'bg-blue-100 text-blue-700',
                                            'partial' => 'bg-amber-100 text-amber-700',
                                            'paid' => 'bg-green-100 text-green-700',
                                            'overdue' => 'bg-red-100 text-red-700',
                                            'cancelled' => 'bg-slate-100 text-slate-500',
                                        ];
                                        $badgeClass = $badgeColors[strtolower($inv->status)] ?? 'bg-slate-100 text-slate-700';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize {{ $badgeClass }}">
                                        {{ $inv->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right font-medium text-slate-800">{{ $currencySymbol }}{{ number_format($inv->total, 2) }}</td>
                                <td class="px-6 py-3.5 text-right font-medium {{ $inv->due_amount > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $currencySymbol }}{{ number_format($inv->due_amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-slate-400">
                                    <i data-lucide="inbox" class="w-8 h-8 mb-3 block"></i>
                                    No invoices found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- RECENT PAYMENTS TABLE --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-base font-semibold text-slate-800">
                    <i data-lucide="arrow-left-right" class="w-4 h-4 text-green-500 mr-2"></i>Recent Payments
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3 text-right">Amount</th>
                            <th class="px-6 py-3">Reference</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($recentPayments as $pay)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-3.5 text-slate-600">{{ $pay->payment_date->format('d M Y') }}</td>
                                <td class="px-6 py-3.5 text-right font-medium text-green-600">{{ $currencySymbol }}{{ number_format($pay->amount, 2) }}</td>
                                <td class="px-6 py-3.5 text-slate-500">{{ $pay->reference ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-10 text-center text-slate-400">
                                    <i data-lucide="inbox" class="w-8 h-8 mb-3 block"></i>
                                    No payments recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    {{-- FOOTER --}}
    <footer class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 mt-4">
        <p class="text-center text-xs text-slate-400">Powered by <span class="font-semibold text-slate-500">OpenBooks SG</span></p>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
