@extends('layouts.app')

@section('title', 'Set Budgets')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <x-sticky-form-bar :cancel-url="route('budgets.index', ['year' => $year])" cancel-label="Back" save-label="Save Budgets">
        <x-slot:title>
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Set Budgets for {{ $year }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">Enter monthly budget amounts for each expense category</p>
            </div>
        </x-slot:title>
        <x-slot:actions>
            <form method="GET" action="{{ route('budgets.create') }}" class="flex items-center gap-2">
                <select name="year" onchange="this.form.submit()" class="text-xs px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    @for($y = now()->year + 1; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>
        </x-slot:actions>
    </x-sticky-form-bar>

    <!-- Budget Form -->
    <form method="POST" action="{{ route('budgets.store') }}">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">

        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-3.5 px-4 sticky left-0 bg-slate-50 dark:bg-slate-800/60 z-10 min-w-[180px]">Category</th>
                            @for($m = 1; $m <= 12; $m++)
                                <th class="py-3.5 px-2 text-center min-w-[90px]">{{ date('M', mktime(0, 0, 0, $m, 1)) }}</th>
                            @endfor
                            <th class="py-3.5 px-4 text-right min-w-[100px]">Annual Total</th>
                            <th class="py-3.5 px-2 text-center min-w-[60px]">Fill</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        @foreach($categories as $cat)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition" data-category="{{ $cat->id }}">
                            <td class="py-2.5 px-4 font-semibold text-slate-900 dark:text-white sticky left-0 bg-white dark:bg-slate-900/80 z-10">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $cat->color ?? '#2563eb' }}"></span>
                                    {{ $cat->name }}
                                </div>
                            </td>
                            @for($m = 1; $m <= 12; $m++)
                                @php $val = $existing[$cat->id . '-' . $m] ?? ''; @endphp
                                <td class="py-2.5 px-1">
                                    <input type="text"
                                        name="budgets[{{ $cat->id }}][{{ $m }}]"
                                        value="{{ $val ? number_format($val, 2) : '' }}"
                                        placeholder="0.00"
                                        class="budget-input w-full text-xs text-right px-2 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                        data-cat="{{ $cat->id }}"
                                        data-month="{{ $m }}"
                                    >
                                </td>
                            @endfor
                            <td class="py-2.5 px-4 text-right font-bold text-slate-900 dark:text-white annual-total" data-cat="{{ $cat->id }}">
                                $0.00
                            </td>
                            <td class="py-2.5 px-2 text-center">
                                <button type="button" onclick="fillAllMonths({{ $cat->id }})" class="btn-icon" title="Copy first month value to all months" aria-label="Fill all months">
                                    <i data-lucide="arrow-left-right" aria-hidden="true"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" id="primarySubmit" class="btn btn-primary">
                <i data-lucide="save" aria-hidden="true"></i> Save Budgets
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
function parseAmount(str) {
    if (!str) return 0;
    return parseFloat(str.replace(/,/g, '')) || 0;
}

function updateAnnualTotal(catId) {
    let total = 0;
    document.querySelectorAll('input.budget-input[data-cat="' + catId + '"]').forEach(function(input) {
        total += parseAmount(input.value);
    });
    const el = document.querySelector('.annual-total[data-cat="' + catId + '"]');
    if (el) {
        el.textContent = '$' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

function fillAllMonths(catId) {
    const firstInput = document.querySelector('input.budget-input[data-cat="' + catId + '"][data-month="1"]');
    if (!firstInput) return;
    const val = firstInput.value;
    document.querySelectorAll('input.budget-input[data-cat="' + catId + '"]').forEach(function(input) {
        input.value = val;
    });
    updateAnnualTotal(catId);
}

document.addEventListener('DOMContentLoaded', function() {
    // Compute initial totals
    @foreach($categories as $cat)
        updateAnnualTotal({{ $cat->id }});
    @endforeach

    // Listen for input changes
    document.querySelectorAll('input.budget-input').forEach(function(input) {
        input.addEventListener('input', function() {
            updateAnnualTotal(this.dataset.cat);
        });
    });
});
</script>
@endsection
