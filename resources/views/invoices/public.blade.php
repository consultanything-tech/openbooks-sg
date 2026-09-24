<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <title>Invoice {{ $invoice->invoice_number }} - OpenBooks SG</title>
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
                        <i data-lucide="coins" class="w-4 h-4"></i>
                    </div>
                @endif
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ $company->name ?? 'OpenBooks Enterprise' }}</h2>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">Client Invoicing Portal</p>
                </div>
            </div>
            <button onclick="window.print()" class="px-3.5 py-1.5 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl border border-slate-300 dark:border-slate-700 transition flex items-center gap-1.5">
                <i data-lucide="printer" class="w-4 h-4"></i> Print / Save PDF
            </button>
        </div>

        <!-- Invoice Card -->
        <div class="bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 shadow-sm space-y-8">
            <div class="flex justify-between items-start border-b border-slate-100 dark:border-slate-800 pb-6">
                <div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">INVOICE</h3>
                    <p class="text-xs font-mono font-bold mt-0.5" style="color: {{ $company->accent_color ?? '#2563eb' }};">{{ $invoice->invoice_number }}</p>
                </div>
                <div class="text-right">
                    @php
                        $badgeClasses = [
                            'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                            'sent' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
                            'draft' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
                            'overdue' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
                            'partial' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                        ];
                    @endphp
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border uppercase {{ $badgeClasses[$invoice->status] ?? 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700' }}">
                        {{ $invoice->status }}
                    </span>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Due Date: {{ date('M d, Y', strtotime($invoice->due_date)) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 text-xs">
                <div>
                    <h4 class="font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Billed To</h4>
                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $invoice->customer->name ?? 'Customer' }}</p>
                    <p class="text-slate-500 dark:text-slate-400">{{ $invoice->customer->address ?? '' }}</p>
                    <p class="text-slate-500 dark:text-slate-400">{{ $invoice->customer->city ?? '' }}, {{ $invoice->customer->country ?? '' }}</p>
                </div>
                <div class="text-right">
                    <h4 class="font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Payable To</h4>
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
                        @foreach($invoice->items as $idx => $it)
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

            <!-- Totals & Balance -->
            <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
                <div class="w-72 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-500 dark:text-slate-400">
                        <span>Subtotal:</span>
                        <span class="text-slate-900 dark:text-white font-medium">{{ $currencySymbol }}{{ number_format($invoice->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-500 dark:text-slate-400">
                        <span>GST (Tax):</span>
                        <span class="text-slate-900 dark:text-white font-medium">{{ $currencySymbol }}{{ number_format($invoice->tax_total, 2) }}</span>
                    </div>
                    @if(($invoice->discount_total ?? 0) > 0)
                    <div class="flex justify-between text-slate-500 dark:text-slate-400">
                        <span>Discount:</span>
                        <span class="text-red-500">-{{ $currencySymbol }}{{ number_format($invoice->discount_total, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-sm font-bold text-slate-900 dark:text-white pt-2 border-t border-slate-200 dark:border-slate-800">
                        <span>Total Amount:</span>
                        <span>{{ $currencySymbol }}{{ number_format($invoice->total, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-500 dark:text-slate-400">
                        <span>Paid to Date:</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ $currencySymbol }}{{ number_format($invoice->paid_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-base font-bold text-slate-900 dark:text-white pt-2 border-t border-slate-200 dark:border-slate-800">
                        <span>Balance Due:</span>
                        <span class="font-extrabold" style="color: {{ $company->accent_color ?? '#2563eb' }};">{{ $currencySymbol }}{{ number_format($invoice->total - $invoice->paid_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            @if($invoice->notes)
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400">
                    <h4 class="font-bold text-slate-700 dark:text-slate-300 mb-1">Payment Instructions:</h4>
                    <p class="whitespace-pre-line">{{ $invoice->notes }}</p>
                </div>
            @endif

            {{-- PayNow QR Code --}}
            @if(!empty($company->paynow_id) && ($invoice->total - $invoice->paid_amount) > 0.01 && ($invoice->currency_code ?? 'SGD') === 'SGD')
                <div class="pt-6 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex flex-col items-center text-center gap-4">
                        <div>
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Pay Instantly via PayNow</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Scan with your banking app to pay <span class="font-bold" style="color: {{ $company->accent_color ?? '#2563eb' }};">{{ $currencySymbol }}{{ number_format($invoice->total - $invoice->paid_amount, 2) }}</span></p>
                        </div>
                        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                            <div id="paynowQr" class="w-48 h-48 flex items-center justify-center">
                                <i data-lucide="loader-2" class="w-4 h-4 animate-spin text-slate-400"></i>
                            </div>
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 space-y-0.5">
                            <p>Payee: <span class="font-semibold">{{ $company->paynow_name ?? $company->name }}</span></p>
                            <p>{{ $company->paynow_id_type ?? 'UEN' }}: <span class="font-mono">{{ $company->paynow_id }}</span></p>
                            <p>Ref: <span class="font-mono">{{ $invoice->invoice_number }}</span></p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <p class="text-center text-xs text-slate-400 dark:text-slate-500">
            Powered by OpenBooks SG &bull; Verified Digital Bill
        </p>
    </div>

    @if(!empty($company->paynow_id) && ($invoice->total - $invoice->paid_amount) > 0.01 && ($invoice->currency_code ?? 'SGD') === 'SGD')
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const proxyType = '{{ $company->paynow_id_type ?? "UEN" }}' === 'MOBILE' ? '0' : '2';
        const proxyValue = '{{ $company->paynow_id }}';
        const amount = '{{ number_format($invoice->total - $invoice->paid_amount, 2, ".", "") }}';
        const reference = '{{ $invoice->invoice_number }}';

        function tlv(tag, value) {
            return tag + String(value.length).padStart(2, '0') + value;
        }

        let mai = tlv('00', 'SG.PAYNOW') + tlv('01', proxyType) + tlv('02', proxyValue) + tlv('03', '0');
        let payload = '';
        payload += tlv('00', '01');
        payload += tlv('01', '12');
        payload += tlv('26', mai);
        payload += tlv('52', '0000');
        payload += tlv('53', '702');
        payload += tlv('54', amount);
        payload += tlv('58', 'SG');
        payload += tlv('59', '{{ addslashes($company->paynow_name ?? $company->name) }}'.substring(0, 25));
        payload += tlv('60', '{{ addslashes($company->city ?? "Singapore") }}');
        payload += tlv('62', tlv('01', reference.substring(0, 25)));
        payload += '6304';

        function crc16(str) {
            let crc = 0xFFFF;
            for (let i = 0; i < str.length; i++) {
                crc ^= str.charCodeAt(i) << 8;
                for (let j = 0; j < 8; j++) {
                    crc = (crc & 0x8000) ? ((crc << 1) ^ 0x1021) : (crc << 1);
                    crc &= 0xFFFF;
                }
            }
            return crc.toString(16).toUpperCase().padStart(4, '0');
        }

        payload = payload.slice(0, -4) + '6304' + crc16(payload);

        const qr = qrcode(0, 'M');
        qr.addData(payload);
        qr.make();

        const container = document.getElementById('paynowQr');
        if (container) {
            container.innerHTML = qr.createSvgTag({ scalable: true });
            container.querySelector('svg').style.width = '100%';
            container.querySelector('svg').style.height = '100%';
        }
    });
    </script>
    @endif
</body>
</html>
