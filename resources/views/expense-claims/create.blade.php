@extends('layouts.app')

@section('title', 'Submit Expense Claim')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <x-sticky-form-bar :cancel-url="route('expense_claims.index')">
    <x-slot:title>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Submit Expense Claim</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">File a new expense reimbursement request</p>
        </div>
    </x-slot:title>
</x-sticky-form-bar>

    <form action="{{ route('expense_claims.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Claim Details Card -->
        <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-5">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Claim Details</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-5">Provide the details for your expense reimbursement</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Claim Number *</label>
                    <input type="text" name="claim_number" value="{{ old('claim_number', $nextNumber) }}" required
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-mono placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Claim Date *</label>
                    <input type="date" name="claim_date" value="{{ old('claim_date', date('Y-m-d')) }}" required
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Category *</label>
                    <select name="category" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white appearance-none cursor-pointer focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                        <option value="">Select Category</option>
                        @foreach($categories ?? ['Travel', 'Meals & Entertainment', 'Office Supplies', 'Transport', 'Accommodation', 'Training', 'Communication', 'Other'] as $cat)
                            <option value="{{ $cat }}" {{ old('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Title *</label>
                    <input type="text" name="title" value="{{ old('title') }}" required placeholder="e.g. Client meeting lunch, Flight to KL"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Amount ({{ $currencySymbol }}) *</label>
                    <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="0.01" required placeholder="0.00"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-bold placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Description</label>
                <textarea name="description" rows="3" placeholder="Provide additional details about this expense..."
                    class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">{{ old('description') }}</textarea>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Receipt Upload</label>
                <div class="flex items-center gap-3">
                    <label class="flex-1 flex items-center justify-center gap-2 px-4 py-4 bg-slate-50 dark:bg-slate-800/50 border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-xl cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500 transition">
                        <i data-lucide="cloud-upload" class="w-4 h-4 text-slate-400 dark:text-slate-500"></i>
                        <span class="text-xs text-slate-500 dark:text-slate-400" id="receiptFileName">Click to upload receipt (JPG, PNG, PDF)</span>
                        <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" class="hidden" onchange="document.getElementById('receiptFileName').textContent = this.files[0] ? this.files[0].name : 'Click to upload receipt (JPG, PNG, PDF)'">
                    </label>
                </div>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1.5">Max file size: 5MB. Accepted formats: JPG, PNG, PDF</p>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('expense_claims.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" id="primarySubmit" class="btn btn-primary">
                <i data-lucide="save" aria-hidden="true"></i> Save
            </button>
        </div>
    </form>
</div>
@endsection
