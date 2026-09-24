<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <title>Quotation {{ $quote->quote_number }} - {{ $company->name ?? 'OpenBooks SG' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="h-full bg-slate-100 dark:bg-slate-950 py-10 px-4 sm:px-6">
    <div class="max-w-3xl mx-auto space-y-6">
        <!-- Brand Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if(($company->show_logo_on_documents ?? true) && !empty($company->logo_path))
                    <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="h-9 w-auto">
                @else
                    <div class="w-9 h-9 rounded-xl border flex items-center justify-center font-bold" style="background-color: {{ $company->accent_color ?? '#2563eb' }}10; border-color: {{ $company->accent_color ?? '#2563eb' }}40; color: {{ $company->accent_color ?? '#2563eb' }};">
                        <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                    </div>
                @endif
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ $company->name ?? 'OpenBooks Enterprise' }}</h2>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">Client Quotation Portal</p>
                </div>
            </div>
            <button onclick="window.print()" class="px-3.5 py-1.5 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl border border-slate-300 dark:border-slate-700 transition flex items-center gap-1.5">
                <i data-lucide="printer" class="w-4 h-4"></i> Print / Save PDF
            </button>
        </div>

        <!-- Quote Card -->
        <div class="bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 shadow-sm space-y-8">
            <div class="flex justify-between items-start border-b border-slate-100 dark:border-slate-800 pb-6">
                <div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">QUOTATION</h3>
                    <p class="text-xs font-mono font-bold mt-0.5" style="color: {{ $company->accent_color ?? '#2563eb' }};">{{ $quote->quote_number }}</p>
                </div>
                <div class="text-right">
                    @php
                        $badgeClasses = [
                            'draft' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
                            'sent' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
                            'accepted' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                            'declined' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
                            'expired' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                            'converted' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/20',
                        ];
                    @endphp
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border uppercase {{ $badgeClasses[$quote->status] ?? 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700' }}">
                        {{ $quote->status }}
                    </span>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Valid Until: {{ date('M d, Y', strtotime($quote->expiry_date)) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 text-xs">
                <div>
                    <h4 class="font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Quoted To</h4>
                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $quote->customer->name ?? 'Customer' }}</p>
                    <p class="text-slate-500 dark:text-slate-400">{{ $quote->customer->address ?? '' }}</p>
                    <p class="text-slate-500 dark:text-slate-400">{{ $quote->customer->city ?? '' }}, {{ $quote->customer->country ?? '' }}</p>
                </div>
                <div class="text-right">
                    <h4 class="font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">From</h4>
                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $company->name ?? 'OpenBooks Enterprise' }}</p>
                    <p class="text-slate-500 dark:text-slate-400">{{ $company->address ?? '' }}</p>
                    <p class="text-slate-500 dark:text-slate-400">Email: {{ $company->email ?? '' }}</p>
                    @if(($company->show_phone_on_documents ?? true) && !empty($company->phone))
                        <p class="text-slate-500 dark:text-slate-400">Phone: {{ $company->phone }}</p>
                    @endif
                    @if(($company->show_tax_number_on_documents ?? true) && !empty($company->tax_number))
                        <p class="text-slate-500 dark:text-slate-400 font-mono">Tax ID: {{ $company->tax_number }}</p>
                    @endif
                </div>
            </div>

            <!-- Items -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 font-semibold border-y border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-2.5 px-3 w-8">#</th>
                            <th class="py-2.5 px-3">Description</th>
                            <th class="py-2.5 px-3 text-center">Qty</th>
                            <th class="py-2.5 px-3 text-right">Unit Price</th>
                            <th class="py-2.5 px-3 text-right">Amount</th>
                            <th class="py-2.5 px-3 text-right">Tax</th>
                            <th class="py-2.5 px-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        @foreach($quote->items as $idx => $it)
                            <tr>
                                <td class="py-2.5 px-3 text-slate-400 dark:text-slate-500">{{ $idx + 1 }}</td>
                                <td class="py-2.5 px-3">
                                    <span class="font-medium text-slate-900 dark:text-white">{{ $it->name }}</span>
                                    @if($it->description)
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">{{ $it->description }}</p>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-center">{{ $it->quantity }}</td>
                                <td class="py-2.5 px-3 text-right">{{ $currencySymbol }}{{ number_format($it->price, 2) }}</td>
                                <td class="py-2.5 px-3 text-right">{{ $currencySymbol }}{{ number_format($it->quantity * $it->price, 2) }}</td>
                                <td class="py-2.5 px-3 text-right text-slate-500 dark:text-slate-400">
                                    <span class="text-[10px]">{{ number_format($it->tax_rate, 0) }}%</span>
                                    {{ $currencySymbol }}{{ number_format($it->tax_amount, 2) }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-semibold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($it->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
                <div class="w-72 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-500 dark:text-slate-400">
                        <span>Subtotal:</span>
                        <span class="text-slate-900 dark:text-white font-medium">{{ $currencySymbol }}{{ number_format($quote->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-500 dark:text-slate-400">
                        <span>GST (Tax):</span>
                        <span class="text-slate-900 dark:text-white font-medium">{{ $currencySymbol }}{{ number_format($quote->tax_total, 2) }}</span>
                    </div>
                    @if(($quote->discount ?? 0) > 0)
                    <div class="flex justify-between text-slate-500 dark:text-slate-400">
                        <span>Discount:</span>
                        <span class="text-red-500">-{{ $currencySymbol }}{{ number_format($quote->discount, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-base font-bold text-slate-900 dark:text-white pt-2 border-t border-slate-200 dark:border-slate-800">
                        <span>Quote Total:</span>
                        <span class="font-extrabold" style="color: {{ $company->accent_color ?? '#2563eb' }};">{{ $currencySymbol }}{{ number_format($quote->total, 2) }}</span>
                    </div>
                </div>
            </div>

            @if($quote->notes)
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400">
                    <h4 class="font-bold text-slate-700 dark:text-slate-300 mb-1">Notes:</h4>
                    <p class="whitespace-pre-line">{{ $quote->notes }}</p>
                </div>
            @endif

            @if($quote->terms)
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400">
                    <h4 class="font-bold text-slate-700 dark:text-slate-300 mb-1">Terms & Conditions:</h4>
                    <p class="whitespace-pre-line">{{ $quote->terms }}</p>
                </div>
            @endif
        </div>

        <p class="text-center text-xs text-slate-400 dark:text-slate-500">
            Powered by OpenBooks SG &bull; Verified Digital Quotation
        </p>
    </div>
</body>
</html>
