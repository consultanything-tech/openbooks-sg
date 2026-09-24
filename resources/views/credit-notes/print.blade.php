<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.favicon')
    <title>Print Credit Note - {{ $creditNote->credit_note_number }}</title>
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
            <h2 class="text-2xl font-black uppercase" style="color: {{ $company->accent_color ?? '#2563eb' }};">CREDIT NOTE</h2>
            <p class="text-sm font-mono font-bold text-slate-700 mt-1">{{ $creditNote->credit_note_number }}</p>
            <p class="text-xs text-slate-500 mt-0.5">Date: {{ date('M d, Y', strtotime($creditNote->credit_note_date)) }}</p>
            @if($creditNote->invoice)
                <p class="text-xs text-slate-500">Ref Invoice: {{ $creditNote->invoice->invoice_number }}</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 gap-8 mb-8">
        <div>
            <h3 class="text-xs font-bold text-slate-400 uppercase mb-1">Issued To</h3>
            <h4 class="text-sm font-bold text-slate-900">{{ $creditNote->customer->name ?? 'Customer' }}</h4>
            <p class="text-xs text-slate-600">{{ $creditNote->customer->address ?? '' }}</p>
            <p class="text-xs text-slate-600">{{ $creditNote->customer->city ?? '' }}, {{ $creditNote->customer->country ?? '' }}</p>
            <p class="text-xs text-slate-600">Email: {{ $creditNote->customer->email ?? '' }}</p>
        </div>
        <div class="text-right space-y-1 text-xs">
            <div class="flex justify-end gap-4">
                <span class="text-slate-500">Credit Note Date:</span>
                <span class="text-slate-900 font-medium">{{ date('M d, Y', strtotime($creditNote->credit_note_date)) }}</span>
            </div>
            @if($creditNote->invoice)
            <div class="flex justify-end gap-4">
                <span class="text-slate-500">Original Invoice:</span>
                <span class="text-slate-900 font-medium">{{ $creditNote->invoice->invoice_number }}</span>
            </div>
            @endif
            <div class="flex justify-end gap-4 pt-2 border-t">
                <span class="text-slate-700 font-bold">Credit Amount:</span>
                <span class="font-extrabold" style="color: {{ $company->accent_color ?? '#2563eb' }};">{{ $currencySymbol }}{{ number_format($creditNote->total, 2) }}</span>
            </div>
        </div>
    </div>

    @if($creditNote->reason)
    <div class="mb-6 text-xs">
        <h4 class="font-bold text-slate-800 mb-1">Reason:</h4>
        <p class="text-slate-600">{{ $creditNote->reason }}</p>
    </div>
    @endif

    <table class="w-full text-left text-xs mb-6">
        <thead class="border-y font-bold" style="background-color: {{ $company->accent_color ?? '#2563eb' }}10; border-color: {{ $company->accent_color ?? '#2563eb' }}30;">
            <tr>
                <th class="py-2.5 px-3">#</th>
                <th class="py-2.5 px-3">Description</th>
                <th class="py-2.5 px-3 text-center">Qty</th>
                <th class="py-2.5 px-3 text-right">Unit Price</th>
                <th class="py-2.5 px-3 text-right">Tax</th>
                <th class="py-2.5 px-3 text-right">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($creditNote->items as $idx => $it)
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
                    <td class="py-2.5 px-3 text-right text-slate-500">{{ $currencySymbol }}{{ number_format($it->tax_amount, 2) }}</td>
                    <td class="py-2.5 px-3 text-right font-bold">{{ $currencySymbol }}{{ number_format($it->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="flex justify-end mb-8">
        <div class="w-64 space-y-1.5 text-xs">
            <div class="flex justify-between text-slate-600">
                <span>Subtotal:</span>
                <span>{{ $currencySymbol }}{{ number_format($creditNote->subtotal, 2) }}</span>
            </div>
            <div class="flex justify-between text-slate-600">
                <span>Tax (GST):</span>
                <span>{{ $currencySymbol }}{{ number_format($creditNote->tax_total, 2) }}</span>
            </div>
            <div class="flex justify-between text-base font-bold pt-2 border-t" style="color: {{ $company->accent_color ?? '#2563eb' }};">
                <span>Credit Total:</span>
                <span>{{ $currencySymbol }}{{ number_format($creditNote->total, 2) }}</span>
            </div>
        </div>
    </div>

    @if($creditNote->notes)
        <div class="border-t pt-4 text-xs text-slate-600">
            <h4 class="font-bold text-slate-800 mb-1">Notes:</h4>
            <p class="whitespace-pre-line">{{ $creditNote->notes }}</p>
        </div>
    @endif

    @if($company->invoice_footer)
        <div class="border-t pt-4 mt-6 text-xs text-slate-500 text-center">
            <p class="whitespace-pre-line">{{ $company->invoice_footer }}</p>
        </div>
    @endif
</body>
</html>
