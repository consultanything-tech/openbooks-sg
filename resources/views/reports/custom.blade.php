@extends('layouts.app')

@section('title', 'Custom Report Builder')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="wand-2" class="w-4 h-4 text-indigo-500"></i> Custom Report Builder
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Build, run, and save custom analytical reports across your financial data</p>
        </div>
    </div>

    <!-- Two-Panel Layout -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- LEFT PANEL: Builder -->
        <div class="xl:col-span-1 space-y-4">
            <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
                    <i data-lucide="sliders" class="w-4 h-4 text-indigo-500"></i> Report Configuration
                </h3>

                <!-- Data Source -->
                <div>
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 block">Data Source</label>
                    <select id="reportSource" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        <option value="invoices">Invoices</option>
                        <option value="bills">Bills</option>
                        <option value="transactions">Transactions</option>
                        <option value="time_entries">Time Entries</option>
                    </select>
                </div>

                <!-- Dimensions -->
                <div>
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">Dimensions</label>
                    <div class="space-y-1.5">
                        <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="dimensions[]" value="date" checked class="dim-check rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500"> Date
                        </label>
                        <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="dimensions[]" value="customer" class="dim-check rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500"> Customer / Vendor
                        </label>
                        <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="dimensions[]" value="category" class="dim-check rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500"> Category
                        </label>
                        <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="dimensions[]" value="status" class="dim-check rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500"> Status
                        </label>
                    </div>
                </div>

                <!-- Metrics -->
                <div>
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">Metrics</label>
                    <div class="space-y-1.5">
                        <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="metrics[]" value="sum_total" checked class="met-check rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500"> Sum Total
                        </label>
                        <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="metrics[]" value="count" class="met-check rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500"> Count
                        </label>
                        <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="metrics[]" value="avg_total" class="met-check rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500"> Average Total
                        </label>
                        <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="metrics[]" value="sum_tax" class="met-check rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500"> Sum Tax
                        </label>
                    </div>
                </div>

                <!-- Date Range -->
                <div>
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">Date Range</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" id="dateFrom" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500" placeholder="From">
                        <input type="date" id="dateTo" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500" placeholder="To">
                    </div>
                </div>

                <!-- Group By -->
                <div>
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 block">Group By</label>
                    <select id="groupBy" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        <option value="">-- Auto (first dimension) --</option>
                        <option value="date">Date</option>
                        <option value="customer">Customer / Vendor</option>
                        <option value="category">Category</option>
                        <option value="status">Status</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2 pt-2">
                    <button type="button" id="btnRunReport" onclick="runReport()" class="btn btn-primary flex-1">
                        <i data-lucide="play" aria-hidden="true"></i> Run Report
                    </button>
                    @canEdit
                    <button type="button" onclick="openSaveModal()" class="btn btn-primary">
                        <i data-lucide="save" aria-hidden="true"></i> Save
                    </button>
                    @endcanEdit
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: Results -->
        <div class="xl:col-span-2 space-y-4">
            <!-- Results Table -->
            <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
                        <i data-lucide="table" class="w-4 h-4 text-indigo-500"></i> Results
                        <span id="resultCount" class="hidden text-[10px] font-normal normal-case text-slate-400"></span>
                    </h3>
                    <button type="button" id="btnExportCsv" onclick="exportCsv()" class="hidden btn btn-secondary">
                        <i data-lucide="file-spreadsheet" aria-hidden="true"></i> Export CSV
                    </button>
                </div>
                <div id="resultsEmpty" class="py-16 text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                        <i data-lucide="bar-chart-2" class="w-6 h-6 text-slate-300 dark:text-slate-600"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">No results yet</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Configure your report and click Run</p>
                </div>
                <div id="resultsLoading" class="hidden py-12 text-center">
                    <i data-lucide="loader-2" class="w-5 h-5 text-indigo-500 mb-2 animate-spin"></i>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Running report query...</p>
                </div>
                <div id="resultsTableWrap" class="hidden overflow-x-auto">
                    <table id="resultsTable" class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60"><tr id="resultsHead"></tr></thead>
                        <tbody id="resultsBody" class="divide-y divide-slate-100 dark:divide-slate-800"></tbody>
                        <tfoot id="resultsFoot" class="bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200 dark:border-slate-700"></tfoot>
                    </table>
                </div>
            </div>

            <!-- Chart -->
            <div id="chartSection" class="hidden bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3 flex items-center gap-2">
                    <i data-lucide="bar-chart-2" class="w-4 h-4 text-indigo-500"></i> Visualization
                </h3>
                <div class="relative h-72">
                    <canvas id="reportChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Saved Reports -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
                <i data-lucide="bookmark" class="w-4 h-4 text-indigo-500"></i> Saved Reports
            </h3>
        </div>
        @if($savedReports->isEmpty())
            <div class="py-10 text-center">
                <p class="text-xs text-slate-500 dark:text-slate-400">No saved reports yet. Build a report above and click Save.</p>
            </div>
        @else
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($savedReports as $report)
                    <div class="px-4 py-3 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-slate-900 dark:text-white truncate">{{ $report->name }}</p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                                <span class="inline-flex items-center gap-1"><i data-lucide="database" class="w-3.5 h-3.5"></i> {{ ucfirst(str_replace('_', ' ', $report->source)) }}</span>
                                @if($report->description)
                                    <span class="mx-1.5">--</span>{{ \Illuminate\Support\Str::limit($report->description, 60) }}
                                @endif
                                <span class="mx-1.5">--</span>by {{ $report->user->name ?? 'Unknown' }}
                                @if($report->is_shared)
                                    <span class="ml-1.5 px-1.5 py-0.5 rounded-full bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 text-[9px] font-semibold">Shared</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-1.5 ml-3 shrink-0">
                            <button type="button" onclick='loadSavedReport(@js($report))' class="btn-icon" title="Run" aria-label="Run">
                                <i data-lucide="play" aria-hidden="true"></i>
                            </button>
                            @if($report->user_id === Auth::id())
                                <form action="{{ route('reports.custom.destroy', $report->id) }}" method="POST" onsubmit="return confirm('Delete this saved report?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete" aria-label="Delete">
                                        <i data-lucide="trash-2" aria-hidden="true"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<!-- Save Report Modal -->
