@extends('layouts.app')

@section('title', 'New Time Entry')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <x-sticky-form-bar :cancel-url="route('time_tracking.index')">
    <x-slot:title>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">New Time Entry</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Log billable or non-billable hours for a customer project</p>
        </div>
    </x-slot:title>
</x-sticky-form-bar>

    @if($errors->any())
    <div class="p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-700 dark:text-red-400 text-xs">
        <ul class="list-disc pl-4 space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    @canEdit
    <form action="{{ route('time_tracking.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Entry Details Card -->
        <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-5">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Entry Details</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-5">Fill in the time tracking information below</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Customer *</label>
                    <select data-combobox name="customer_id" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white appearance-none cursor-pointer focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                        <option value="">Select Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Project</label>
                    <input type="text" name="project" value="{{ old('project') }}" placeholder="e.g. Website Redesign"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Date *</label>
                    <input type="date" name="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}" required
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Hours *</label>
                    <input type="number" name="hours" id="hoursInput" value="{{ old('hours') }}" step="0.25" min="0" required placeholder="0.00"
                        oninput="updateAmountPreview()"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-bold placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Rate ({{ $currencySymbol ?? '' }}) *</label>
                    <input type="number" name="rate" id="rateInput" value="{{ old('rate') }}" step="0.01" min="0" required placeholder="0.00"
                        oninput="updateAmountPreview()"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-bold placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
            </div>

            <!-- Amount Preview -->
            <div class="flex items-center gap-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center shrink-0">
                    <i data-lucide="calculator" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <div>
                    <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Amount</span>
                    <p id="amountPreview" class="text-lg font-extrabold text-slate-900 dark:text-white">{{ $currencySymbol ?? '' }}0.00</p>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Description</label>
                <textarea name="description" rows="3" placeholder="Describe the work performed..."
                    class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">{{ old('description') }}</textarea>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" name="is_billable" value="1" {{ old('is_billable', '1') ? 'checked' : '' }}
                        class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-xs font-medium text-slate-700 dark:text-slate-300">This entry is billable</span>
                </label>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('time_tracking.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" id="primarySubmit" class="btn btn-primary">
                <i data-lucide="save" aria-hidden="true"></i> Save
            </button>
        </div>
    </form>
    @endcanEdit
</div>

<script>
function updateAmountPreview() {
    const hours = parseFloat(document.getElementById('hoursInput').value) || 0;
    const rate = parseFloat(document.getElementById('rateInput').value) || 0;
    const amount = (hours * rate).toFixed(2);
    const symbol = @json($currencySymbol ?? '');
    document.getElementById('amountPreview').textContent = symbol + parseFloat(amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>
@endsection
