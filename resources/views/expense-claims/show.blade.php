@extends('layouts.app')

@section('title', 'Expense Claim')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-4">
        <a href="{{ route('expense_claims.index') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">Expense Claims</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-slate-900 dark:text-white font-medium">{{ $claim->claim_number }}</span>
    </nav>

    <!-- Action Buttons -->
    <div class="flex items-center gap-2 flex-wrap">
        @if($claim->status === 'submitted')
            <button onclick="document.getElementById('approveForm').submit()" class="btn btn-success">
                <i data-lucide="check" aria-hidden="true"></i> Approve
            </button>
            <form id="approveForm" action="{{ route('expense_claims.approve', $claim->id) }}" method="POST" class="hidden">@csrf</form>

            <button onclick="document.getElementById('rejectModal').classList.remove('hidden')" class="btn btn-secondary">
                <i data-lucide="x" aria-hidden="true"></i> Reject
            </button>
        @endif

        @if($claim->status === 'approved')
            <button onclick="document.getElementById('markPaidModal').classList.remove('hidden')" class="btn btn-success">
                <i data-lucide="banknote" aria-hidden="true"></i> Mark as Paid
            </button>
        @endif

        @if($claim->status !== 'paid')
            <form action="{{ route('expense_claims.destroy', $claim->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this expense claim? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger-text">
                    Delete
                </button>
            </form>
        @endif
    </div>

    <!-- Status Badge & Title -->
    <div>
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{{ $claim->claim_number }}</h1>
            @php
                $badgeClasses = [
                    'draft'     => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700',
                    'submitted' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
                    'approved'  => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                    'rejected'  => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
                    'paid'      => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/20',
                ];
            @endphp
            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border uppercase {{ $badgeClasses[$claim->status] ?? 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700' }}">
                {{ $claim->status }}
            </span>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Filed by {{ $claim->employee_name ?? ($claim->user->name ?? 'N/A') }} &bull; {{ $claim->category ?? 'General' }}
        </p>
        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Last updated {{ $claim->updated_at ? $claim->updated_at->diffForHumans() : '—' }}</p>
    </div>

    <!-- Claim Details Card -->
    <div class="p-8 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-b border-slate-100 dark:border-slate-800 pb-6">
            <div class="space-y-4">
                <div>
                    <h3 class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Claim Number</h3>
                    <p class="text-sm font-bold text-slate-900 dark:text-white font-mono">{{ $claim->claim_number }}</p>
                </div>
                <div>
                    <h3 class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Employee</h3>
                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $claim->employee_name ?? ($claim->user->name ?? 'N/A') }}</p>
                </div>
                <div>
                    <h3 class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Date</h3>
                    <p class="text-sm text-slate-700 dark:text-slate-300">{{ date('M d, Y', strtotime($claim->claim_date)) }}</p>
                </div>
                <div>
                    <h3 class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Category</h3>
                    <p class="text-sm text-slate-700 dark:text-slate-300">{{ $claim->category ?? 'General' }}</p>
                </div>
            </div>
            <div class="space-y-4">
                <div>
                    <h3 class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Title</h3>
                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $claim->title }}</p>
                </div>
                <div>
                    <h3 class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Amount</h3>
                    <p class="text-xl font-extrabold text-slate-900 dark:text-white" style="color: {{ $company->accent_color ?? '#2563eb' }}">{{ $currencySymbol }}{{ number_format($claim->amount, 2) }}</p>
                </div>
                <div>
                    <h3 class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Status</h3>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold border uppercase {{ $badgeClasses[$claim->status] ?? 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700' }}">
                        {{ $claim->status }}
                    </span>
                </div>
            </div>
        </div>

        @if($claim->description)
        <div class="border-b border-slate-100 dark:border-slate-800 pb-6">
            <h3 class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Description</h3>
            <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ $claim->description }}</p>
        </div>
        @endif

        <!-- Receipt Preview -->
        @if($claim->receipt_path)
        <div class="border-b border-slate-100 dark:border-slate-800 pb-6">
            <h3 class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">Receipt</h3>
            @if(Str::endsWith(strtolower($claim->receipt_path), ['.jpg', '.jpeg', '.png', '.gif', '.webp']))
                <div class="max-w-sm">
                    <img src="{{ asset('storage/' . $claim->receipt_path) }}" alt="Receipt" class="rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm max-w-full h-auto">
                </div>
            @else
                <a href="{{ asset('storage/' . $claim->receipt_path) }}" target="_blank" class="btn btn-secondary">
                    <i data-lucide="file" class="text-red-500" aria-hidden="true"></i> View Receipt (PDF)
                </a>
            @endif
        </div>
        @endif

        <!-- Rejection Reason -->
        @if($claim->status === 'rejected' && $claim->rejection_reason)
        <div class="p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-500/20 flex items-center justify-center shrink-0">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 dark:text-red-400"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-red-800 dark:text-red-400 mb-1">Claim Rejected</h4>
                    <p class="text-xs text-red-700 dark:text-red-300">{{ $claim->rejection_reason }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Paid Confirmation -->
        @if($claim->status === 'paid')
        <div class="p-4 rounded-xl bg-purple-50 dark:bg-purple-500/10 border border-purple-200 dark:border-purple-500/20">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-500/20 flex items-center justify-center shrink-0">
                    <i data-lucide="check-circle" class="w-4 h-4 text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-purple-800 dark:text-purple-400 mb-1">Reimbursement Paid</h4>
                    <p class="text-xs text-purple-700 dark:text-purple-300">This expense claim has been reimbursed for {{ $currencySymbol }}{{ number_format($claim->amount, 2) }}.</p>
                    @if($claim->paid_at)
                        <p class="text-[10px] text-purple-500 dark:text-purple-400 mt-1">Paid on {{ date('M d, Y', strtotime($claim->paid_at)) }}</p>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Reject Expense Claim</h3>
                <button onclick="document.getElementById('rejectModal').classList.add('hidden')" class="btn-icon" aria-label="Close">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </div>

            <form action="{{ route('expense_claims.reject', $claim->id) }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5 block">Rejection Reason *</label>
                    <textarea name="rejection_reason" rows="3" required placeholder="Provide a reason for rejecting this claim..."
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Claim</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Mark as Paid Modal -->
    <div id="markPaidModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Mark as Paid</h3>
                <button onclick="document.getElementById('markPaidModal').classList.add('hidden')" class="btn-icon" aria-label="Close">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </div>

            <p class="text-xs text-slate-500 dark:text-slate-400">
                Confirm reimbursement of <span class="font-bold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($claim->amount, 2) }}</span> to {{ $claim->employee_name ?? ($claim->user->name ?? 'employee') }}.
            </p>

            <form action="{{ route('expense_claims.mark_paid', $claim->id) }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5 block">Deduct From Bank Account *</label>
                    <select name="bank_account_id" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                        @foreach($bankAccounts ?? [] as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Bal: {{ $currencySymbol }}{{ number_format($acc->current_balance, 2) }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('markPaidModal').classList.add('hidden')" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>

    <x-activity-timeline model-type="ExpenseClaim" :model-id="$claim->id" />
</div>
@endsection