<div id="saveReportModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeSaveModal()"></div>
    <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-md p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Save Report</h3>
            <button onclick="closeSaveModal()" class="btn-icon" aria-label="Close"><i data-lucide="x" aria-hidden="true"></i></button>
        </div>
        <div class="space-y-3">
            <div>
                <label class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 block">Report Name *</label>
                <input type="text" id="saveName" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500" placeholder="e.g. Monthly Sales by Customer">
            </div>
            <div>
                <label class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 block">Description</label>
                <textarea id="saveDescription" rows="2" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 resize-none" placeholder="Optional description..."></textarea>
            </div>
            <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                <input type="checkbox" id="saveShared" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500"> Share with all users
            </label>
        </div>
        <div class="flex items-center justify-end gap-2 mt-5">
            <button onclick="closeSaveModal()" class="btn btn-ghost">Cancel</button>
            <button onclick="saveReport()" class="btn btn-primary">
                <i data-lucide="save" aria-hidden="true"></i> Save Report
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let reportChart = null;
let lastResults = null;

// Dimensions/metrics each source can actually support (mirrors server config)
const SOURCE_CAPS = {
    invoices:     { dims: ['date', 'customer', 'status'],     mets: ['sum_total', 'count', 'avg_total', 'sum_tax'] },
    bills:        { dims: ['date', 'customer', 'status'],     mets: ['sum_total', 'count', 'avg_total', 'sum_tax'] },
    transactions: { dims: ['date', 'customer', 'category'],   mets: ['sum_total', 'count', 'avg_total'] },
    time_entries: { dims: ['date', 'customer'],               mets: ['sum_total', 'count', 'avg_total'] },
};

