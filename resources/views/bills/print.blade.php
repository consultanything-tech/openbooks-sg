<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.favicon')
    <title>Print Bill - {{ $bill->bill_number }}</title>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-white text-slate-900 p-8 max-w-4xl mx-auto text-sm">
    {{-- Accent top border --}}
    <div style="height: 4px; background-color: {{ $company->accent_color ?? '#2563eb' }};" class="rounded-full mb-6"></div>

    <div class="no-print mb-6 flex justify-between items-center bg-slate-100 p-4 rounded-xl border border-slate-200">
        <span class="text-xs text-slate-600">Print Preview Mode</span>
        <button onclick="window.print()" class="text-white text-xs font-medium px-5 py-2 rounded-xl transition" style="background-color: {{ $company->accent_color ?? '#2563eb' }};">
            Print / Save as PDF
        </button>
    </div>

    <div class="flex justify-between items-start border-b pb-6 mb-6">
        <div>
            @if(($company->show_logo_on_documents ?? true) && !empty($company->logo_path))
                <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="h-16 w-auto mb-2">
            @endif
            <h1 class="text-xl font-bold tracking-tight text-slate-950">{{ $company->name ?? 'OpenBooks Enterprise' }}</h1>
            <p class="text-xs text-slate-600 mt-1">{{ $company->address ?? '' }}</p>
            <p class="text-xs text-slate-600">{{ $company->city ?? '' }}, {{ $company->country ?? '' }}</p>
            <p class="text-xs text-slate-600">Email: {{ $company->email ?? '' }}</p>
            @if(($company->show_phone_on_documents ?? true) && !empty($company->phone))
                <p class="text-xs text-slate-600">Phone: {{ $company->phone }}</p>
            @endif
            @if(($company->show_tax_number_on_documents ?? true) && !empty($company->tax_number))
                <p class="text-xs text-slate-600">Tax ID: {{ $company->tax_number }}</p>
            @endif
        </div>
        <div class="text-right">
            <h2 class="text-2xl font-black uppercase" style="color: {{ $company->accent_color ?? '#2563eb' }};">BILL</h2>
            <p class="text-sm font-mono font-bold text-slate-700 mt-1">{{ $bill->bill_number }}</p>
            <p class="text-xs text-slate-500 mt-0.5">Date: {{ date('M d, Y', strtotime($bill->bill_date)) }}</p>
            <p class="text-xs text-slate-500">Due: {{ date('M d, Y', strtotime($bill->due_date)) }}</p>
            @if(($bill->currency_code ?? 'SGD') !== ($company->currency_code ?? 'SGD'))
                <p class="text-xs text-slate-600 font-semibold mt-1">Currency: {{ $bill->currency_code }} (Rate: {{ number_format($bill->exchange_rate, 6) }})</p>
            @endif
        </div>
    </div>

    <div class="mb-8">
        <h3 class="text-xs font-bold text-slate-400 uppercase mb-1">Vendor</h3>
        <h4 class="text-sm font-bold text-slate-900">{{ $bill->vendor->name ?? 'Vendor' }}</h4>
        <p class="text-xs text-slate-600">{{ $bill->vendor->address ?? '' }}</p>
        <p class="text-xs text-slate-600">{{ $bill->vendor->city ?? '' }}, {{ $bill->vendor->country ?? '' }}</p>
        <p class="text-xs text-slate-600">Email: {{ $bill->vendor->email ?? '' }}</p>
        @if($bill->vendor->phone ?? false)
            <p class="text-xs text-slate-600">Phone: {{ $bill->vendor->phone }}</p>
        @endif
    </div>

    <table class="w-full text-left text-xs mb-6">
        <thead class="border-y font-bold" style="background-color: {{ $company->accent_color ?? '#2563eb' }}10; border-color: {{ $company->accent_color ?? '#2563eb' }}30;">
            <tr>
                <th class="py-2.5 px-3">#</th>
                <th class="py-2.5 px-3">Description</th>
                <th class="py-2.5 px-3 text-center">Qty</th>
                <th class="py-2.5 px-3 text-right">Unit Price</th>
                <th class="py-2.5 px-3 text-right">Amount</th>
                <th class="py-2.5 px-3 text-right">Tax</th>
                <th class="py-2.5 px-3 text-right">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($bill->items as $idx => $it)
                <tr>
                    <td class="py-2.5 px-3 text-slate-400">{{ $idx + 1 }}</td>
                    <td class="py-2.5 px-3">
                        <span class="font-medium">{{ $it->name }}</span>
                        @if($it->description)
                            <br><span class="text-slate-500 text-[10px]">{{ $it->description }}</span>
                        @endif
                    </td>
                    <td class="py-2.5 px-3 text-center">{{ $it->quantity }}</td>
                    <td class="py-2.5 px-3 text-right">{{ $currencySymbol }}{{ number_format($it->price, 2) }}</td>
                    <td class="py-2.5 px-3 text-right">{{ $currencySymbol }}{{ number_format($it->quantity * $it->price, 2) }}</td>
                    <td class="py-2.5 px-3 text-right text-slate-500">{{ number_format($it->tax_rate, 0) }}% ({{ $currencySymbol }}{{ number_format($it->tax_amount, 2) }})</td>
                    <td class="py-2.5 px-3 text-right font-bold">{{ $currencySymbol }}{{ number_format($it->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="flex justify-end mb-8">
        <div class="w-64 space-y-1.5 text-xs">
            <div class="flex justify-between text-slate-600">
                <span>Subtotal:</span>
                <span>{{ $currencySymbol }}{{ number_format($bill->subtotal, 2) }}</span>
            </div>
            <div class="flex justify-between text-slate-600">
                <span>Tax (GST):</span>
                <span>{{ $currencySymbol }}{{ number_format($bill->tax_total, 2) }}</span>
            </div>
            @if(($bill->discount_total ?? 0) > 0)
            <div class="flex justify-between text-slate-600">
                <span>Discount:</span>
                <span class="text-red-600">-{{ $currencySymbol }}{{ number_format($bill->discount_total, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between text-base font-bold text-slate-900 pt-2 border-t">
                <span>Total:</span>
                <span>{{ $currencySymbol }}{{ number_format($bill->total, 2) }}</span>
            </div>
            <div class="flex justify-between text-slate-600">
                <span>Paid:</span>
                <span>{{ $currencySymbol }}{{ number_format($bill->paid_amount, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm font-bold pt-1 border-t" style="color: {{ $company->accent_color ?? '#2563eb' }};">
                <span>Balance Due:</span>
                <span>{{ $currencySymbol }}{{ number_format($bill->total - $bill->paid_amount, 2) }}</span>
            </div>
            @if(($bill->currency_code ?? 'SGD') !== ($company->currency_code ?? 'SGD'))
            <div class="flex justify-between text-xs text-slate-600 pt-1 border-t mt-1">
                <span>{{ $company->currency_code ?? 'SGD' }} Equivalent:</span>
                <span class="font-semibold">{{ $company->currency_symbol ?? 'S$' }}{{ number_format($bill->total * $bill->exchange_rate, 2) }}</span>
            </div>
            @endif
        </div>
    </div>

    @if($bill->notes)
        <div class="border-t pt-4 text-xs text-slate-600">
            <h4 class="font-bold text-slate-800 mb-1">Notes:</h4>
            <p class="whitespace-pre-line">{{ $bill->notes }}</p>
        </div>
    @endif

    @if($company->default_payment_terms)
        <div class="border-t pt-4 mt-4 text-xs text-slate-600">
            <h4 class="font-bold text-slate-800 mb-1">Payment Terms:</h4>
            <p class="whitespace-pre-line">{{ $company->default_payment_terms }}</p>
        </div>
    @endif

    @if($company->invoice_footer)
        <div class="border-t pt-4 mt-6 text-xs text-slate-500 text-center">
            <p class="whitespace-pre-line">{{ $company->invoice_footer }}</p>
        </div>
    @endif
</body>
</html>
