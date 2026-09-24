@extends('layouts.app')

@section('title', 'New Invoice')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <x-breadcrumbs :items="[['label' => 'Invoices', 'url' => route('invoices.index')], ['label' => 'New Invoice']]" />
    <x-sticky-form-bar :cancel-url="route('invoices.index')">
    <x-slot:title>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Create New Invoice</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Issue an itemized customer invoice with catalog products, services, and taxes</p>
        </div>
    </x-slot:title>
</x-sticky-form-bar>

    <!-- Datalist for Fast Item Autocomplete -->
    <datalist id="catalogItemsList">
        @foreach($items as $it)
            <option value="{{ $it->name }}" data-price="{{ $it->sale_price }}" data-tax-rate="{{ $it->tax->rate ?? 0 }}">
                {{ $it->sku ? '[' . $it->sku . '] ' : '' }}{{ $it->name }} &bull; {{ $currencySymbol }}{{ number_format($it->sale_price, 2) }}
            </option>
        @endforeach
    </datalist>

    <form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm" class="space-y-6">
        @csrf

        <!-- Top Details Card -->
        <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-5">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Invoice Details</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-5">Customer, reference numbers, and dates</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="customer_id" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Customer <span aria-hidden="true">*</span></label>
                    <select data-combobox name="customer_id" id="customer_id" required aria-required="true"
                        @error('customer_id') aria-invalid="true" aria-describedby="customer_id-error" @enderror
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white appearance-none cursor-pointer focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                        <option value="">Select Customer</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->email ?: 'No email' }})</option>
                        @endforeach
                    </select>
                    @error('customer_id')<p id="customer_id-error" role="alert" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="invoice_number" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Invoice Number <span aria-hidden="true">*</span></label>
                    <input type="text" name="invoice_number" id="invoice_number" value="{{ old('invoice_number', $nextInvoiceNumber) }}" required aria-required="true"
                        @error('invoice_number') aria-invalid="true" aria-describedby="invoice_number-error" @enderror
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-mono font-bold placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    @error('invoice_number')<p id="invoice_number-error" role="alert" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="order_number" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Order / PO Reference</label>
                    <input type="text" name="order_number" id="order_number" value="{{ old('order_number') }}" placeholder="e.g. PO-9821"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    @error('order_number')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                <div>
                    <label for="invoice_date" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Invoice Date <span aria-hidden="true">*</span></label>
                    <input type="date" name="invoice_date" id="invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}" required aria-required="true"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    @error('invoice_date')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="due_date" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Due Date <span aria-hidden="true">*</span></label>
                    <input type="date" name="due_date" id="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+15 days'))) }}" required aria-required="true"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    @error('due_date')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>

            <!-- Currency Selection -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                <div>
                    <label for="currencyCodeSelect" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Currency</label>
                    <select name="currency_code" id="currencyCodeSelect" onchange="onCurrencyChange()" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white appearance-none cursor-pointer focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                        <option value="SGD" data-rate="1.000000" data-symbol="{{ $company->currency_symbol ?? 'S$' }}">{{ $company->currency_code ?? 'SGD' }} ({{ $company->currency_symbol ?? 'S$' }}) - Base Currency</option>
                        @foreach($currencies as $cur)
                            <option value="{{ $cur->currency_code }}" data-rate="{{ $cur->exchange_rate }}" data-symbol="{{ $cur->currency_symbol }}" {{ old('currency_code') == $cur->currency_code ? 'selected' : '' }}>
                                {{ $cur->currency_code }} ({{ $cur->currency_symbol }}) - {{ $cur->currency_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('currency_code')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                </div>
                <div id="exchangeRateField" class="hidden">
                    <label for="exchangeRateInput" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Exchange Rate (to {{ $company->currency_code ?? 'SGD' }})</label>
                    <input type="number" name="exchange_rate" id="exchangeRateInput" value="{{ old('exchange_rate', '1.000000') }}" step="0.000001" min="0.000001"
                        aria-describedby="exchangeRateHelp" oninput="calcTotals()"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-mono font-bold placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    @error('exchange_rate')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    <p id="exchangeRateHelp" class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">1 foreign unit = X {{ $company->currency_code ?? 'SGD' }}</p>
                </div>
                <div id="baseEquivalentField" class="hidden flex items-end">
                    <div class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2">
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mb-0.5">Base Currency Equivalent</p>
                        <p id="baseEquivalentDisplay" class="text-xs font-bold text-indigo-700 dark:text-indigo-400">{{ $company->currency_symbol ?? 'S$' }}0.00</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items Card -->
        <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Line Items</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Pick catalog items or type custom services with real-time totals</p>
                </div>
                <button type="button" onclick="addRow()" class="inline-flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-bold px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/30 transition">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Add Item</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs" id="itemsTable">
                    <caption class="sr-only">Invoice line items - product or service, quantity, price, tax, and line total</caption>
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th scope="col" class="py-2.5 px-3 min-w-[280px]">Product / Service (Catalog & Custom)</th>
                            <th scope="col" class="py-2.5 px-3 w-24">Qty</th>
                            <th scope="col" class="py-2.5 px-3 w-32">Price ({{ $currencySymbol }})</th>
                            <th scope="col" class="py-2.5 px-3 w-32">Tax (GST)</th>
                            <th scope="col" class="py-2.5 px-3 w-32 text-right">Line Total</th>
                            <th scope="col" class="py-2.5 px-2 w-10 text-center"><span class="sr-only">Remove item</span></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody" class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr class="item-row">
                            <td class="py-2.5 px-3">
                                <div class="space-y-1.5">
                                    <select onchange="onCatalogItemSelect(this)" class="catalog-select w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-slate-700 dark:text-slate-300 text-xs font-medium focus:outline-none focus:border-indigo-500 appearance-none cursor-pointer">
                                        <option value="">-- Choose from Catalog (or type below) --</option>
                                        @foreach($items as $it)
                                            <option value="{{ $it->id }}"
                                                data-name="{{ $it->name }}"
                                                data-price="{{ $it->sale_price }}"
                                                data-tax-rate="{{ $it->tax->rate ?? 0 }}">
                                                {{ $it->name }} {{ $it->sku ? '(' . $it->sku . ')' : '' }} &bull; {{ $currencySymbol }}{{ number_format($it->sale_price, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="items[0][item_name]" list="catalogItemsList" required placeholder="Service or Product description"
                                        oninput="onItemNameInput(this)"
                                        class="item-name-input w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                                </div>
                            </td>
                            <td class="py-2.5 px-3 align-top pt-3">
                                <input type="number" name="items[0][quantity]" value="1" min="1" step="1" required oninput="calcTotals()"
                                    class="qty-input w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white font-semibold text-center focus:outline-none focus:border-indigo-500 transition">
                            </td>
                            <td class="py-2.5 px-3 align-top pt-3">
                                <input type="number" name="items[0][price]" value="0.00" min="0" step="0.01" required oninput="calcTotals()"
                                    class="price-input w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white font-bold focus:outline-none focus:border-indigo-500 transition">
                            </td>
                            <td class="py-2.5 px-3 align-top pt-3">
                                <select name="items[0][tax_rate]" onchange="calcTotals()" class="tax-input w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 appearance-none cursor-pointer transition">
                                    <option value="0">0% None</option>
                                    @foreach($taxes as $tx)
                                        <option value="{{ $tx->rate }}">{{ $tx->name }} ({{ $tx->rate }}%)</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-2.5 px-3 align-top pt-4 text-right font-extrabold text-slate-900 dark:text-white line-total">{{ $currencySymbol }}0.00</td>
                            <td class="py-2.5 px-2 align-top pt-3.5 text-center">
                                <button type="button" onclick="removeRow(this)" class="btn-icon btn-icon-danger" title="Remove line" aria-label="Remove line"><i data-lucide="trash-2" aria-hidden="true"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Financial Totals Block -->
            <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
                <div class="w-72 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-500 dark:text-slate-400">
                        <span>Subtotal:</span>
                        <span id="subtotalDisplay" class="text-slate-900 dark:text-white font-bold">{{ $currencySymbol }}0.00</span>
                    </div>
                    <div class="flex justify-between text-slate-500 dark:text-slate-400">
                        <span>Taxes (GST/VAT):</span>
                        <span id="taxDisplay" class="text-slate-900 dark:text-white font-bold">{{ $currencySymbol }}0.00</span>
                    </div>
                    <label for="invoice_discount" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Discount</label>
                    <div class="flex justify-between items-center">
                        <div class="w-28">
                            <input type="number" name="discount" id="invoice_discount" value="{{ old('discount', '0.00') }}" min="0" step="0.01" oninput="calcTotals()"
                                class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2 py-1 text-xs text-slate-900 dark:text-white text-right font-semibold focus:outline-none focus:border-indigo-500 transition">
                            @error('discount')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="flex justify-between text-base font-bold text-slate-900 dark:text-white pt-2 border-t border-slate-200 dark:border-slate-700">
                        <span>Total Payable:</span>
                        <span id="grandTotalDisplay" class="text-indigo-700 dark:text-indigo-400 font-black">{{ $currencySymbol }}0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes & Terms -->
        <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
            <div>
                <label for="invoice_notes" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Customer Notes / Payment Instructions</label>
                <textarea name="notes" id="invoice_notes" rows="2" placeholder="Bank details, PayNow/GIRO instructions, or thank you message..."
                    class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"></textarea>
                @error('notes')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
            </div>
        </div>

        <!-- Hidden status field -->
        <input type="hidden" name="status" id="invoiceStatusInput" value="sent">

        <!-- Submission Buttons -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('invoices.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" onclick="document.getElementById('invoiceStatusInput').value='draft'" class="btn btn-secondary">
                <i data-lucide="save" aria-hidden="true"></i> Save as Draft
            </button>
            <button type="submit" onclick="document.getElementById('invoiceStatusInput').value='sent'" id="primarySubmit" class="btn btn-primary">
                <i data-lucide="save" aria-hidden="true"></i> Save
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    let rowIndex = 1;
    const taxes = {!! json_encode($taxes) !!};
    const catalogItems = {!! json_encode($items) !!};
    const currencySymbol = {!! json_encode($currencySymbol) !!};

    function onCatalogItemSelect(selectElem) {
        const row = selectElem.closest('.item-row');
        const selectedOption = selectElem.options[selectElem.selectedIndex];
        if (!selectedOption || !selectedOption.value) return;

        const name = selectedOption.getAttribute('data-name');
        const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
        const taxRate = parseFloat(selectedOption.getAttribute('data-tax-rate')) || 0;

        const nameInput = row.querySelector('.item-name-input');
        const priceInput = row.querySelector('.price-input');
        const taxSelect = row.querySelector('.tax-input');

        if (nameInput) nameInput.value = name;
        if (priceInput) priceInput.value = price.toFixed(2);

        if (taxSelect) {
            let matched = false;
            for (let opt of taxSelect.options) {
                if (parseFloat(opt.value) === taxRate) {
                    opt.selected = true;
                    matched = true;
                    break;
                }
            }
            if (!matched && taxSelect.options.length > 0) {
                taxSelect.options[0].selected = true;
            }
        }

        calcTotals();
    }

    function onItemNameInput(inputElem) {
        const val = inputElem.value.trim().toLowerCase();
        const matchedItem = catalogItems.find(it => it.name.toLowerCase() === val || (it.sku && it.sku.toLowerCase() === val));
        if (matchedItem) {
            const row = inputElem.closest('.item-row');
            const priceInput = row.querySelector('.price-input');
            const taxSelect = row.querySelector('.tax-input');
            const catalogSelect = row.querySelector('.catalog-select');

            if (priceInput) priceInput.value = parseFloat(matchedItem.sale_price).toFixed(2);
            if (catalogSelect) catalogSelect.value = matchedItem.id;

            if (taxSelect && matchedItem.tax) {
                for (let opt of taxSelect.options) {
                    if (parseFloat(opt.value) === parseFloat(matchedItem.tax.rate)) {
                        opt.selected = true;
                        break;
                    }
                }
            }
            calcTotals();
        }
    }

    function addRow() {
        const tbody = document.getElementById('itemsBody');
        const tr = document.createElement('tr');
        tr.className = 'item-row';

        let taxOptions = '<option value="0">0% None</option>';
        taxes.forEach(t => {
            taxOptions += `<option value="${t.rate}">${t.name} (${t.rate}%)</option>`;
        });

        let itemOptions = '<option value="">-- Choose from Catalog (or type below) --</option>';
        catalogItems.forEach(it => {
            const priceStr = parseFloat(it.sale_price).toFixed(2);
            const skuStr = it.sku ? `(${it.sku}) ` : '';
            itemOptions += `<option value="${it.id}" data-name="${it.name}" data-price="${it.sale_price}" data-tax-rate="${it.tax ? it.tax.rate : 0}">${it.name} ${skuStr}&bull; ${currencySymbol}${priceStr}</option>`;
        });

        tr.innerHTML = `
            <td class="py-2.5 px-3">
                <div class="space-y-1.5">
                    <select onchange="onCatalogItemSelect(this)" class="catalog-select w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-slate-700 dark:text-slate-300 text-xs font-medium focus:outline-none focus:border-indigo-500 appearance-none cursor-pointer">
                        ${itemOptions}
                    </select>
                    <input type="text" name="items[${rowIndex}][item_name]" list="catalogItemsList" required placeholder="Service or Product description"
                        oninput="onItemNameInput(this)"
                        class="item-name-input w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
            </td>
            <td class="py-2.5 px-3 align-top pt-3">
                <input type="number" name="items[${rowIndex}][quantity]" value="1" min="1" step="1" required oninput="calcTotals()"
                    class="qty-input w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white font-semibold text-center focus:outline-none focus:border-indigo-500 transition">
            </td>
            <td class="py-2.5 px-3 align-top pt-3">
                <input type="number" name="items[${rowIndex}][price]" value="0.00" min="0" step="0.01" required oninput="calcTotals()"
                    class="price-input w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white font-bold focus:outline-none focus:border-indigo-500 transition">
            </td>
            <td class="py-2.5 px-3 align-top pt-3">
                <select name="items[${rowIndex}][tax_rate]" onchange="calcTotals()" class="tax-input w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 appearance-none cursor-pointer transition">
                    ${taxOptions}
                </select>
            </td>
            <td class="py-2.5 px-3 align-top pt-4 text-right font-extrabold text-slate-900 dark:text-white line-total">${currencySymbol}0.00</td>
            <td class="py-2.5 px-2 align-top pt-3.5 text-center">
                <button type="button" onclick="removeRow(this)" class="btn-icon btn-icon-danger" title="Remove line" aria-label="Remove line"><i data-lucide="trash-2" aria-hidden="true"></i></button>
            </td>
        `;
        tbody.appendChild(tr);
        lucide.createIcons();
        rowIndex++;
        calcTotals();
    }

    function removeRow(btn) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length > 1) {
            btn.closest('tr').remove();
            calcTotals();
        } else {
            alert('Invoice must contain at least one line item.');
        }
    }

    const baseCurrencySymbol = {!! json_encode($company->currency_symbol ?? 'S$') !!};
    const baseCurrencyCode = {!! json_encode($company->currency_code ?? 'SGD') !!};

    function onCurrencyChange() {
        const sel = document.getElementById('currencyCodeSelect');
        const opt = sel.options[sel.selectedIndex];
        const code = sel.value;
        const rate = parseFloat(opt.getAttribute('data-rate')) || 1;
        const rateField = document.getElementById('exchangeRateField');
        const eqField = document.getElementById('baseEquivalentField');
        const rateInput = document.getElementById('exchangeRateInput');

        if (code !== baseCurrencyCode) {
            rateField.classList.remove('hidden');
            eqField.classList.remove('hidden');
            rateInput.value = rate.toFixed(6);
        } else {
            rateField.classList.add('hidden');
            eqField.classList.add('hidden');
            rateInput.value = '1.000000';
        }
        calcTotals();
    }

    function calcTotals() {
        let subtotal = 0;
        let taxTotal = 0;
        const sel = document.getElementById('currencyCodeSelect');
        const opt = sel.options[sel.selectedIndex];
        const activeSym = (opt.getAttribute('data-symbol') || currencySymbol);

        document.querySelectorAll('.item-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const taxRate = parseFloat(row.querySelector('.tax-input').value) || 0;

            const lineSub = qty * price;
            const lineTax = (lineSub * taxRate) / 100;
            const lineTotal = lineSub + lineTax;

            row.querySelector('.line-total').innerText = activeSym + lineTotal.toFixed(2);

            subtotal += lineSub;
            taxTotal += lineTax;
        });

        const discount = parseFloat(document.getElementById('invoice_discount').value) || 0;
        const grandTotal = Math.max(0, subtotal + taxTotal - discount);

        document.getElementById('subtotalDisplay').innerText = activeSym + subtotal.toFixed(2);
        document.getElementById('taxDisplay').innerText = activeSym + taxTotal.toFixed(2);
        document.getElementById('grandTotalDisplay').innerText = activeSym + grandTotal.toFixed(2);

        // Base currency equivalent
        const exchangeRate = parseFloat(document.getElementById('exchangeRateInput').value) || 1;
        const baseEquiv = grandTotal * exchangeRate;
        const eqDisplay = document.getElementById('baseEquivalentDisplay');
        if (eqDisplay) {
            eqDisplay.innerText = baseCurrencySymbol + baseEquiv.toFixed(2);
        }
    }

    // Initialize currency state on page load
    onCurrencyChange();
    calcTotals();
</script>
@endsection