function applySourceCaps() {
    const caps = SOURCE_CAPS[document.getElementById('reportSource').value] || SOURCE_CAPS.invoices;
    document.querySelectorAll('.dim-check').forEach(c => {
        const ok = caps.dims.includes(c.value);
        c.disabled = !ok;
        if (!ok) c.checked = false;
        c.closest('label').classList.toggle('opacity-40', !ok);
        c.closest('label').classList.toggle('cursor-not-allowed', !ok);
    });
    document.querySelectorAll('.met-check').forEach(c => {
        const ok = caps.mets.includes(c.value);
        c.disabled = !ok;
        if (!ok) c.checked = false;
        c.closest('label').classList.toggle('opacity-40', !ok);
        c.closest('label').classList.toggle('cursor-not-allowed', !ok);
    });
    const gb = document.getElementById('groupBy');
    Array.from(gb.options).forEach(o => { o.disabled = o.value !== '' && !caps.dims.includes(o.value); });
    if (gb.value && gb.selectedOptions[0] && gb.selectedOptions[0].disabled) gb.value = '';
    // Keep at least one dimension/metric checked
    if (!document.querySelector('.dim-check:checked')) {
        const first = document.querySelector('.dim-check:not(:disabled)');
        if (first) first.checked = true;
    }
    if (!document.querySelector('.met-check:checked')) {
        const first = document.querySelector('.met-check:not(:disabled)');
        if (first) first.checked = true;
    }
}

function getReportConfig() {
    const source = document.getElementById('reportSource').value;
    const dimensions = Array.from(document.querySelectorAll('.dim-check:checked')).map(c => c.value);
    const metrics = Array.from(document.querySelectorAll('.met-check:checked')).map(c => c.value);
    const date_from = document.getElementById('dateFrom').value || null;
    const date_to = document.getElementById('dateTo').value || null;
    const group_by = document.getElementById('groupBy').value || null;
    return { source, dimensions, metrics, date_from, date_to, group_by };
}

function runReport() {
    const config = getReportConfig();
    if (config.dimensions.length === 0) { alert('Select at least one dimension.'); return; }
    if (config.metrics.length === 0) { alert('Select at least one metric.'); return; }

    document.getElementById('resultsEmpty').classList.add('hidden');
    document.getElementById('resultsTableWrap').classList.add('hidden');
    document.getElementById('chartSection').classList.add('hidden');
    document.getElementById('resultsLoading').classList.remove('hidden');
    document.getElementById('btnRunReport').disabled = true;

    fetch('{{ route("reports.custom.run") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify(config)
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('resultsLoading').classList.add('hidden');
        document.getElementById('btnRunReport').disabled = false;

        if (!data.success) {
            let msg = data.message || 'Report failed.';
            if (data.errors) {
                const first = Object.values(data.errors)[0];
                if (first && first[0]) msg = first[0];
            }
            alert(msg);
            return;
        }

        lastResults = data.results;
        renderTable(data.results);
        renderChart(data.results);
    })
    .catch(err => {
        document.getElementById('resultsLoading').classList.add('hidden');
        document.getElementById('btnRunReport').disabled = false;
        alert('Error: ' + err.message);
    });
}

function renderTable(results) {
    const rows = results.rows || [];
    const summary = results.summary || {};

    if (rows.length === 0) {
        document.getElementById('resultsEmpty').classList.remove('hidden');
        document.getElementById('resultsEmpty').innerHTML = '<div class="py-8 text-center"><p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">No data found</p><p class="text-xs text-slate-500 dark:text-slate-400">Try adjusting your date range or filters.</p></div>';
        document.getElementById('btnExportCsv').classList.add('hidden');
        document.getElementById('resultCount').classList.add('hidden');
        return;
    }

    const headers = Object.keys(rows[0]);
    const headRow = document.getElementById('resultsHead');
    headRow.innerHTML = headers.map(h => '<th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3">' + formatHeader(h) + '</th>').join('');

    const body = document.getElementById('resultsBody');
    body.innerHTML = rows.map(row => '<tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">' + headers.map(h => {
        let val = row[h];
        if (typeof val === 'number' && h !== 'record_count') val = '{{ $currencySymbol }}' + val.toFixed(2);
        return '<td class="px-4 py-2.5 text-xs text-slate-700 dark:text-slate-300">' + (val != null ? val : '--') + '</td>';
    }).join('') + '</tr>').join('');

    // Summary footer
    const foot = document.getElementById('resultsFoot');
    foot.innerHTML = '<tr>' + headers.map((h, i) => {
        let val = '--';
        if (summary[h] !== undefined) {
            val = h === 'record_count' ? summary[h] : '{{ $currencySymbol }}' + Number(summary[h]).toFixed(2);
        } else if (i === 0) {
            val = '<span class="font-bold text-slate-900 dark:text-white">TOTAL</span>';
        }
        return '<td class="px-4 py-3 text-xs font-semibold text-slate-700 dark:text-slate-300">' + val + '</td>';
    }).join('') + '</tr>';

    document.getElementById('resultsTableWrap').classList.remove('hidden');
    document.getElementById('btnExportCsv').classList.remove('hidden');
    document.getElementById('btnExportCsv').classList.add('flex');
    const countEl = document.getElementById('resultCount');
    countEl.textContent = '(' + rows.length + ' rows)';
    countEl.classList.remove('hidden');
}

