@extends('layouts.app')

@section('title', 'GST Return (Form GST F5)')

@section('content')
<div class="space-y-6">
    {{-- Header + Report Subnav --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">GST Return (Form GST F5)</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Quarterly GST filing report for IRAS compliance</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('reports.profit_loss') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Profit & Loss</a>
            <a href="{{ route('reports.balance_sheet') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Balance Sheet</a>
            <a href="{{ route('reports.trial_balance') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Trial Balance</a>
            <a href="{{ route('reports.income_expense') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Monthly Cashflow</a>
            <a href="{{ route('reports.tax_summary') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">Tax Summary</a>
            <a href="{{ route('reports.ar_aging') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">AR Aging</a>
            <a href="{{ route('reports.ap_aging') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 shadow-sm transition">AP Aging</a>
            <a href="{{ route('reports.gst_f5') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/20 shadow-sm">GST F5</a>
        </div>
    </div>

    {{-- Quarter/Year Selector --}}
    <form method="GET" action="{{ route('reports.gst_f5') }}" class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm flex flex-wrap items-center gap-4 text-xs">
        <span class="text-slate-500 dark:text-slate-400 font-semibold">Filing Period:</span>
        <select name="quarter" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
            <option value="1" {{ $quarter == 1 ? 'selected' : '' }}>Q1 (Jan - Mar)</option>
            <option value="2" {{ $quarter == 2 ? 'selected' : '' }}>Q2 (Apr - Jun)</option>
            <option value="3" {{ $quarter == 3 ? 'selected' : '' }}>Q3 (Jul - Sep)</option>
            <option value="4" {{ $quarter == 4 ? 'selected' : '' }}>Q4 (Oct - Dec)</option>
        </select>
        <input type="number" name="year" value="{{ $year }}" min="2000" max="2099" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white w-24 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
        <button type="submit" class="btn btn-primary"><i data-lucide="refresh-cw" aria-hidden="true"></i> Update</button>
        <a href="{{ route('reports.gst_f5.export_csv', ['quarter' => $quarter, 'year' => $year]) }}" class="btn btn-secondary">
            <i data-lucide="file-spreadsheet" aria-hidden="true"></i> Export CSV
        </a>
        <button type="button" onclick="window.print()" class="btn btn-secondary">
            <i data-lucide="printer" aria-hidden="true"></i> Print
        </button>
    </form>

    {{-- GST F5 Form --}}
    <div class="p-8 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm max-w-3xl mx-auto space-y-6">
        {{-- Form Header --}}
        <div class="text-center border-b-2 border-slate-200 dark:border-slate-700 pb-5">
            <p class="text-[11px] text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] font-bold">Inland Revenue Authority of Singapore</p>
            <h2 class="text-lg font-black text-slate-900 dark:text-white mt-1">GOODS & SERVICES TAX RETURN</h2>
            <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 mt-0.5">Form GST F5</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 font-medium">
                For the period: <span class="text-slate-900 dark:text-white font-bold">{{ date('d M Y', strtotime($startDate)) }}</span>
                to <span class="text-slate-900 dark:text-white font-bold">{{ date('d M Y', strtotime($endDate)) }}</span>
            </p>
            <div class="mt-3 text-xs text-slate-500 dark:text-slate-400 space-y-0.5">
                <p>Company Name: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $company->name ?? 'Not Set' }}</span></p>
                <p>GST Registration No: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $company->tax_number ?? 'Not Set' }}</span></p>
            </div>
        </div>

        {{-- Section: SUPPLIES (Boxes 1-4) --}}
        <div class="space-y-1">
            <h3 class="text-[11px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider px-1 mb-2">Supplies</h3>
            <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
                {{-- Box 1 --}}
                <div class="flex items-center justify-between px-5 py-3.5 bg-white dark:bg-slate-900/60">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-[11px] font-black text-slate-600 dark:text-slate-400 shrink-0">1</span>
                        <span class="text-xs text-slate-700 dark:text-slate-300 font-medium">Total value of standard-rated supplies</span>
                    </div>
                    <span class="text-sm font-bold text-slate-900 dark:text-white font-mono tabular-nums">S$ {{ number_format($box1, 2) }}</span>
                </div>
                {{-- Box 2 --}}
                <div class="flex items-center justify-between px-5 py-3.5 bg-white dark:bg-slate-900/60">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-[11px] font-black text-slate-600 dark:text-slate-400 shrink-0">2</span>
                        <span class="text-xs text-slate-700 dark:text-slate-300 font-medium">Total value of zero-rated supplies</span>
                    </div>
                    <span class="text-sm font-bold text-slate-900 dark:text-white font-mono tabular-nums">S$ {{ number_format($box2, 2) }}</span>
                </div>
                {{-- Box 3 --}}
                <div class="flex items-center justify-between px-5 py-3.5 bg-white dark:bg-slate-900/60">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-[11px] font-black text-slate-600 dark:text-slate-400 shrink-0">3</span>
                        <span class="text-xs text-slate-700 dark:text-slate-300 font-medium">Total value of exempt supplies</span>
                    </div>
                    <span class="text-sm font-bold text-slate-900 dark:text-white font-mono tabular-nums">S$ {{ number_format($box3, 2) }}</span>
                </div>
                {{-- Box 4 --}}
                <div class="flex items-center justify-between px-5 py-3.5 bg-slate-50 dark:bg-slate-800/60 border-t-2 border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-500/20 text-[11px] font-black text-indigo-700 dark:text-indigo-400 shrink-0">4</span>
                        <span class="text-xs text-slate-900 dark:text-white font-bold">Total value of supplies (Box 1 + 2 + 3)</span>
                    </div>
                    <span class="text-sm font-extrabold text-slate-900 dark:text-white font-mono tabular-nums">S$ {{ number_format($box4, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Section: PURCHASES (Box 5) --}}
        <div class="space-y-1">
            <h3 class="text-[11px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider px-1 mb-2">Purchases</h3>
            <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3.5 bg-white dark:bg-slate-900/60">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-[11px] font-black text-slate-600 dark:text-slate-400 shrink-0">5</span>
                        <span class="text-xs text-slate-700 dark:text-slate-300 font-medium">Total value of taxable purchases</span>
                    </div>
                    <span class="text-sm font-bold text-slate-900 dark:text-white font-mono tabular-nums">S$ {{ number_format($box5, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Section: TAX (Boxes 6-8) --}}
        <div class="space-y-1">
            <h3 class="text-[11px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider px-1 mb-2">Tax</h3>
            <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
                {{-- Box 6 --}}
                <div class="flex items-center justify-between px-5 py-3.5 bg-white dark:bg-slate-900/60">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-[11px] font-black text-slate-600 dark:text-slate-400 shrink-0">6</span>
                        <span class="text-xs text-slate-700 dark:text-slate-300 font-medium">Output tax due</span>
                    </div>
                    <span class="text-sm font-bold text-slate-900 dark:text-white font-mono tabular-nums">S$ {{ number_format($box6, 2) }}</span>
                </div>
                {{-- Box 7 --}}
                <div class="flex items-center justify-between px-5 py-3.5 bg-white dark:bg-slate-900/60">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-[11px] font-black text-slate-600 dark:text-slate-400 shrink-0">7</span>
                        <span class="text-xs text-slate-700 dark:text-slate-300 font-medium">Input tax and refunds claimed</span>
                    </div>
                    <span class="text-sm font-bold text-slate-900 dark:text-white font-mono tabular-nums">S$ {{ number_format($box7, 2) }}</span>
                </div>
                {{-- Box 8 --}}
                <div class="flex items-center justify-between px-5 py-4 border-t-2 border-slate-200 dark:border-slate-700 {{ $box8 > 0 ? 'bg-red-50 dark:bg-red-500/5' : ($box8 < 0 ? 'bg-emerald-50 dark:bg-emerald-500/5' : 'bg-slate-50 dark:bg-slate-800/60') }}">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg {{ $box8 > 0 ? 'bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400' : ($box8 < 0 ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400' : 'bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-400') }} text-[11px] font-black shrink-0">8</span>
                        <div>
                            <span class="text-xs text-slate-900 dark:text-white font-bold">Net GST to be {{ $box8 > 0 ? 'paid to' : 'claimed from' }} IRAS</span>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 block">(Box 6 minus Box 7)</span>
                        </div>
                    </div>
                    <span class="text-base font-black font-mono tabular-nums {{ $box8 > 0 ? 'text-red-700 dark:text-red-400' : ($box8 < 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-900 dark:text-white') }}">S$ {{ number_format(abs($box8), 2) }}{{ $box8 < 0 ? ' CR' : '' }}</span>
                </div>
            </div>
        </div>

        {{-- Section: SCHEMES (Box 9) --}}
        <div class="space-y-1">
            <h3 class="text-[11px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider px-1 mb-2">Schemes</h3>
            <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3.5 bg-white dark:bg-slate-900/60">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-[11px] font-black text-slate-600 dark:text-slate-400 shrink-0">9</span>
                        <span class="text-xs text-slate-700 dark:text-slate-300 font-medium">Total value of goods imported under MES / Other Schemes</span>
                    </div>
                    <span class="text-sm font-bold text-slate-900 dark:text-white font-mono tabular-nums">S$ {{ number_format($box9, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 text-center">
            <p class="text-[11px] text-slate-400 dark:text-slate-500">This is a system-generated report for internal reference. Please verify all figures before submitting to IRAS.</p>
        </div>
    </div>
</div>
@endsection
