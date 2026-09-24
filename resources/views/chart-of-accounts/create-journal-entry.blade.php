@extends('layouts.app')

@section('title', 'New Journal Entry')

@section('content')
<div class="space-y-6">
    <x-sticky-form-bar :cancel-url="route('accounts.journal_entries')" submit-id="submitBtn">
        <x-slot:title>
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">New Journal Entry</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Record a double-entry transaction with balanced debits and credits</p>
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

    <form action="{{ route('accounts.journal_entries.store') }}" method="POST" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm overflow-hidden">
        @csrf
        <div class="p-6 space-y-6">
            <!-- Entry Details -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Entry Date</label>
                    <input type="date" name="entry_date" value="{{ date('Y-m-d') }}" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600">
                </div>
                <div>
                    <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Description</label>
                    <textarea name="description" rows="1" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600" placeholder="Brief description of the transaction"></textarea>
                </div>
            </div>

            <!-- Journal Lines -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Journal Lines</h3>
                    <button type="button" onclick="addLine()" class="btn btn-sm btn-secondary">
                        <i data-lucide="plus" aria-hidden="true"></i> Add Line
                    </button>
                </div>
                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60">
                            <tr>
                                <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3">Account</th>
                                <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right w-36">Debit</th>
                                <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right w-36">Credit</th>
                                <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 w-12"></th>
                            </tr>
                        </thead>
                        <tbody id="linesBody" class="divide-y divide-slate-100 dark:divide-slate-800">
                        </tbody>
                        <tfoot class="bg-slate-50 dark:bg-slate-800/60 border-t-2 border-slate-200 dark:border-slate-700">
                            <tr>
                                <td class="px-4 py-3 font-bold text-slate-900 dark:text-white text-right">Totals</td>
                                <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white" id="totalDebit">0.00</td>
                                <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white" id="totalCredit">0.00</td>
                                <td class="px-4 py-3 text-center" id="balanceIndicator">
                                    <i data-lucide="x-circle" class="w-4 h-4 text-red-500"></i>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
            <span id="balanceMessage" class="text-[10px] font-semibold text-red-500">Entry is not balanced</span>
            <button type="submit" id="submitBtn" disabled class="btn btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                <i data-lucide="save" aria-hidden="true"></i> Save
            </button>
        </div>
    </form>
</div>

<script>
let lineCount = 0;
const accounts = @json($accounts);

function buildOptions(selected) {
    let html = '<option value="">Select account...</option>';
    accounts.forEach(a => {
        html += `<option value="${a.id}" ${a.id == selected ? 'selected' : ''}>${a.code} - ${a.name}</option>`;
    });
    return html;
}

function addLine(selectedAccount) {
    const tbody = document.getElementById('linesBody');
    const idx = lineCount++;
    const tr = document.createElement('tr');
    tr.id = 'line-' + idx;
    tr.innerHTML = `
        <td class="px-4 py-2.5">
            <select name="lines[${idx}][account_id]" required onchange="recalculate()" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600">
                ${buildOptions(selectedAccount || '')}
            </select>
        </td>
        <td class="px-4 py-2.5">
            <input type="number" name="lines[${idx}][debit]" step="0.01" min="0" value="0" oninput="recalculate()" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-1.5 text-xs text-right text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600">
        </td>
        <td class="px-4 py-2.5">
            <input type="number" name="lines[${idx}][credit]" step="0.01" min="0" value="0" oninput="recalculate()" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-1.5 text-xs text-right text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600">
        </td>
        <td class="px-4 py-2.5 text-center">
            ${idx > 1 ? `<button type="button" onclick="removeLine(${idx})" class="p-1 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition"><i data-lucide="x" class="w-4 h-4"></i></button>` : ''}
        </td>
    `;
    tbody.appendChild(tr);
    if (typeof lucide !== 'undefined') lucide.createIcons();
    recalculate();
}

function removeLine(idx) {
    const row = document.getElementById('line-' + idx);
    if (row) row.remove();
    recalculate();
}

function recalculate() {
    let totalDebit = 0;
    let totalCredit = 0;
    document.querySelectorAll('#linesBody input[type="number"]').forEach(input => {
        const val = parseFloat(input.value) || 0;
        if (input.name.includes('[debit]')) totalDebit += val;
        if (input.name.includes('[credit]')) totalCredit += val;
    });

    document.getElementById('totalDebit').textContent = totalDebit.toFixed(2);
    document.getElementById('totalCredit').textContent = totalCredit.toFixed(2);

    const balanced = Math.abs(totalDebit - totalCredit) < 0.005 && totalDebit > 0;
    const indicator = document.getElementById('balanceIndicator');
    const message = document.getElementById('balanceMessage');
    const submitBtn = document.getElementById('submitBtn');

    if (balanced) {
        indicator.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i>';
        message.textContent = 'Entry is balanced';
        message.className = 'text-[10px] font-semibold text-emerald-500';
        submitBtn.disabled = false;
    } else {
        indicator.innerHTML = '<i data-lucide="x-circle" class="w-4 h-4 text-red-500"></i>';
        message.textContent = 'Entry is not balanced';
        message.className = 'text-[10px] font-semibold text-red-500';
        submitBtn.disabled = true;
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// Start with 2 lines
addLine();
addLine();
</script>
@endsection
