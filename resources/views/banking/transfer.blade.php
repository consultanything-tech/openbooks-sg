@extends('layouts.app')

@section('title', 'Transfer Funds')

@section('content')
<div class="max-w-xl mx-auto space-y-6">
    <x-sticky-form-bar :cancel-url="route('banking.index')" save-label="Execute Transfer">
        <x-slot:title>
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Internal Account Transfer</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Move funds between checking, savings, or physical cash drawers</p>
            </div>
        </x-slot:title>
    </x-sticky-form-bar>

    <form action="{{ route('banking.transfer.post') }}" method="POST" class="glass-card p-6 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-5">
        @csrf

        <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Transfer From *</label>
            <select name="from_account_id" required class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2.5 text-slate-900 dark:text-white text-xs focus:outline-none focus:border-indigo-500">
                <option value="">Select Source Account</option>
                @foreach($accounts as $a)
                    <option value="{{ $a->id }}">{{ $a->name }} (Available: {{ $currencySymbol }}{{ number_format($a->current_balance, 2) }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Transfer To *</label>
            <select name="to_account_id" required class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2.5 text-slate-900 dark:text-white text-xs focus:outline-none focus:border-indigo-500">
                <option value="">Select Destination Account</option>
                @foreach($accounts as $a)
                    <option value="{{ $a->id }}">{{ $a->name }} (Current: {{ $currencySymbol }}{{ number_format($a->current_balance, 2) }})</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Amount ({{ $currencySymbol }}) *</label>
                <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0.00"
                    class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2.5 text-slate-900 dark:text-white text-xs font-bold focus:outline-none focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Transfer Date *</label>
                <input type="date" name="transfer_date" value="{{ date('Y-m-d') }}" required
                    class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2.5 text-slate-900 dark:text-white text-xs focus:outline-none focus:border-indigo-500">
            </div>
        </div>

        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
            <a href="{{ route('banking.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" id="primarySubmit" class="btn btn-primary">
                <i data-lucide="zap" aria-hidden="true"></i> Execute Transfer
            </button>
        </div>
    </form>
</div>
@endsection