var CHART_METRIC_KEYS = ['sum_total', 'record_count', 'avg_total', 'sum_tax'];
var CHART_PALETTE = [
    ['rgba(37, 99, 235, 0.7)',  'rgba(37, 99, 235, 1)'],
    ['rgba(16, 185, 129, 0.7)', 'rgba(16, 185, 129, 1)'],
    ['rgba(245, 158, 11, 0.7)', 'rgba(245, 158, 11, 1)'],
    ['rgba(139, 92, 246, 0.7)', 'rgba(139, 92, 246, 1)'],
    ['rgba(244, 63, 94, 0.7)',  'rgba(244, 63, 94, 1)'],
    ['rgba(6, 182, 212, 0.7)',  'rgba(6, 182, 212, 1)'],
    ['rgba(249, 115, 22, 0.7)', 'rgba(249, 115, 22, 1)'],
    ['rgba(100, 116, 139, 0.7)','rgba(100, 116, 139, 1)']
];

function chartColor(i) { return CHART_PALETTE[i % CHART_PALETTE.length]; }

function renderChart(results) {
    const rows = results.rows || [];
    if (rows.length === 0 || rows.length > 50) return;

    const headers = Object.keys(rows[0]);
    const dims = headers.filter(h => CHART_METRIC_KEYS.indexOf(h) === -1);
    const metrics = headers.filter(h => CHART_METRIC_KEYS.indexOf(h) !== -1);
    if (dims.length === 0 || metrics.length === 0) return;

    const clip = (v, n) => String(v == null ? '--' : v).substring(0, n || 30);
    // All dimensions after the first, combined so none are ever dropped from the chart
    const seriesOf = r => dims.slice(1).map(d => clip(r[d], 24)).join(' · ');
    const useCountAxis = metrics.indexOf('record_count') !== -1 && metrics.some(m => m !== 'record_count');

    const ds = (label, data, i, metricKey) => {
        const d = {
            label: label,
            data: data,
            backgroundColor: chartColor(i)[0],
            borderColor: chartColor(i)[1],
            borderWidth: 1,
            borderRadius: 6,
        };
        if (metricKey === 'record_count' && useCountAxis) d.yAxisID = 'y1';
        return d;
    };

    let labels = [], datasets = [];

    if (dims.length === 1) {
        // One dataset per selected metric
        labels = rows.map(r => clip(r[dims[0]]));
        datasets = metrics.map((m, i) => ds(formatHeader(m), rows.map(r => Number(r[m]) || 0), i, m));
    } else if (metrics.length === 1) {
        // Two+ dimensions, single metric: X = 1st dim, series = all remaining dims (grouped bars)
        const labelKeys = [], seriesKeys = [];
        rows.forEach(r => {
            const lk = clip(r[dims[0]]), sk = seriesOf(r);
            if (labelKeys.indexOf(lk) === -1) labelKeys.push(lk);
            if (seriesKeys.indexOf(sk) === -1 && seriesKeys.length < 10) seriesKeys.push(sk);
        });
        labels = labelKeys;
        datasets = seriesKeys.map((sk, i) => ds(sk, labelKeys.map(lk => {
            const hit = rows.find(r => clip(r[dims[0]]) === lk && seriesOf(r) === sk);
            return hit ? Number(hit[metrics[0]]) || 0 : 0;
        }), i));
    } else {
        // Two+ dimensions and multiple metrics: multi-line X labels (1st dim + all remaining
        // dims on a second line), one dataset per metric
        labels = rows.map(r => [clip(r[dims[0]]), seriesOf(r)]);
        datasets = metrics.map((m, i) => ds(formatHeader(m), rows.map(r => Number(r[m]) || 0), i, m));
    }

    document.getElementById('chartSection').classList.remove('hidden');

    if (reportChart) reportChart.destroy();

    const ctx = document.getElementById('reportChart').getContext('2d');
    const isDark = document.documentElement.classList.contains('dark');

    reportChart = new Chart(ctx, {
        type: 'bar',
        data: { labels: labels, datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: datasets.length > 1,
                    labels: { color: isDark ? '#94a3b8' : '#64748b', font: { size: 10 }, boxWidth: 12, boxHeight: 12 }
                },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            const v = ctx.parsed.y;
                            const isCount = ctx.dataset.label === 'Record Count';
                            return ' ' + ctx.dataset.label + ': ' + (isCount ? v : '{{ $currencySymbol }}' + Number(v).toFixed(2));
                        }
                    }
                }
            },
            scales: {
                x: { ticks: { color: isDark ? '#94a3b8' : '#64748b', font: { size: 10 }, maxRotation: 45 }, grid: { display: false } },
                y: { ticks: { color: isDark ? '#94a3b8' : '#64748b', font: { size: 10 } }, grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' } },
                ...(useCountAxis ? { y1: {
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { color: isDark ? '#94a3b8' : '#64748b', font: { size: 10 }, precision: 0 }
                } } : {})
            }
        }
    });
}

