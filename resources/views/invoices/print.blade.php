<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.favicon')
    <title>Print Invoice - {{ $invoice->invoice_number }}</title>
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
            <h2 class="text-2xl font-black uppercase" style="color: {{ $company->accent_color ?? '#2563eb' }};">INVOICE</h2>
            <p class="text-sm font-mono font-bold text-slate-700 mt-1">{{ $invoice->invoice_number }}</p>
            <p class="text-xs text-slate-500 mt-0.5">Date: {{ date('M d, Y', strtotime($invoice->invoice_date)) }}</p>
            <p class="text-xs text-slate-500">Due: {{ date('M d, Y', strtotime($invoice->due_date)) }}</p>
            @if(($invoice->currency_code ?? 'SGD') !== ($company->currency_code ?? 'SGD'))
                <p class="text-xs text-slate-600 font-semibold mt-1">Currency: {{ $invoice->currency_code }} (Rate: {{ number_format($invoice->exchange_rate, 6) }})</p>
            @endif
        </div>
    </div>

    <div class="mb-8">
        <h3 class="text-xs font-bold text-slate-400 uppercase mb-1">Billed To</h3>
        <h4 class="text-sm font-bold text-slate-900">{{ $invoice->customer->name ?? 'Customer' }}</h4>
        <p class="text-xs text-slate-600">{{ $invoice->customer->address ?? '' }}</p>
        <p class="text-xs text-slate-600">{{ $invoice->customer->city ?? '' }}, {{ $invoice->customer->country ?? '' }}</p>
        <p class="text-xs text-slate-600">Email: {{ $invoice->customer->email ?? '' }}</p>
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
            @foreach($invoice->items as $idx => $it)
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
                <span>{{ $currencySymbol }}{{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            <div class="flex justify-between text-slate-600">
                <span>Tax (GST):</span>
                <span>{{ $currencySymbol }}{{ number_format($invoice->tax_total, 2) }}</span>
            </div>
            @if(($invoice->discount_total ?? 0) > 0)
            <div class="flex justify-between text-slate-600">
                <span>Discount:</span>
                <span class="text-red-600">-{{ $currencySymbol }}{{ number_format($invoice->discount_total, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between text-base font-bold text-slate-900 pt-2 border-t">
                <span>Total:</span>
                <span>{{ $currencySymbol }}{{ number_format($invoice->total, 2) }}</span>
            </div>
            <div class="flex justify-between text-slate-600">
                <span>Paid:</span>
                <span>{{ $currencySymbol }}{{ number_format($invoice->paid_amount, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm font-bold pt-1 border-t" style="color: {{ $company->accent_color ?? '#2563eb' }};">
                <span>Balance Due:</span>
                <span>{{ $currencySymbol }}{{ number_format($invoice->total - $invoice->paid_amount, 2) }}</span>
            </div>
            @if(($invoice->currency_code ?? 'SGD') !== ($company->currency_code ?? 'SGD'))
            <div class="flex justify-between text-xs text-slate-600 pt-1 border-t mt-1">
                <span>{{ $company->currency_code ?? 'SGD' }} Equivalent:</span>
                <span class="font-semibold">{{ $company->currency_symbol ?? 'S$' }}{{ number_format($invoice->total * $invoice->exchange_rate, 2) }}</span>
            </div>
            @endif
        </div>
    </div>

    @if($invoice->notes)
        <div class="border-t pt-4 text-xs text-slate-600">
            <h4 class="font-bold text-slate-800 mb-1">Notes:</h4>
            <p class="whitespace-pre-line">{{ $invoice->notes }}</p>
        </div>
    @endif

    @if($company->default_payment_terms)
        <div class="border-t pt-4 mt-4 text-xs text-slate-600">
            <h4 class="font-bold text-slate-800 mb-1">Payment Terms:</h4>
            <p class="whitespace-pre-line">{{ $company->default_payment_terms }}</p>
        </div>
    @endif

    @if(!empty($company->paynow_id) && ($invoice->total - $invoice->paid_amount) > 0.01 && ($invoice->currency_code ?? 'SGD') === 'SGD')
        <div class="border-t pt-4 mt-4 flex items-start gap-4">
            <div class="flex-1 text-xs text-slate-600">
                <h4 class="font-bold text-slate-800 mb-1">Pay via PayNow</h4>
                <p>Payee: {{ $company->paynow_name ?? $company->name }}</p>
                <p>{{ $company->paynow_id_type ?? 'UEN' }}: {{ $company->paynow_id }}</p>
                <p>Amount: {{ $currencySymbol }}{{ number_format($invoice->total - $invoice->paid_amount, 2) }}</p>
                <p>Ref: {{ $invoice->invoice_number }}</p>
            </div>
            <div class="w-28 h-28 border border-slate-200 rounded-lg p-1">
                <div id="printPaynowQr" class="w-full h-full"></div>
            </div>
        </div>
    @endif

    @if($company->invoice_footer)
        <div class="border-t pt-4 mt-6 text-xs text-slate-500 text-center">
            <p class="whitespace-pre-line">{{ $company->invoice_footer }}</p>
        </div>
    @endif

    @if(!empty($company->paynow_id) && ($invoice->total - $invoice->paid_amount) > 0.01 && ($invoice->currency_code ?? 'SGD') === 'SGD')
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const proxyType = '{{ $company->paynow_id_type ?? "UEN" }}' === 'MOBILE' ? '0' : '2';
        const proxyValue = '{{ $company->paynow_id }}';
        const amount = '{{ number_format($invoice->total - $invoice->paid_amount, 2, ".", "") }}';
        const ref = '{{ $invoice->invoice_number }}';
        function tlv(t, v) { return t + String(v.length).padStart(2, '0') + v; }
        let mai = tlv('00', 'SG.PAYNOW') + tlv('01', proxyType) + tlv('02', proxyValue) + tlv('03', '0');
        let p = tlv('00','01') + tlv('01','12') + tlv('26', mai) + tlv('52','0000') + tlv('53','702') + tlv('54', amount) + tlv('58','SG') + tlv('59', '{{ addslashes($company->paynow_name ?? $company->name) }}'.substring(0,25)) + tlv('60', '{{ addslashes($company->city ?? "Singapore") }}') + tlv('62', tlv('01', ref.substring(0,25))) + '6304';
        function crc16(s) { let c=0xFFFF; for(let i=0;i<s.length;i++){c^=s.charCodeAt(i)<<8;for(let j=0;j<8;j++){c=(c&0x8000)?((c<<1)^0x1021):(c<<1);c&=0xFFFF;}} return c.toString(16).toUpperCase().padStart(4,'0'); }
        p = p.slice(0,-4) + '6304' + crc16(p);
        const qr = qrcode(0, 'M'); qr.addData(p); qr.make();
        const el = document.getElementById('printPaynowQr');
        if (el) { el.innerHTML = qr.createSvgTag({scalable:true}); el.querySelector('svg').style.width='100%'; el.querySelector('svg').style.height='100%'; }
    });
    </script>
    @endif
</body>
</html>
