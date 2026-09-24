@extends('layouts.app')

@section('title', 'Budget vs Actual')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Budget vs Actual Overview</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Compare budgeted amounts against actual spending for {{ $year }}</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <!-- Year selector -->
            <form method="GET" action="{{ route('budgets.index') }}" class="flex items-center gap-2">
                <select name="year" onchange="this.form.submit()" class="text-xs px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    @for($y = now()->year + 1; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>
            @canEdit
            <a href="{{ route('budgets.create', ['year' => $year]) }}" class="btn btn-primary">
                <i data-lucide="pencil" aria-hidden="true"></i> Set Budgets
            </a>
            @endcanEdit
        </div>
    </div>

    @if($totalBudget > 0)
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card rounded-2xl p-5">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-1">Total Budget</p>
            <p class="text-xl font-bold text-slate-900 dark:text-white">${{ number_format($totalBudget, 2) }}</p>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-1">Total Actual</p>
            <p class="text-xl font-bold text-slate-900 dark:text-white">${{ number_format($totalActual, 2) }}</p>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-1">Total Variance</p>
            <p class="text-xl font-bold {{ $totalVariance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $totalVariance >= 0 ? '' : '-' }}${{ number_format(abs($totalVariance), 2) }}
            </p>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-1">Overall % Used</p>
            <p class="text-xl font-bold {{ $overallPct <= 80 ? 'text-emerald-600 dark:text-emerald-400' : ($overallPct <= 100 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">{{ $overallPct }}%</p>
        </div>
    </div>

    <!-- Budget vs Actual Table -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">Category</th>
                        <th class="py-3.5 px-4 text-right">Annual Budget</th>
                        <th class="py-3.5 px-4 text-right">Actual YTD</th>
                        <th class="py-3.5 px-4 text-right">Variance</th>
                        <th class="py-3.5 px-4 w-64">% Used</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @foreach($overview as $row)
                        @if($row['budget'] > 0 || $row['actual'] > 0)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3.5 px-4 font-semibold text-slate-900 dark:text-white">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $row['category']->color ?? '#2563eb' }}"></span>
                                    {{ $row['category']->name }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-right font-semibold">${{ number_format($row['budget'], 2) }}</td>
                            <td class="py-3.5 px-4 text-right font-semibold">${{ number_format($row['actual'], 2) }}</td>
                            <td class="py-3.5 px-4 text-right font-bold {{ $row['variance'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $row['variance'] >= 0 ? '' : '-' }}${{ number_format(abs($row['variance']), 2) }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 rounded-full bg-slate-100 dark:bg-slate-700 overflow-hidden">
                                        @php
                                            $pct = min($row['pct_used'], 150);
                                            $barColor = $row['pct_used'] <= 80 ? 'bg-emerald-500' : ($row['pct_used'] <= 100 ? 'bg-amber-500' : 'bg-red-500');
                                        @endphp
                                        <div class="{{ $barColor }} h-full rounded-full transition-all" style="width: {{ min($pct, 100) }}%"></div>
                                    </div>
                                    <span class="text-[10px] font-bold w-10 text-right {{ $row['pct_used'] <= 80 ? 'text-emerald-600 dark:text-emerald-400' : ($row['pct_used'] <= 100 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">{{ $row['pct_used'] }}%</span>
                                </div>
                            </td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Chart: Budget vs Actual -->
    <div class="glass-card rounded-2xl p-6">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Budget vs Actual by Category</h3>
        <div style="height: 350px;">
            <canvas id="budgetChart"></canvas>
        </div>
    </div>
    @else
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm px-6 py-12 text-center">
        <div class="flex flex-col items-center justify-center">
            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-3">
                <i data-lucide="target" class="w-6 h-6 text-slate-400 dark:text-slate-500" aria-hidden="true"></i>
            </div>
            <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">No Budgets Set for {{ $year }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">Set monthly budgets by category to track your spending against targets.</p>
            @canEdit
            <a href="{{ route('budgets.create', ['year' => $year]) }}" class="btn btn-primary mt-3">
                <i data-lucide="plus" aria-hidden="true"></i> Set Budgets for {{ $year }}
            </a>
            @endcanEdit
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
@if($totalBudget > 0)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('budgetChart');
    if (!ctx) return;

    const labels = @json(collect($overview)->filter(fn($r) => $r['budget'] > 0 || $r['actual'] > 0)->pluck('category.name')->values());
    const budgetData = @json(collect($overview)->filter(fn($r) => $r['budget'] > 0 || $r['actual'] > 0)->pluck('budget')->values());
    const actualData = @json(collect($overview)->filter(fn($r) => $r['budget'] > 0 || $r['actual'] > 0)->pluck('actual')->values());

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Budget',
                    data: budgetData,
                    backgroundColor: 'rgba(99, 102, 241, 0.7)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: 'Actual',
                    data: actualData,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 20,
                        font: { size: 11, family: "'Inter', sans-serif" },
                        color: document.documentElement.classList.contains('dark') ? '#94a3b8' : '#475569',
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 10, family: "'Inter', sans-serif" },
                        color: document.documentElement.classList.contains('dark') ? '#64748b' : '#64748b',
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: document.documentElement.classList.contains('dark') ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)',
                    },
                    ticks: {
                        font: { size: 10, family: "'Inter', sans-serif" },
                        color: document.documentElement.classList.contains('dark') ? '#64748b' : '#64748b',
                        callback: function(value) { return '$' + value.toLocaleString(); }
                    }
                }
            }
        }
    });
});
</script>
@endif
@endsection