function formatHeader(key) {
    return key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function exportCsv() {
    const config = getReportConfig();
    if (config.dimensions.length === 0 || config.metrics.length === 0) { alert('Configure report first.'); return; }

    // Build a form to POST and trigger download
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("reports.custom.export") }}';
    form.style.display = 'none';

    const addField = (name, value) => {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = name; input.value = value;
        form.appendChild(input);
    };

    addField('_token', '{{ csrf_token() }}');
    addField('source', config.source);
    config.dimensions.forEach((d, i) => addField('dimensions[' + i + ']', d));
    config.metrics.forEach((m, i) => addField('metrics[' + i + ']', m));
    if (config.date_from) addField('date_from', config.date_from);
    if (config.date_to) addField('date_to', config.date_to);
    if (config.group_by) addField('group_by', config.group_by);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// Save modal
function openSaveModal() { document.getElementById('saveReportModal').classList.remove('hidden'); }
function closeSaveModal() { document.getElementById('saveReportModal').classList.add('hidden'); }

function saveReport() {
    const name = document.getElementById('saveName').value.trim();
    if (!name) { alert('Please enter a report name.'); return; }

    const config = getReportConfig();
    if (config.dimensions.length === 0 || config.metrics.length === 0) { alert('Please configure dimensions and metrics first.'); return; }

    fetch('{{ route("reports.custom.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({
            name: name,
            description: document.getElementById('saveDescription').value.trim() || null,
            source: config.source,
            definition: { dimensions: config.dimensions, metrics: config.metrics, date_from: config.date_from, date_to: config.date_to, group_by: config.group_by },
            is_shared: document.getElementById('saveShared').checked
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { location.reload(); }
        else { alert(data.message || 'Save failed.'); }
    })
    .catch(err => alert('Error: ' + err.message));
}

// Load a saved report into the builder
function loadSavedReport(report) {
    document.getElementById('reportSource').value = report.source;
    applySourceCaps();
    const def = report.definition || {};

    document.querySelectorAll('.dim-check').forEach(c => { c.checked = !c.disabled && (def.dimensions || []).includes(c.value); });
    document.querySelectorAll('.met-check').forEach(c => { c.checked = !c.disabled && (def.metrics || []).includes(c.value); });

    document.getElementById('dateFrom').value = def.date_from || '';
    document.getElementById('dateTo').value = def.date_to || '';
    const gb = document.getElementById('groupBy');
    gb.value = def.group_by || '';
    if (gb.selectedOptions[0] && gb.selectedOptions[0].disabled) gb.value = '';

    runReport();
}

document.getElementById('reportSource').addEventListener('change', applySourceCaps);
applySourceCaps();
</script>
@endsection
