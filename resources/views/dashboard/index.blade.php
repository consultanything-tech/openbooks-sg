@extends('layouts.app')

@section('title', 'Financial Dashboard')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Dashboard</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Financial overview and key metrics at a glance</p>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Cash in Banks -->
        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-500/5 rounded-full blur-xl group-hover:bg-emerald-500/10 transition"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Total Cash & Banks</span>
                <span class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-sm">
                    <i data-lucide="landmark" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ $company->currency_symbol ?? '$' }}{{ number_format($totalCash, 2) }}</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold"><i data-lucide="check-circle" class="w-3.5 h-3.5 inline-block"></i> Reconciled</span> across {{ count($bankAccounts) }} accounts
                </p>
            </div>
        </div>

        <!-- Receivables -->
        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-blue-500/5 rounded-full blur-xl group-hover:bg-blue-500/10 transition"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Accounts Receivable</span>
                <span class="p-2 rounded-xl bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 text-sm">
                    <i data-lucide="arrow-down-left" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ $company->currency_symbol ?? '$' }}{{ number_format($totalReceivables, 2) }}</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Pending customer collections</p>
            </div>
        </div>

        <!-- Payables -->
        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-amber-500/5 rounded-full blur-xl group-hover:bg-amber-500/10 transition"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Accounts Payable</span>
                <span class="p-2 rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 text-sm">
                    <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ $company->currency_symbol ?? '$' }}{{ number_format($totalPayables, 2) }}</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Unpaid vendor obligations</p>
            </div>
        </div>

        <!-- Net Profit -->
        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-purple-500/5 rounded-full blur-xl group-hover:bg-purple-500/10 transition"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Net Profit</span>
                <span class="p-2 rounded-xl bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 text-sm">
                    <i data-lucide="trending-up" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-extrabold {{ $netProfit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $company->currency_symbol ?? '$' }}{{ number_format($netProfit, 2) }}
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Total revenue minus expenses</p>
            </div>
        </div>
    </div>

    <!-- Overdue Alerts -->
    @if($overdueInvoiceCount > 0 || $overdueBillCount > 0)
    <div class="p-4 rounded-2xl border border-red-200 dark:border-red-500/20 bg-red-50 dark:bg-red-500/5 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="p-2.5 rounded-xl bg-red-100 dark:bg-red-500/15 text-red-600 dark:text-red-400">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
            </span>
            <div>
                <h3 class="text-sm font-bold text-red-800 dark:text-red-300">Overdue Attention Required</h3>
                <p class="text-sm text-red-600 dark:text-red-400 mt-0.5">
                    @if($overdueInvoiceCount > 0)
                        <span class="font-semibold">{{ $overdueInvoiceCount }} invoice{{ $overdueInvoiceCount > 1 ? 's' : '' }}</span> overdue ({{ $company->currency_symbol ?? 'S$' }}{{ number_format($overdueInvoiceTotal, 2) }})
                    @endif
                    @if($overdueInvoiceCount > 0 && $overdueBillCount > 0) &bull; @endif
                    @if($overdueBillCount > 0)
                        <span class="font-semibold">{{ $overdueBillCount }} bill{{ $overdueBillCount > 1 ? 's' : '' }}</span> overdue
                    @endif
                </p>
            </div>
        </div>
        <a href="{{ route('invoices.index', ['status' => 'overdue']) }}" class="btn btn-danger">
            <i data-lucide="alert-triangle" aria-hidden="true"></i>
            View Overdue
        </a>
    </div>
    @endif

    <!-- Cash Flow Forecast -->
    <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Cash Flow Forecast</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Projected cash position over next 90 days</p>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <span class="flex items-center gap-1 text-slate-700 dark:text-slate-300">
                    <span class="w-3 h-3 rounded bg-indigo-500 inline-block"></span> Projected Balance
                </span>
                <span class="flex items-center gap-1 text-slate-700 dark:text-slate-300">
                    <span class="w-3 h-3 rounded bg-emerald-500 inline-block"></span> Inflows
                </span>
                <span class="flex items-center gap-1 text-slate-700 dark:text-slate-300">
                    <span class="w-3 h-3 rounded bg-red-400 inline-block"></span> Outflows
                </span>
            </div>
        </div>
        <div class="h-56">
            <canvas id="forecastChart"></canvas>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Cash Flow 6 Months -->
        <div class="lg:col-span-2 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Cash Flow Dynamics</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Monthly income vs expenses over last 6 months</p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1 text-slate-700 dark:text-slate-300">
                        <span class="w-3 h-3 rounded bg-emerald-500 inline-block"></span> Income
                    </span>
                    <span class="flex items-center gap-1 text-slate-700 dark:text-slate-300">
                        <span class="w-3 h-3 rounded bg-red-500 inline-block"></span> Expense
                    </span>
                </div>
            </div>
            <div class="h-64">
                <canvas id="cashFlowChart"></canvas>
            </div>
        </div>

        <!-- Expense by Category -->
        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80">
            <div class="mb-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Expense Distribution</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Cost allocation breakdown by category</p>
            </div>
            <div class="h-64 flex items-center justify-center">
                <canvas id="expenseCategoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Dual Table: Recent Invoices & Recent Ledger -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Invoices -->
        <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Recent Invoices</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Latest sales billing activity</p>
                </div>
                <a href="{{ route('invoices.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 font-semibold">View All &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Invoice #</th>
                            <th class="py-3 px-4">Customer</th>
                            <th class="py-3 px-4">Total</th>
                            <th class="py-3 px-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        @forelse($recentInvoices as $inv)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3 px-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                    <a href="{{ route('invoices.show', $inv->id) }}">{{ $inv->invoice_number }}</a>
                                </td>
                                <td class="py-3 px-4 text-slate-900 dark:text-white font-medium">{{ $inv->customer->name ?? 'N/A' }}</td>
                                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">{{ $company->currency_symbol ?? '$' }}{{ number_format($inv->total, 2) }}</td>
                                <td class="py-3 px-4">
                                    @php
                                        $badgeClasses = [
                                            'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                                            'sent' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
                                            'draft' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
                                            'overdue' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
                                            'partial' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                                        ];
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border uppercase {{ $badgeClasses[$inv->status] ?? 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700' }}">
                                        {{ $inv->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-12 text-center"><div class="flex flex-col items-center justify-center"><div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-3"><i data-lucide="file-text" class="w-6 h-6 text-slate-400 dark:text-slate-500" aria-hidden="true"></i></div><p class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">No invoices generated yet.</p><p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">Invoices will appear here once created.</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Ledger Transactions -->
        <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Live Banking Ledger</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Real-time debits and credits</p>
                </div>
                <a href="{{ route('banking.transactions') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 font-semibold">View Ledger &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Account / Ref</th>
                            <th class="py-3 px-4">Type</th>
                            <th class="py-3 px-4 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        @forelse($recentTransactions as $tx)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3 px-4 text-slate-500 dark:text-slate-400">{{ date('M d, Y', strtotime($tx->transaction_date)) }}</td>
                                <td class="py-3 px-4">
                                    <p class="text-slate-900 dark:text-white font-medium truncate max-w-[140px]">{{ $tx->bankAccount->name ?? 'Default' }}</p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-500 truncate max-w-[140px]">{{ $tx->description }}</p>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border uppercase {{ $tx->type === 'income' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20' : 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20' }}">
                                        {{ $tx->type }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-semibold {{ $tx->type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-200' }}">
                                    {{ $tx->type === 'income' ? '+' : '-' }}{{ $company->currency_symbol ?? '$' }}{{ number_format($tx->amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-12 text-center"><div class="flex flex-col items-center justify-center"><div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-3"><i data-lucide="arrow-left-right" class="w-6 h-6 text-slate-400 dark:text-slate-500" aria-hidden="true"></i></div><p class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">No transactions recorded yet.</p><p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">Banking transactions will appear here once recorded.</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const isDark = document.documentElement.classList.contains('dark');
        const cashFlowCtx = document.getElementById('cashFlowChart').getContext('2d');
        const months = {!! json_encode($months) !!};
        const incomeData = {!! json_encode($incomeData) !!};
        const expenseData = {!! json_encode($expenseData) !!};

        new Chart(cashFlowCtx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Income',
                        data: incomeData,
                        backgroundColor: '#10b981',
                        borderRadius: 6,
                    },
                    {
                        label: 'Expense',
                        data: expenseData,
                        backgroundColor: '#ef4444',
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' },
                        ticks: { color: isDark ? '#94a3b8' : '#64748b' }
                    },
                    y: {
                        grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' },
                        ticks: { color: isDark ? '#94a3b8' : '#64748b' }
                    }
                }
            }
        });

        // Cash Flow Forecast Chart
        const forecastCtx = document.getElementById('forecastChart').getContext('2d');
        const forecastLabels = {!! json_encode($forecastLabels) !!};
        const forecastData = {!! json_encode($forecastData) !!};
        const forecastInflows = {!! json_encode($forecastInflows) !!};
        const forecastOutflows = {!! json_encode($forecastOutflows) !!};

        new Chart(forecastCtx, {
            type: 'line',
            data: {
                labels: forecastLabels,
                datasets: [
                    {
                        label: 'Projected Balance',
                        data: forecastData,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.08)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2.5,
                        pointRadius: 5,
                        pointBackgroundColor: '#2563eb',
                    },
                    {
                        label: 'Expected Inflows',
                        data: forecastInflows,
                        borderColor: '#10b981',
                        borderDash: [5, 5],
                        borderWidth: 1.5,
                        pointRadius: 3,
                        pointBackgroundColor: '#10b981',
                    },
                    {
                        label: 'Expected Outflows',
                        data: forecastOutflows,
                        borderColor: '#f87171',
                        borderDash: [5, 5],
                        borderWidth: 1.5,
                        pointRadius: 3,
                        pointBackgroundColor: '#f87171',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' },
                        ticks: { color: isDark ? '#94a3b8' : '#64748b' }
                    },
                    y: {
                        grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' },
                        ticks: { color: isDark ? '#94a3b8' : '#64748b' }
                    }
                }
            }
        });

        // Expense Category Chart
        const expenseCtx = document.getElementById('expenseCategoryChart').getContext('2d');
        const catLabels = {!! json_encode($categoryLabels) !!};
        const catAmounts = {!! json_encode($categorySeries) !!};
        const catColors = {!! json_encode($categoryColors) !!};

        new Chart(expenseCtx, {
            type: 'doughnut',
            data: {
                labels: catLabels.length ? catLabels : ['Operating'],
                datasets: [{
                    data: catAmounts.length ? catAmounts : [100],
                    backgroundColor: catColors.length ? catColors : ['#10b981', '#3b82f6', '#f59e0b', '#ec4899', '#8b5cf6'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: isDark ? '#94a3b8' : '#475569', font: { size: 10 } }
                    }
                },
                cutout: '70%'
            }
        });
    });
</script>
@endsection
