@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">System Settings</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Configure company identity, currency, financial year periods, tax rates, and category tags</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/30 text-xs font-semibold">
                <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                <span>Active Currency: {{ $company->currency_code ?? 'SGD' }} ({{ $company->currency_symbol ?? 'S$' }})</span>
            </span>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="sticky top-0 z-20 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm rounded-t-xl -mx-1 px-1 pt-1">
        <div class="flex gap-0 border-b border-slate-200 dark:border-slate-700 mb-6 overflow-x-auto" id="settingsTabBar">
            <button type="button" data-tab="company" class="settings-tab whitespace-nowrap px-4 py-2.5 text-xs font-semibold transition text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600">
                <i data-lucide="building" class="w-4 h-4 mr-1.5"></i>Company & Financial
            </button>
            <button type="button" data-tab="branding" class="settings-tab whitespace-nowrap px-4 py-2.5 text-xs font-semibold transition text-slate-500 dark:text-slate-400 border-b-2 border-transparent hover:text-slate-700 dark:hover:text-slate-300">
                <i data-lucide="palette" class="w-4 h-4 mr-1.5"></i>Branding & Templates
            </button>
            <button type="button" data-tab="ai" class="settings-tab whitespace-nowrap px-4 py-2.5 text-xs font-semibold transition text-slate-500 dark:text-slate-400 border-b-2 border-transparent hover:text-slate-700 dark:hover:text-slate-300">
                <i data-lucide="bot" class="w-4 h-4 mr-1.5"></i>AI Assistant
            </button>
            <button type="button" data-tab="taxes" class="settings-tab whitespace-nowrap px-4 py-2.5 text-xs font-semibold transition text-slate-500 dark:text-slate-400 border-b-2 border-transparent hover:text-slate-700 dark:hover:text-slate-300">
                <i data-lucide="percent" class="w-4 h-4 mr-1.5"></i>Tax Rates & Categories
            </button>
        </div>
    </div>

    <!-- Tab: Company & Financial Defaults -->
    <div id="tab-company" class="tab-content">
    <!-- Company Settings Form -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 mb-6 space-y-5">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Company & Financial Defaults</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Base configuration for ledger, invoicing, and tax reporting</p>
            </div>
            <span class="px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-500/30 text-[11px] font-semibold">
                <i data-lucide="calendar-check" class="w-4 h-4 mr-1"></i> FY: {{ $company->financial_year ?? 'April - March' }}
            </span>
        </div>

        <form action="{{ route('settings.company') }}" method="POST" class="space-y-4 text-xs">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Company Name *</label>
                    <input type="text" name="name" value="{{ old('name', $company->name ?? 'OpenBooks Enterprise') }}" required
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Corporate Email *</label>
                    <input type="email" name="email" value="{{ old('email', $company->email ?? 'billing@openbooks.sg') }}" required
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone', $company->phone ?? '+65 6789 0123') }}"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Default Currency Code *</label>
                    <input type="text" name="currency_code" value="{{ old('currency_code', $company->currency_code ?? 'SGD') }}" required
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white uppercase font-mono font-bold placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition" placeholder="SGD">
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">e.g. SGD, USD, EUR, GBP</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Currency Symbol *</label>
                    <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $company->currency_symbol ?? 'S$') }}" required
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-bold placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition" placeholder="S$">
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">e.g. S$ (Dollar), $, €, £</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Financial Year Period *</label>
                    <select name="financial_year" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white appearance-none cursor-pointer focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                        <option value="April - March" {{ ($company->financial_year ?? 'April - March') === 'April - March' ? 'selected' : '' }}>January - December (Standard Singapore FY)</option>
                        <option value="January - December" {{ ($company->financial_year ?? '') === 'January - December' ? 'selected' : '' }}>January - December (Calendar Year)</option>
                        <option value="July - June" {{ ($company->financial_year ?? '') === 'July - June' ? 'selected' : '' }}>July - June (AUS/NZ FY)</option>
                        <option value="October - September" {{ ($company->financial_year ?? '') === 'October - September' ? 'selected' : '' }}>October - September (US Fed FY)</option>
                    </select>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Used for annual profit/loss & tax summaries</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Financial Year Start Date</label>
                    <input type="text" name="financial_year_start" value="{{ old('financial_year_start', $company->financial_year_start ?? '04-01') }}"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-mono placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition" placeholder="04-01">
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">MM-DD format (04-01 for April 1)</p>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-2 mb-1.5">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">UEN / Tax ID</label>
                        @if(($company->tax_number ?? null))
                            <button type="button" class="btn-icon" data-copy="{{ $company->tax_number }}" title="Copy" aria-label="Copy"><i data-lucide="copy" aria-hidden="true"></i></button>
                        @endif
                    </div>
                    <input type="text" name="tax_number" value="{{ old('tax_number', $company->tax_number ?? '202312345A') }}"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white uppercase font-mono placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition" placeholder="202312345A">
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Appears on tax invoices & receipts</p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Registered Office Address</label>
                <input type="text" name="address" value="{{ old('address', $company->address ?? 'Tech Innovation Hub, MG Road') }}"
                    class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition mb-2">
                <div class="grid grid-cols-3 gap-3">
                    <input type="text" name="city" placeholder="City" value="{{ old('city', $company->city ?? 'Singapore') }}"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    <input type="text" name="state" placeholder="State" value="{{ old('state', $company->state ?? '') }}"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    <input type="text" name="country" placeholder="Country" value="{{ old('country', $company->country ?? 'Singapore') }}"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save" aria-hidden="true"></i> Save
                </button>
            </div>
        </form>
    </div>

    <!-- Currency Management Link -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <i data-lucide="coins" class="w-4 h-4"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Multi-Currency Exchange Rates</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Manage foreign currency rates for invoices and bills</p>
                </div>
            </div>
            <a href="{{ route('settings.currencies') }}" class="btn btn-secondary">
                <i data-lucide="arrow-right" aria-hidden="true"></i> Manage Currencies
            </a>
        </div>
    </div>
    </div><!-- /tab-company -->

    <!-- Tab: Branding & Document Templates -->
    <div id="tab-branding" class="tab-content hidden">
    <!-- Hidden Remove Logo Form -->
    @if($company->logo_path)
    <form id="removeLogoForm" action="{{ route('settings.logo.remove') }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
    @endif

    <!-- Branding & Document Templates -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 mb-6 space-y-5">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-600 flex items-center justify-center text-white shadow-sm">
                    <i data-lucide="palette" class="w-4 h-4"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Branding & Document Templates</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Logo, colours, numbering prefixes, and default content for invoices & bills</p>
                </div>
            </div>
        </div>

        <form action="{{ route('settings.branding') }}" method="POST" enctype="multipart/form-data" class="space-y-6 text-xs" id="brandingForm">
            @csrf

            {{-- A. Logo & Visual Identity --}}
            <div class="space-y-4">
                <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Logo & Visual Identity</h3>

                <div class="flex items-start gap-5">
                    @if($company->logo_path)
                        <div class="shrink-0">
                            <div class="w-24 h-24 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 flex items-center justify-center overflow-hidden">
                                <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="max-h-20 max-w-20 object-contain" id="currentLogoImg">
                            </div>
                        </div>
                    @endif
                    <div class="flex-1 space-y-3">
                        <label class="block w-full cursor-pointer">
                            <div class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl px-4 py-5 text-center hover:border-indigo-400 dark:hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-500/5 transition">
                                <i data-lucide="cloud-upload" class="w-5 h-5 text-slate-400 dark:text-slate-500 mb-1.5 block"></i>
                                <span class="text-slate-600 dark:text-slate-400 font-medium">Click to upload logo</span>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">PNG, JPG, or SVG (max 2MB)</p>
                            </div>
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/svg+xml" class="hidden" onchange="previewBrandingLogo(this)">
                        </label>
                        <div id="brandingLogoPreviewContainer" class="hidden">
                            <div class="flex items-center gap-3 p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/30">
                                <img id="brandingLogoPreviewImg" src="" alt="Preview" class="h-10 w-10 object-contain rounded-lg">
                                <span id="brandingLogoPreviewName" class="text-xs text-indigo-700 dark:text-indigo-300 font-medium truncate"></span>
                            </div>
                        </div>
                        @if($company->logo_path)
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="document.getElementById('removeLogoForm').submit()" class="btn btn-danger-text">
                                    Remove current logo
                                </button>
                            </div>
                        @endif
                        @error('logo')
                            <p class="text-red-500 text-[11px] mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Accent colour --}}
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Accent Colour</label>
                    <div class="flex items-center gap-3">
                        <input type="color" id="accentColorPicker" value="{{ old('accent_color', $company->accent_color ?? '#2563eb') }}"
                            class="w-9 h-9 rounded-lg border border-slate-300 dark:border-slate-600 cursor-pointer p-0.5 bg-white dark:bg-slate-800"
                            onchange="document.getElementById('accentColorText').value=this.value; updateBrandingPreview();">
                        <input type="text" name="accent_color" id="accentColorText" value="{{ old('accent_color', $company->accent_color ?? '#2563eb') }}"
                            maxlength="20" placeholder="#2563eb"
                            class="w-28 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-mono placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                            oninput="document.getElementById('accentColorPicker').value=this.value; updateBrandingPreview();">
                        <span class="text-[10px] text-slate-400 dark:text-slate-500">Used for headers on printed documents</span>
                    </div>
                </div>

                {{-- Toggles --}}
                <div class="space-y-3 pt-1">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="show_logo_on_documents" value="1" {{ old('show_logo_on_documents', $company->show_logo_on_documents ?? true) ? 'checked' : '' }} class="sr-only peer" onchange="updateBrandingPreview()">
                        <div class="w-9 h-5 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                        <span class="ml-2.5 text-xs font-medium text-slate-700 dark:text-slate-300">Show logo on printed documents</span>
                    </label>
                    <br>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="show_tax_number_on_documents" value="1" {{ old('show_tax_number_on_documents', $company->show_tax_number_on_documents ?? true) ? 'checked' : '' }} class="sr-only peer" onchange="updateBrandingPreview()">
                        <div class="w-9 h-5 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                        <span class="ml-2.5 text-xs font-medium text-slate-700 dark:text-slate-300">Show UEN/Tax ID on documents</span>
                    </label>
                    <br>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="show_phone_on_documents" value="1" {{ old('show_phone_on_documents', $company->show_phone_on_documents ?? true) ? 'checked' : '' }} class="sr-only peer" onchange="updateBrandingPreview()">
                        <div class="w-9 h-5 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                        <span class="ml-2.5 text-xs font-medium text-slate-700 dark:text-slate-300">Show phone number on documents</span>
                    </label>
                </div>
            </div>

            <hr class="border-slate-200 dark:border-slate-700">

            {{-- B. Document Numbering --}}
            <div class="space-y-4">
                <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Document Numbering</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Invoice Prefix</label>
                        <input type="text" name="invoice_prefix" id="invoicePrefixInput" value="{{ old('invoice_prefix', $company->invoice_prefix ?? 'INV') }}"
                            maxlength="20" placeholder="INV"
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-mono uppercase placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                            oninput="updatePrefixPreview()">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Bill Prefix</label>
                        <input type="text" name="bill_prefix" id="billPrefixInput" value="{{ old('bill_prefix', $company->bill_prefix ?? 'BILL') }}"
                            maxlength="20" placeholder="BILL"
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-mono uppercase placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                            oninput="updatePrefixPreview()">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Credit Note Prefix</label>
                        <input type="text" name="credit_note_prefix" id="cnPrefixInput" value="{{ old('credit_note_prefix', $company->credit_note_prefix ?? 'CN') }}"
                            maxlength="20" placeholder="CN"
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-mono uppercase placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                            oninput="updatePrefixPreview()">
                    </div>
                </div>
                <div class="flex items-center gap-4 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium uppercase tracking-wider">Preview</span>
                    <span id="prefixPreviewInv" class="px-2 py-0.5 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 font-mono font-bold text-[11px] border border-indigo-200 dark:border-indigo-500/30">INV-{{ date('Y') }}-001</span>
                    <span id="prefixPreviewBill" class="px-2 py-0.5 rounded-lg bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300 font-mono font-bold text-[11px] border border-amber-200 dark:border-amber-500/30">BILL-{{ date('Y') }}-001</span>
                    <span id="prefixPreviewCn" class="px-2 py-0.5 rounded-lg bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-300 font-mono font-bold text-[11px] border border-rose-200 dark:border-rose-500/30">CN-{{ date('Y') }}-001</span>
                </div>
            </div>

            <hr class="border-slate-200 dark:border-slate-700">

            {{-- C. Default Document Content --}}
            <div class="space-y-4">
                <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Default Document Content</h3>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Default Payment Terms</label>
                    <textarea name="default_payment_terms" id="paymentTermsInput" rows="2"
                        placeholder="Payment due within 30 days of invoice date."
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition resize-none"
                        oninput="updateBrandingPreview()">{{ old('default_payment_terms', $company->default_payment_terms ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Default Payment Notes / Instructions</label>
                    <textarea name="default_payment_notes" id="paymentNotesInput" rows="3"
                        placeholder="Bank: DBS Bank Ltd&#10;Account: 012-345678-9&#10;PayNow UEN: 202312345A"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition resize-none"
                        oninput="updateBrandingPreview()">{{ old('default_payment_notes', $company->default_payment_notes ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Invoice Footer Text</label>
                    <textarea name="invoice_footer" id="invoiceFooterInput" rows="2"
                        placeholder="Thank you for your business. This is a computer-generated document."
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition resize-none"
                        oninput="updateBrandingPreview()">{{ old('invoice_footer', $company->invoice_footer ?? '') }}</textarea>
                </div>
            </div>

            <hr class="border-slate-200 dark:border-slate-700">

            {{-- D. Live Preview --}}
            <div class="space-y-3">
                <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Document Preview</h3>
                <div class="rounded-xl border border-slate-200 dark:border-slate-300 bg-white p-5 shadow-sm" id="brandingPreviewCard">
                    {{-- Header bar --}}
                    <div id="previewHeader" class="rounded-lg px-4 py-3 mb-4 flex items-center justify-between" style="background-color: {{ $company->accent_color ?? '#2563eb' }};">
                        <div class="flex items-center gap-3">
                            <div id="previewLogoWrap" class="{{ ($company->show_logo_on_documents ?? true) ? '' : 'hidden' }}">
                                @if($company->logo_path)
                                    <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo" class="h-8 w-8 object-contain rounded bg-white/90 p-0.5" id="previewLogoImg">
                                @else
                                    <div class="h-8 w-8 rounded bg-white/20 flex items-center justify-center text-white text-[10px] font-bold" id="previewLogoImg">LOGO</div>
                                @endif
                            </div>
                            <div>
                                <div class="text-white text-xs font-bold" id="previewCompanyName">{{ $company->name ?? 'Your Company' }}</div>
                                <div class="text-white/70 text-[10px]" id="previewCompanyDetails">
                                    <span id="previewTaxNum" class="{{ ($company->show_tax_number_on_documents ?? true) ? '' : 'hidden' }}">UEN: {{ $company->tax_number ?? '202312345A' }}</span>
                                    <span id="previewPhoneSep" class="{{ (($company->show_tax_number_on_documents ?? true) && ($company->show_phone_on_documents ?? true)) ? '' : 'hidden' }}"> &middot; </span>
                                    <span id="previewPhone" class="{{ ($company->show_phone_on_documents ?? true) ? '' : 'hidden' }}">{{ $company->phone ?? '+65 6789 0123' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-white text-right">
                            <div class="text-[10px] font-bold uppercase tracking-wider">INVOICE</div>
                            <div class="text-[10px] text-white/70 font-mono" id="previewDocNumber">{{ ($company->invoice_prefix ?? 'INV') }}-{{ date('Y') }}-001</div>
                        </div>
                    </div>
                    {{-- Body skeleton --}}
                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between text-[10px] text-slate-500">
                            <span>Bill To: <span class="text-slate-800 font-medium">Sample Customer Pte Ltd</span></span>
                            <span>Date: {{ date('d M Y') }}</span>
                        </div>
                        <table class="w-full text-[10px]">
                            <thead><tr class="border-b border-slate-200"><th class="text-left py-1 text-slate-500 font-medium">Description</th><th class="text-right py-1 text-slate-500 font-medium">Amount</th></tr></thead>
                            <tbody>
                                <tr class="border-b border-slate-100"><td class="py-1.5 text-slate-700">Professional Services</td><td class="py-1.5 text-right text-slate-700 font-mono">{{ $company->currency_symbol ?? 'S$' }}1,500.00</td></tr>
                                <tr class="border-b border-slate-100"><td class="py-1.5 text-slate-700">Consultation Fee</td><td class="py-1.5 text-right text-slate-700 font-mono">{{ $company->currency_symbol ?? 'S$' }}500.00</td></tr>
                            </tbody>
                            <tfoot><tr><td class="pt-2 text-right font-bold text-slate-800" colspan="2">Total: {{ $company->currency_symbol ?? 'S$' }}2,000.00</td></tr></tfoot>
                        </table>
                    </div>
                    {{-- Payment terms --}}
                    <div id="previewPaymentTerms" class="text-[10px] text-slate-500 mb-1">{{ $company->default_payment_terms ?? 'Payment due within 30 days of invoice date.' }}</div>
                    <div id="previewPaymentNotes" class="text-[10px] text-slate-400 whitespace-pre-line mb-3">{{ $company->default_payment_notes ?? '' }}</div>
                    {{-- Footer --}}
                    <div class="border-t border-slate-200 pt-2">
                        <div id="previewFooter" class="text-[9px] text-slate-400 text-center italic">{{ $company->invoice_footer ?? 'Thank you for your business.' }}</div>
                    </div>
                </div>
            </div>

            <hr class="border-slate-200 dark:border-slate-700">

            {{-- E. PayNow QR Configuration --}}
            <div class="space-y-4">
                <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="qr-code" class="w-4 h-4 text-indigo-500"></i> PayNow QR Code (Singapore)
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Enable PayNow QR codes on invoices so clients can scan and pay instantly. QR codes appear on invoice detail, print, and public portal pages.</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">PayNow ID Type</label>
                        <select name="paynow_id_type" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                            <option value="UEN" {{ ($company->paynow_id_type ?? 'UEN') === 'UEN' ? 'selected' : '' }}>UEN (Business)</option>
                            <option value="MOBILE" {{ ($company->paynow_id_type ?? '') === 'MOBILE' ? 'selected' : '' }}>Mobile Number</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">PayNow ID (UEN or Mobile)</label>
                        <input type="text" name="paynow_id" value="{{ old('paynow_id', $company->paynow_id ?? '') }}"
                            placeholder="e.g. 202312345A or +6591234567"
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-mono placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Payee Display Name</label>
                        <input type="text" name="paynow_name" value="{{ old('paynow_name', $company->paynow_name ?? '') }}"
                            placeholder="e.g. My Company Pte Ltd"
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                </div>
                @if(!empty($company->paynow_id))
                    <div class="flex items-center gap-2 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                        <span class="text-xs text-emerald-700 dark:text-emerald-300 font-medium">PayNow QR is active — QR codes will appear on all SGD invoices with outstanding balance.</span>
                    </div>
                @else
                    <div class="flex items-center gap-2 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                        <i data-lucide="info" class="w-4 h-4 text-slate-400"></i>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Enter your PayNow UEN or mobile number to enable QR codes on invoices.</span>
                    </div>
                @endif
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save" aria-hidden="true"></i> Save
                </button>
            </div>
        </form>
    </div>
    </div><!-- /tab-branding -->

    <!-- Tab: AI Assistant -->
    <div id="tab-ai" class="tab-content hidden">
    <!-- AI Assistant & Voice Engine (NVIDIA NIM) Settings -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 mb-6 space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-700 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white shadow-sm">
                    <i data-lucide="bot" class="w-4 h-4"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-1">AI Assistant & Voice Engine (NVIDIA NIM)</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Configure your personal NVIDIA API key for voice chat and autonomous accounting actions</p>
                </div>
            </div>
            <div>
                @if(!empty($company->nvidia_api_key))
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/30 text-[11px] font-semibold">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                        <span>AI Configured & Active</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30 text-[11px] font-semibold">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span>API Key Not Configured</span>
                    </span>
                @endif
            </div>
        </div>

        <form action="{{ route('settings.ai') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">NVIDIA NIM API Key</label>
                    <a href="https://build.nvidia.com/settings/api-keys" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 text-[11px] font-semibold hover:underline inline-flex items-center gap-1">
                        Get a free key <i data-lucide="external-link" class="w-3 h-3"></i>
                    </a>
                </div>
                <div class="relative">
                    <input type="password" id="nvidiaApiKeyInput" name="nvidia_api_key"
                        value="{{ old('nvidia_api_key', $company->nvidia_api_key ?? '') }}"
                        placeholder="Enter your personal API key (e.g. nvapi-...)"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-mono placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition pr-20">
                    <button type="button" onclick="toggleApiKeyVisibility()" class="absolute right-3 top-2 text-xs text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white font-semibold transition">
                        <span id="apiKeyVisibilityText">Show</span>
                    </button>
                </div>
                <p class="text-[10px] text-slate-500 dark:text-slate-500 mt-1.5">
                    No default key is hardcoded. Enter your key above to enable autonomous bill creation, invoicing, balance checks, and voice commands.
                </p>
                <div class="mt-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 p-3.5 space-y-2">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <i data-lucide="key-round" class="w-3.5 h-3.5 text-indigo-500"></i> Where &amp; how to get a free key
                    </p>
                    <ol class="list-decimal list-inside space-y-1 text-[11px] text-slate-600 dark:text-slate-400">
                        <li>Go to <a href="https://build.nvidia.com" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">build.nvidia.com</a> and create a free account (or sign in).</li>
                        <li>Open the <span class="font-semibold">API Keys</span> page from your avatar menu — direct link: <a href="https://build.nvidia.com/settings/api-keys" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">build.nvidia.com/settings/api-keys</a>.</li>
                        <li>Click <span class="font-semibold">Generate API Key</span> and copy it — keys start with <span class="font-mono">nvapi-</span>.</li>
                        <li>Paste it in the field above and press <span class="font-semibold">Save AI Settings</span>. NVIDIA includes free inference credits with every account.</li>
                    </ol>
                    <p class="text-[10px] text-slate-500 dark:text-slate-500">Your key is stored in your own company settings and is sent only to NVIDIA when AI features run — never anywhere else.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">AI LLM Model</label>
                    <select name="nvidia_model" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white appearance-none cursor-pointer focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                        <option value="meta/llama-3.2-11b-vision-instruct" {{ ($company->nvidia_model ?? 'meta/llama-3.2-11b-vision-instruct') === 'meta/llama-3.2-11b-vision-instruct' ? 'selected' : '' }}>meta/llama-3.2-11b-vision-instruct (Fast & Free - Recommended)</option>
                        <option value="meta/llama-3.2-90b-vision-instruct" {{ ($company->nvidia_model ?? '') === 'meta/llama-3.2-90b-vision-instruct' ? 'selected' : '' }}>meta/llama-3.2-90b-vision-instruct (Large & Powerful)</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn btn-primary w-full">
                        <i data-lucide="save" aria-hidden="true"></i> Save
                    </button>
                </div>
            </div>
        </form>
    </div>
    </div><!-- /tab-ai -->

    <!-- Tab: Tax Rates & Categories -->
    <div id="tab-taxes" class="tab-content hidden">
    <!-- Taxes & Categories Dual Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Tax Rates -->
        <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Tax Rates (GST / VAT)</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-5">Manage applicable tax rates for invoices and bills</p>
            </div>
            <div class="space-y-2 text-xs">
                @foreach($taxes as $t)
                    <div class="flex justify-between items-center p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ $t->name }}</span>
                        <span class="px-2 py-0.5 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 font-bold border border-emerald-200 dark:border-emerald-500/30">{{ $t->rate }}%</span>
                    </div>
                @endforeach
            </div>

            <form action="{{ route('settings.taxes') }}" method="POST" class="pt-3 border-t border-slate-200 dark:border-slate-700 space-y-3 text-xs">
                @csrf
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="name" placeholder="Tax Name (e.g. GST 9%)" required
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    <input type="number" name="rate" placeholder="Rate %" step="0.1" required
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
                <input type="hidden" name="type" value="percent">
                <button type="submit" class="btn btn-secondary w-full"><i data-lucide="plus" aria-hidden="true"></i> Add Tax Rate</button>
            </form>
        </div>

        <!-- Categories -->
        <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Chart of Categories</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-5">Income and expense categories for transaction classification</p>
            </div>
            <div class="space-y-2 text-xs max-h-48 overflow-y-auto pr-1">
                @foreach($categories as $cat)
                    <div class="flex justify-between items-center p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ $cat->name }}</span>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] uppercase font-bold {{ $cat->type === 'income' ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/30' : 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30' }}">
                            {{ $cat->type }}
                        </span>
                    </div>
                @endforeach
            </div>

            <form action="{{ route('settings.categories') }}" method="POST" class="pt-3 border-t border-slate-200 dark:border-slate-700 space-y-3 text-xs">
                @csrf
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="name" placeholder="Category Name" required
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    <select name="type" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white appearance-none cursor-pointer focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary w-full"><i data-lucide="plus" aria-hidden="true"></i> Add Category</button>
            </form>
        </div>
    </div>
    </div><!-- /tab-taxes -->

    <!-- Quick Links: Email, Security & Backup -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
        <a href="{{ route('settings.smtp') }}" class="group bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 flex items-start gap-4 hover:border-indigo-300 dark:hover:border-indigo-600 transition">
            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0 group-hover:scale-110 transition-transform">
                <i data-lucide="mail" class="w-4 h-4"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">SMTP & Email Settings</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Configure outgoing email server, payment reminders, and automated invoice delivery.</p>
                @if(!empty($company->smtp_host))
                    <span class="inline-flex items-center gap-1 mt-2 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400"><i data-lucide="check-circle" class="w-4 h-4"></i> Configured</span>
                @else
                    <span class="inline-flex items-center gap-1 mt-2 text-[10px] font-semibold text-amber-600 dark:text-amber-400"><i data-lucide="alert-circle" class="w-4 h-4"></i> Not configured</span>
                @endif
            </div>
        </a>

        <a href="{{ route('settings.backups') }}" class="group bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 flex items-start gap-4 hover:border-indigo-300 dark:hover:border-indigo-600 transition">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0 group-hover:scale-110 transition-transform">
                <i data-lucide="database" class="w-4 h-4"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Backup & Restore</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Create database backups, download, and restore from previous snapshots.</p>
                <span class="inline-flex items-center gap-1 mt-2 text-[10px] font-semibold text-indigo-600 dark:text-indigo-400"><i data-lucide="history" class="w-4 h-4"></i> Daily at 02:00</span>
            </div>
        </a>

        <a href="{{ route('2fa.setup') }}" class="group bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 flex items-start gap-4 hover:border-indigo-300 dark:hover:border-indigo-600 transition">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0 group-hover:scale-110 transition-transform">
                <i data-lucide="shield" class="w-4 h-4"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Two-Factor Authentication</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Add an extra layer of security to your account with TOTP-based 2FA.</p>
                @if(Auth::user()->two_factor_enabled)
                    <span class="inline-flex items-center gap-1 mt-2 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400"><i data-lucide="check-circle" class="w-4 h-4"></i> Enabled</span>
                @else
                    <span class="inline-flex items-center gap-1 mt-2 text-[10px] font-semibold text-slate-500 dark:text-slate-400"><i data-lucide="minus-circle" class="w-4 h-4"></i> Disabled</span>
                @endif
            </div>
        </a>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function previewBrandingLogo(input) {
        const container = document.getElementById('brandingLogoPreviewContainer');
        const img = document.getElementById('brandingLogoPreviewImg');
        const name = document.getElementById('brandingLogoPreviewName');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                name.textContent = input.files[0].name;
                container.classList.remove('hidden');
                // Also update the live preview logo
                const previewLogoImg = document.getElementById('previewLogoImg');
                if (previewLogoImg) {
                    if (previewLogoImg.tagName === 'IMG') {
                        previewLogoImg.src = e.target.result;
                    } else {
                        const newImg = document.createElement('img');
                        newImg.src = e.target.result;
                        newImg.alt = 'Logo';
                        newImg.id = 'previewLogoImg';
                        newImg.className = 'h-8 w-8 object-contain rounded bg-white/90 p-0.5';
                        previewLogoImg.replaceWith(newImg);
                    }
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function toggleApiKeyVisibility() {
        const input = document.getElementById('nvidiaApiKeyInput');
        const txt = document.getElementById('apiKeyVisibilityText');
        if (input.type === 'password') {
            input.type = 'text';
            txt.innerText = 'Hide';
        } else {
            input.type = 'password';
            txt.innerText = 'Show';
        }
    }

    /* ---- Branding live-preview helpers ---- */
    const currentYear = new Date().getFullYear();

    function updatePrefixPreview() {
        const inv = (document.getElementById('invoicePrefixInput').value || 'INV').toUpperCase();
        const bill = (document.getElementById('billPrefixInput').value || 'BILL').toUpperCase();
        const cn = (document.getElementById('cnPrefixInput').value || 'CN').toUpperCase();
        document.getElementById('prefixPreviewInv').textContent = inv + '-' + currentYear + '-001';
        document.getElementById('prefixPreviewBill').textContent = bill + '-' + currentYear + '-001';
        document.getElementById('prefixPreviewCn').textContent = cn + '-' + currentYear + '-001';
        // Also update the mini invoice preview doc number
        const docNum = document.getElementById('previewDocNumber');
        if (docNum) docNum.textContent = inv + '-' + currentYear + '-001';
    }

    function updateBrandingPreview() {
        const form = document.getElementById('brandingForm');
        if (!form) return;

        // Accent colour
        const accentColor = document.getElementById('accentColorText').value || '#2563eb';
        const header = document.getElementById('previewHeader');
        if (header) header.style.backgroundColor = accentColor;

        // Toggles
        const showLogo = form.querySelector('input[name="show_logo_on_documents"]').checked;
        const showTax = form.querySelector('input[name="show_tax_number_on_documents"]').checked;
        const showPhone = form.querySelector('input[name="show_phone_on_documents"]').checked;

        const logoWrap = document.getElementById('previewLogoWrap');
        const taxNum = document.getElementById('previewTaxNum');
        const phone = document.getElementById('previewPhone');
        const phoneSep = document.getElementById('previewPhoneSep');

        if (logoWrap) logoWrap.classList.toggle('hidden', !showLogo);
        if (taxNum) taxNum.classList.toggle('hidden', !showTax);
        if (phone) phone.classList.toggle('hidden', !showPhone);
        if (phoneSep) phoneSep.classList.toggle('hidden', !(showTax && showPhone));

        // Text areas
        const termsEl = document.getElementById('previewPaymentTerms');
        const notesEl = document.getElementById('previewPaymentNotes');
        const footerEl = document.getElementById('previewFooter');

        const terms = document.getElementById('paymentTermsInput').value;
        const notes = document.getElementById('paymentNotesInput').value;
        const footer = document.getElementById('invoiceFooterInput').value;

        if (termsEl) termsEl.textContent = terms || 'Payment due within 30 days of invoice date.';
        if (notesEl) notesEl.textContent = notes || '';
        if (footerEl) footerEl.textContent = footer || 'Thank you for your business.';
    }

    // Initialise prefix preview on load
    document.addEventListener('DOMContentLoaded', function() {
        updatePrefixPreview();

        // Tab navigation
        const tabBar = document.getElementById('settingsTabBar');
        if (tabBar) {
            const tabs = tabBar.querySelectorAll('.settings-tab');
            const activeClass = 'text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600';
            const inactiveClass = 'text-slate-500 dark:text-slate-400 border-b-2 border-transparent hover:text-slate-700 dark:hover:text-slate-300';

            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    // Deactivate all tabs
                    tabs.forEach(function(t) {
                        t.className = 'settings-tab whitespace-nowrap px-4 py-2.5 text-xs font-semibold transition ' + inactiveClass;
                    });
                    // Activate clicked tab
                    tab.className = 'settings-tab whitespace-nowrap px-4 py-2.5 text-xs font-semibold transition ' + activeClass;

                    // Hide all tab content, show selected
                    document.querySelectorAll('.tab-content').forEach(function(panel) {
                        panel.classList.add('hidden');
                    });
                    var target = document.getElementById('tab-' + tab.getAttribute('data-tab'));
                    if (target) {
                        target.classList.remove('hidden');
                    }
                });
            });
        }
    });
</script>
@endsection
