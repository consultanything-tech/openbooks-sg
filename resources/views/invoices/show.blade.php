@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number)

@php
    $currencySymbol = $currencySymbol ?? $company->currency_symbol ?? 'S$';
@endphp

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <x-breadcrumbs :items="[['label' => 'Invoices', 'url' => route('invoices.index')], ['label' => $invoice->invoice_number]]" />

    <!-- Action Buttons -->
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn btn-secondary">
            <i data-lucide="pencil" aria-hidden="true"></i> Edit Invoice
        </a>
        @if($invoice->status === 'draft')
            <form action="{{ route('invoices.mark_sent', $invoice->id) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn btn-secondary">
                    <i data-lucide="send" aria-hidden="true"></i> Mark as Sent
                </button>
            </form>
        @endif
        <form action="{{ route('invoices.duplicate', $invoice->id) }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="btn btn-secondary">
                <i data-lucide="copy" aria-hidden="true"></i> Duplicate
            </button>
        </form>
        <a href="{{ route('credit_notes.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-secondary">
            <i data-lucide="file-minus" aria-hidden="true"></i> Create Credit Note
        </a>
        @if($invoice->status !== 'paid')
            <form action="{{ route('invoices.destroy', $invoice->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this invoice? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger-text">
                    Delete
                </button>
            </form>
        @endif
    </div>

    <!-- Header with Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{{ $invoice->invoice_number }}</h1>
                @php
                    $badgeClasses = [
                        'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                        'sent' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
                        'draft' => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-700/40 dark:text-slate-300 dark:border-slate-600',
                        'overdue' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
                        'partial' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                    ];
                @endphp
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border uppercase tracking-wider {{ $badgeClasses[$invoice->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                    {{ $invoice->status }}
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Issued on {{ date('M d, Y', strtotime($invoice->invoice_date)) }} &bull; Due {{ date('M d, Y', strtotime($invoice->due_date)) }}
                @if(($invoice->currency_code ?? 'SGD') !== ($company->currency_code ?? 'SGD'))
                    &bull; <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $invoice->currency_code }} @ {{ number_format($invoice->exchange_rate, 6) }}</span>
                @endif
            </p>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Last updated {{ $invoice->updated_at ? $invoice->updated_at->diffForHumans() : '—' }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('invoices.print', $invoice->id) }}" target="_blank" class="btn btn-secondary">
                <i data-lucide="printer" aria-hidden="true"></i> Print
            </a>
            <a href="{{ route('invoices.public', $invoice->public_token) }}" target="_blank" class="btn btn-secondary">
                <i data-lucide="external-link" aria-hidden="true"></i> Client Link
            </a>
            <button type="button" class="btn-icon" data-copy="{{ route('invoices.public', $invoice->public_token) }}" title="Copy" aria-label="Copy"><i data-lucide="copy" aria-hidden="true"></i></button>
            @if(($invoice->total - $invoice->paid_amount) > 0.01)
                <button onclick="document.getElementById('paymentModal').classList.remove('hidden')" class="btn btn-primary">
                    <i data-lucide="arrow-left-right" aria-hidden="true"></i> Record Payment
                </button>
            @endif
        </div>
    </div>

    <!-- Printable Style Invoice Card -->
    <div class="p-8 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm space-y-8">
        <!-- Top Metadata & Company Brand -->
        <div class="flex flex-col sm:flex-row justify-between items-start border-b border-slate-100 dark:border-slate-800 pb-8 gap-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    @if(($company->show_logo_on_documents ?? true) && !empty($company->logo_path))
                        <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="h-10 w-auto">
                    @else
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center font-bold" style="background-color: {{ $company->accent_color ?? '#2563eb' }}10; color: {{ $company->accent_color ?? '#2563eb' }};">
                            <i data-lucide="coins" class="w-4 h-4"></i>
                        </div>
                    @endif
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ $company->name ?? 'OpenBooks Enterprise' }}</h2>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $company->address ?? 'Corporate Headquarters' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $company->city ?? '' }}, {{ $company->country ?? '' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Email: {{ $company->email ?? 'billing@openbooks.sg' }}</p>
                @if(($company->show_phone_on_documents ?? true) && !empty($company->phone))
                    <p class="text-xs text-slate-500 dark:text-slate-400">Phone: {{ $company->phone }}</p>
                @endif
                @if(($company->show_tax_number_on_documents ?? true) && !empty($company->tax_number))
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">Tax ID: {{ $company->tax_number }}</p>
                @endif
            </div>

            <div class="text-left sm:text-right">
                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase">INVOICE</h3>
                <p class="text-xs font-mono font-bold mt-1" style="color: {{ $company->accent_color ?? '#2563eb' }};">{{ $invoice->invoice_number }}</p>
                @if($invoice->order_number ?? false)
                    <p class="text-xs text-slate-500 mt-0.5">PO #: {{ $invoice->order_number }}</p>
                @endif
            </div>
        </div>

        <!-- Billed To Details -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
            <div>
                <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Billed To</h4>
                <h5 class="text-sm font-bold text-slate-900 dark:text-white">{{ $invoice->customer->name ?? 'Valued Customer' }}</h5>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $invoice->customer->address ?? '' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $invoice->customer->city ?? '' }}, {{ $invoice->customer->country ?? '' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Email: {{ $invoice->customer->email ?? 'N/A' }}</p>
                @if($invoice->customer->phone ?? false)
                    <p class="text-xs text-slate-500 dark:text-slate-400">Phone: {{ $invoice->customer->phone }}</p>
                @endif
            </div>

            <div class="space-y-2 text-xs sm:text-right">
                <div class="flex justify-between sm:justify-end sm:gap-6">
                    <span class="text-slate-500 dark:text-slate-400">Invoice Date:</span>
                    <span class="text-slate-900 dark:text-white font-medium">{{ date('M d, Y', strtotime($invoice->invoice_date)) }}</span>
                </div>
                <div class="flex justify-between sm:justify-end sm:gap-6">
                    <span class="text-slate-500 dark:text-slate-400">Payment Due:</span>
                    <span class="text-slate-900 dark:text-white font-medium">{{ date('M d, Y', strtotime($invoice->due_date)) }}</span>
                </div>
                <div class="flex justify-between sm:justify-end sm:gap-6 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <span class="text-slate-700 dark:text-slate-300 font-bold">Total Due:</span>
                    <span class="font-extrabold text-sm" style="color: {{ $company->accent_color ?? '#2563eb' }};">{{ $currencySymbol }}{{ number_format($invoice->total - $invoice->paid_amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 font-semibold border-y border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-3 w-8">#</th>
                        <th class="py-3 px-3">Description</th>
                        <th class="py-3 px-3 text-center">Qty</th>
                        <th class="py-3 px-3 text-right">Unit Price</th>
                        <th class="py-3 px-3 text-right">Amount</th>
                        <th class="py-3 px-3 text-right">Tax</th>
                        <th class="py-3 px-3 text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @foreach($invoice->items as $idx => $it)
                        <tr>
                            <td class="py-3.5 px-3 text-slate-400 dark:text-slate-500">{{ $idx + 1 }}</td>
                            <td class="py-3.5 px-3">
                                <span class="font-medium text-slate-900 dark:text-white">{{ $it->name }}</span>
                                @if($it->description)
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">{{ $it->description }}</p>
                                @endif
                            </td>
                            <td class="py-3.5 px-3 text-center">{{ $it->quantity }}</td>
                            <td class="py-3.5 px-3 text-right">{{ $currencySymbol }}{{ number_format($it->price, 2) }}</td>
                            <td class="py-3.5 px-3 text-right">{{ $currencySymbol }}{{ number_format($it->quantity * $it->price, 2) }}</td>
                            <td class="py-3.5 px-3 text-right text-slate-500 dark:text-slate-400">
                                <span class="text-[10px]">{{ number_format($it->tax_rate, 0) }}%</span>
                                {{ $currencySymbol }}{{ number_format($it->tax_amount, 2) }}
                            </td>
                            <td class="py-3.5 px-3 text-right font-bold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($it->total, 2) }}</td>
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
                    <span>GST (9%):</span>
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
                    <span class="text-slate-900 dark:text-white font-extrabold">{{ $currencySymbol }}{{ number_format($invoice->total, 2) }}</span>
                </div>
                <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                    <span>Paid to Date:</span>
                    <span class="text-indigo-600 dark:text-indigo-400 font-semibold">{{ $currencySymbol }}{{ number_format($invoice->paid_amount, 2) }}</span>
                </div>
                <div class="flex justify-between text-base font-bold text-slate-900 dark:text-white pt-2 border-t border-slate-200 dark:border-slate-800">
                    <span>Balance Remaining:</span>
                    <span class="font-black" style="color: {{ $company->accent_color ?? '#2563eb' }};">{{ $currencySymbol }}{{ number_format($invoice->total - $invoice->paid_amount, 2) }}</span>
                </div>
                @if(($invoice->currency_code ?? 'SGD') !== ($company->currency_code ?? 'SGD'))
                <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400 pt-1">
                    <span>Base Currency ({{ $company->currency_code ?? 'SGD' }}) Equivalent:</span>
                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $company->currency_symbol ?? 'S$' }}{{ number_format($invoice->total * $invoice->exchange_rate, 2) }}</span>
                </div>
                <div class="flex justify-between text-[10px] text-slate-400 dark:text-slate-500">
                    <span>Exchange Rate:</span>
                    <span>1 {{ $invoice->currency_code }} = {{ number_format($invoice->exchange_rate, 6) }} {{ $company->currency_code ?? 'SGD' }}</span>
                </div>
                @endif
            </div>
        </div>

        @if($invoice->notes)
            <div class="pt-6 border-t border-slate-100 dark:border-slate-800">
                <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Notes & Payment Instructions</h4>
                <p class="text-xs text-slate-600 dark:text-slate-400 whitespace-pre-line">{{ $invoice->notes }}</p>
            </div>
        @endif

        {{-- PayNow QR Code Section --}}
        @if(!empty($company->paynow_id) && ($invoice->total - $invoice->paid_amount) > 0.01 && ($invoice->currency_code ?? 'SGD') === 'SGD')
            <div class="pt-6 border-t border-slate-100 dark:border-slate-800">
                <div class="flex flex-col sm:flex-row items-start gap-6">
                    <div class="flex-1">
                        <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Pay Instantly via PayNow</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">Scan this QR code with your banking app to pay instantly via PayNow.</p>
                        <div class="space-y-1 text-xs text-slate-600 dark:text-slate-400">
                            <p><span class="font-semibold">Payee:</span> {{ $company->paynow_name ?? $company->name }}</p>
                            <p><span class="font-semibold">{{ $company->paynow_id_type ?? 'UEN' }}:</span> {{ $company->paynow_id }}</p>
                            <p><span class="font-semibold">Amount:</span> <span class="font-bold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($invoice->total - $invoice->paid_amount, 2) }}</span></p>
                            <p><span class="font-semibold">Reference:</span> {{ $invoice->invoice_number }}</p>
                        </div>
                    </div>
                    <div class="bg-white p-3 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
                        <div id="paynowQr" class="w-40 h-40 flex items-center justify-center">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin text-slate-400"></i>
                        </div>
                        <p class="text-[9px] text-center text-slate-400 mt-1.5 font-medium">PayNow QR</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Record Payment Modal -->
    <div id="paymentModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Record Invoice Payment</h3>
                <button onclick="document.getElementById('paymentModal').classList.add('hidden')" class="btn-icon" aria-label="Close">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </div>

            <form action="{{ route('invoices.payment', $invoice->id) }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Payment Amount ({{ $currencySymbol }}) *</label>
                    <input type="number" name="amount" value="{{ $invoice->total - $invoice->paid_amount }}" step="0.01" min="0.01" max="{{ $invoice->total - $invoice->paid_amount }}" required
                        class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-indigo-600 shadow-sm">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Deposit To Bank Account *</label>
                    <select name="bank_account_id" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600 shadow-sm">
                        @foreach($bankAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->bank_name ?: 'Account' }} - Bal: {{ $currencySymbol }}{{ number_format($acc->current_balance, 2) }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Payment Date *</label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600 shadow-sm">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Payment Method *</label>
                        <select name="payment_method" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600 shadow-sm">
                            <option value="Bank Transfer">Bank Transfer (GIRO/FAST)</option>
                            <option value="PayNow">PayNow / QR</option>
                            <option value="Credit Card">Credit / Debit Card</option>
                            <option value="Cash">Cash</option>
                            <option value="Cheque">Cheque</option>
                            <option value="PayPal">PayPal / Stripe</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Reference / Transaction ID</label>
                    <input type="text" name="reference_number" placeholder="e.g. TXN-89218 or PayNow Ref"
                        class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600 shadow-sm">
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>

    <x-activity-timeline model-type="Invoice" :model-id="$invoice->id" />
</div>
@endsection

@if(!empty($company->paynow_id) && ($invoice->total - $invoice->paid_amount) > 0.01 && ($invoice->currency_code ?? 'SGD') === 'SGD')
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Build PayNow QR payload (SGQR / EMVCo format)
    const proxyType = '{{ $company->paynow_id_type ?? "UEN" }}' === 'MOBILE' ? '0' : '2';
    const proxyValue = '{{ $company->paynow_id }}';
    const amount = '{{ number_format($invoice->total - $invoice->paid_amount, 2, ".", "") }}';
    const reference = '{{ $invoice->invoice_number }}';
    const editable = '0'; // fixed amount

    // EMVCo TLV format for PayNow
    function tlv(tag, value) {
        return tag + String(value.length).padStart(2, '0') + value;
    }

    // Merchant Account Information (Tag 26)
    let mai = tlv('00', 'SG.PAYNOW');
    mai += tlv('01', proxyType);
    mai += tlv('02', proxyValue);
    mai += tlv('03', editable);
    if (amount !== '0.00') {
        // nothing extra needed here
    }

    let payload = '';
    payload += tlv('00', '01'); // Payload Format Indicator
    payload += tlv('01', '12'); // Point of Initiation (dynamic)
    payload += tlv('26', mai); // Merchant Account Info
    payload += tlv('52', '0000'); // Merchant Category Code
    payload += tlv('53', '702'); // Transaction Currency (SGD = 702)
    payload += tlv('54', amount); // Transaction Amount
    payload += tlv('58', 'SG'); // Country Code
    payload += tlv('59', '{{ addslashes($company->paynow_name ?? $company->name) }}'.substring(0, 25)); // Merchant Name
    payload += tlv('60', '{{ addslashes($company->city ?? "Singapore") }}'); // Merchant City

    // Additional Data (Tag 62)
    let addData = tlv('01', reference.substring(0, 25)); // Bill Number
    payload += tlv('62', addData);

    // CRC placeholder
    payload += '6304';

    // Calculate CRC-16/CCITT-FALSE
    function crc16(str) {
        let crc = 0xFFFF;
        for (let i = 0; i < str.length; i++) {
            crc ^= str.charCodeAt(i) << 8;
            for (let j = 0; j < 8; j++) {
                if (crc & 0x8000) {
                    crc = (crc << 1) ^ 0x1021;
                } else {
                    crc <<= 1;
                }
                crc &= 0xFFFF;
            }
        }
        return crc.toString(16).toUpperCase().padStart(4, '0');
    }

    payload = payload.slice(0, -4) + '6304' ; // ensure CRC tag is there
    // Recalculate with the 6304 prefix
    const fullPayload = payload.slice(0, payload.length);
    const crc = crc16(fullPayload);
    payload = payload.slice(0, -4) + '6304' + crc;

    // Generate QR
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
@endsection
@endif
