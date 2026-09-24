<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Customer Portal - Invoice {{ $invoice->invoice_number }}</title>
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
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .print-full { max-width: 100% !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 font-sans antialiased">

    {{-- HEADER --}}
    <header class="bg-white border-b border-slate-200 shadow-sm no-print">
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

    {{-- ACTION BAR --}}
    <div class="bg-white border-b border-slate-200 no-print">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <a href="{{ route('portal.invoices') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-indigo-600 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Invoices
            </a>
            <button onclick="window.print()" class="btn btn-secondary">
                <i data-lucide="printer" aria-hidden="true"></i> Print Invoice
            </button>
        </div>
    </div>

    {{-- INVOICE DOCUMENT --}}
    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 print-full">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            {{-- INVOICE TOP SECTION --}}
            <div class="p-8 border-b border-slate-100">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
                    {{-- Company Info --}}
                    <div>
                        <h2 class="text-xl font-bold text-slate-800">{{ $company->name ?? '' }}</h2>
                        @if(!empty($company->address))
                            <p class="text-sm text-slate-500 mt-1">{!! nl2br(e($company->address)) !!}</p>
                        @endif
                        @if(!empty($company->phone))
                            <p class="text-sm text-slate-500 mt-0.5"><i data-lucide="phone" class="w-4 h-4 inline-block mr-1.5"></i>{{ $company->phone }}</p>
                        @endif
                        @if(!empty($company->email))
                            <p class="text-sm text-slate-500"><i data-lucide="mail" class="w-4 h-4 inline-block mr-1.5"></i>{{ $company->email }}</p>
                        @endif
                    </div>

                    {{-- Invoice Title & Meta --}}
                    <div class="text-left md:text-right">
                        <h1 class="text-3xl font-extrabold text-indigo-600 tracking-tight mb-3">INVOICE</h1>
                        <div class="space-y-1 text-sm">
                            <p><span class="text-slate-500">Invoice #:</span> <span class="font-semibold text-slate-800">{{ $invoice->invoice_number }}</span></p>
                            <p><span class="text-slate-500">Date:</span> <span class="font-medium text-slate-700">{{ $invoice->invoice_date->format('d M Y') }}</span></p>
                            @if($invoice->due_date)
                                <p><span class="text-slate-500">Due Date:</span> <span class="font-medium text-slate-700">{{ $invoice->due_date->format('d M Y') }}</span></p>
                            @endif
                            <p class="pt-1">
                                @php
                                    $badgeColors = [
                                        'draft' => 'bg-slate-100 text-slate-700',
                                        'sent' => 'bg-blue-100 text-blue-700',
                                        'partial' => 'bg-amber-100 text-amber-700',
                                        'paid' => 'bg-green-100 text-green-700',
                                        'overdue' => 'bg-red-100 text-red-700',
                                        'cancelled' => 'bg-slate-100 text-slate-500',
                                    ];
                                    $badgeClass = $badgeColors[strtolower($invoice->status)] ?? 'bg-slate-100 text-slate-700';
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide capitalize {{ $badgeClass }}">
                                    {{ $invoice->status }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- BILL TO --}}
            <div class="px-8 py-6 bg-slate-50 border-b border-slate-100">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Bill To</p>
                <p class="text-base font-semibold text-slate-800">{{ $customer->name }}</p>
                @if(!empty($customer->email))
                    <p class="text-sm text-slate-500 mt-0.5">{{ $customer->email }}</p>
                @endif
                @if(!empty($customer->phone))
                    <p class="text-sm text-slate-500">{{ $customer->phone }}</p>
                @endif
                @if(!empty($customer->address))
                    <p class="text-sm text-slate-500 mt-1">{!! nl2br(e($customer->address)) !!}</p>
                @endif
            </div>

            {{-- LINE ITEMS TABLE --}}
            <div class="px-8 py-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-slate-200">
                            <th class="text-left py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">#</th>
                            <th class="text-left py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Item</th>
                            <th class="text-center py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Qty</th>
                            <th class="text-right py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Price</th>
                            <th class="text-right py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tax</th>
                            <th class="text-right py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($invoice->items as $index => $item)
                            <tr>
                                <td class="py-3 text-slate-400 font-medium">{{ $index + 1 }}</td>
                                <td class="py-3">
                                    <p class="font-medium text-slate-800">{{ $item->name }}</p>
                                    @if(!empty($item->description))
                                        <p class="text-xs text-slate-400 mt-0.5">{{ $item->description }}</p>
                                    @endif
                                </td>
                                <td class="py-3 text-center text-slate-600">{{ $item->quantity }}</td>
                                <td class="py-3 text-right text-slate-600">{{ $currencySymbol }}{{ number_format($item->price, 2) }}</td>
                                <td class="py-3 text-right text-slate-500">
                                    @if($item->tax_rate > 0)
                                        {{ $item->tax_rate }}% ({{ $currencySymbol }}{{ number_format($item->tax_amount, 2) }})
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="py-3 text-right font-semibold text-slate-800">{{ $currencySymbol }}{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">No line items.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- TOTALS --}}
            <div class="px-8 py-6 bg-slate-50 border-t border-slate-200">
                <div class="flex justify-end">
                    <div class="w-full max-w-xs space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Subtotal</span>
                            <span class="font-medium text-slate-800">{{ $currencySymbol }}{{ number_format($invoice->subtotal, 2) }}</span>
                        </div>
                        @if($invoice->tax_total > 0)
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tax</span>
                                <span class="font-medium text-slate-800">{{ $currencySymbol }}{{ number_format($invoice->tax_total, 2) }}</span>
                            </div>
                        @endif
                        @if($invoice->discount_total > 0)
                            <div class="flex justify-between">
                                <span class="text-slate-500">Discount</span>
                                <span class="font-medium text-green-600">-{{ $currencySymbol }}{{ number_format($invoice->discount_total, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between pt-2 border-t border-slate-200">
                            <span class="font-bold text-slate-800 text-base">Total</span>
                            <span class="font-bold text-slate-800 text-base">{{ $currencySymbol }}{{ number_format($invoice->total, 2) }}</span>
                        </div>
                        @if($invoice->paid_amount > 0)
                            <div class="flex justify-between">
                                <span class="text-slate-500">Paid</span>
                                <span class="font-medium text-green-600">-{{ $currencySymbol }}{{ number_format($invoice->paid_amount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between pt-2 border-t border-slate-200">
                            <span class="font-bold text-slate-800 text-base">Balance Due</span>
                            <span class="font-bold text-base {{ $invoice->due_amount > 0 ? 'text-red-600' : 'text-green-600' }}">
                                {{ $currencySymbol }}{{ number_format($invoice->due_amount, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- NOTES & TERMS --}}
            @if(!empty($invoice->notes) || !empty($invoice->terms))
                <div class="px-8 py-6 border-t border-slate-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @if(!empty($invoice->notes))
                            <div>
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                                    <i data-lucide="sticky-note" class="w-3.5 h-3.5 inline-block mr-1"></i> Notes
                                </p>
                                <p class="text-sm text-slate-600 leading-relaxed">{!! nl2br(e($invoice->notes)) !!}</p>
                            </div>
                        @endif
                        @if(!empty($invoice->terms))
                            <div>
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                                    <i data-lucide="scale" class="w-3.5 h-3.5 inline-block mr-1"></i> Terms &amp; Conditions
                                </p>
                                <p class="text-sm text-slate-600 leading-relaxed">{!! nl2br(e($invoice->terms)) !!}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </main>

    {{-- FOOTER --}}
    <footer class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 mt-4 no-print">
        <p class="text-center text-xs text-slate-400">Powered by <span class="font-semibold text-slate-500">OpenBooks SG</span></p>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
