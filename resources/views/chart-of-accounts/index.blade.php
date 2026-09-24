@extends('layouts.app')

@section('title', 'Chart of Accounts')

@section('content')
<div class="space-y-6">
    @if($errors->any())
    <div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-700 dark:text-red-400 text-xs">
        <ul class="list-disc pl-4 space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    @if(session('success'))
    <div class="mb-4 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-xs">
        <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i>{{ session('success') }}
    </div>
    @endif

    @php $currencySymbol = $company->currency_symbol ?? 'S$'; @endphp

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Chart of Accounts</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage your company's account structure</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            @if($accounts->isEmpty())
            <form action="{{ route('accounts.seed_defaults') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn btn-secondary">
                    <i data-lucide="database" aria-hidden="true"></i> Seed Defaults
                </button>
            </form>
            @endif
            @canEdit
            <button onclick="openModal('addAccountModal')" class="btn btn-primary">
                <i data-lucide="plus" aria-hidden="true"></i>
                New Account
            </button>
            @endcanEdit
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="flex items-center gap-1 border-b border-slate-200 dark:border-slate-800">
        <a href="{{ route('accounts.index') }}" class="px-4 py-2.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 dark:border-indigo-400 transition">
            <i data-lucide="git-branch" class="w-4 h-4 mr-1.5"></i>Chart of Accounts
        </a>
        <a href="{{ route('accounts.journal_entries') }}" class="px-4 py-2.5 text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
            <i data-lucide="book" class="w-4 h-4 mr-1.5"></i>Journal Entries
        </a>
        <a href="{{ route('accounts.trial_balance') }}" class="px-4 py-2.5 text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
            <i data-lucide="scale" class="w-4 h-4 mr-1.5"></i>Trial Balance
        </a>
    </div>

    <!-- Account Groups -->
    @php
        $typeConfig = [
            'asset' => ['label' => 'Assets', 'icon' => 'building', 'color' => 'text-blue-600 dark:text-blue-400', 'bg' => 'bg-blue-50 dark:bg-blue-500/10'],
            'liability' => ['label' => 'Liabilities', 'icon' => 'credit-card', 'color' => 'text-red-600 dark:text-red-400', 'bg' => 'bg-red-50 dark:bg-red-500/10'],
            'equity' => ['label' => 'Equity', 'icon' => 'scale', 'color' => 'text-purple-600 dark:text-purple-400', 'bg' => 'bg-purple-50 dark:bg-purple-500/10'],
            'revenue' => ['label' => 'Revenue', 'icon' => 'trending-up', 'color' => 'text-emerald-600 dark:text-emerald-400', 'bg' => 'bg-emerald-50 dark:bg-emerald-500/10'],
            'expense' => ['label' => 'Expenses', 'icon' => 'trending-down', 'color' => 'text-amber-600 dark:text-amber-400', 'bg' => 'bg-amber-50 dark:bg-amber-500/10'],
        ];
    @endphp

    @foreach($typeConfig as $type => $config)
        @php $groupAccounts = $accounts->get($type, collect()); @endphp
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm overflow-hidden">
            <button onclick="toggleGroup('{{ $type }}')" class="w-full flex items-center justify-between px-5 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl {{ $config['bg'] }} flex items-center justify-center">
                        <i data-lucide="{{ $config['icon'] }}" class="w-4 h-4 {{ $config['color'] }}"></i>
                    </div>
                    <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $config['label'] }}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">{{ $groupAccounts->count() }}</span>
                </div>
                <i data-lucide="chevron-down" id="chevron-{{ $type }}" class="w-4 h-4 text-slate-400 transition-transform duration-200"></i>
            </button>
            <div id="group-{{ $type }}" class="hidden">
                @if($groupAccounts->isNotEmpty())
                <div class="overflow-x-auto border-t border-slate-100 dark:border-slate-800">
                    <table class="w-full text-left text-xs responsive-table">
                        <thead class="bg-slate-50 dark:bg-slate-800/60">
                            <tr>
                                <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3">Code</th>
                                <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3">Name</th>
                                <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3">Sub-Type</th>
                                <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Balance</th>
                                @canEdit
                                <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Actions</th>
                                @endcanEdit
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            @foreach($groupAccounts as $account)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition {{ !$account->is_active ? 'opacity-50' : '' }}">
                                <td class="px-4 py-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $account->code }}</td>
                                <td class="px-4 py-3">
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ $account->name }}</span>
                                    @if($account->is_system)
                                        <span class="ml-1.5 px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 uppercase">System</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($account->sub_type)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">{{ ucfirst(str_replace('_', ' ', $account->sub_type)) }}</span>
                                    @else
                                    <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($account->balance, 2) }}</td>
                                @canEdit
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button onclick='openEditModal(@json($account))' class="btn-icon" title="Edit account" aria-label="Edit account">
                                            <i data-lucide="pencil" aria-hidden="true"></i>
                                        </button>
                                        @if(!$account->is_system)
                                        <form action="{{ route('accounts.destroy', $account->id) }}" method="POST" onsubmit="return confirm('Delete account {{ $account->name }}? This cannot be undone.')" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Delete account" aria-label="Delete account">
                                                <i data-lucide="trash-2" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                                @endcanEdit
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="px-6 py-10 text-center">
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-3">
                            <i data-lucide="folder-open" class="w-6 h-6 text-slate-400 dark:text-slate-500" aria-hidden="true"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">No accounts in this category yet.</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">Add accounts to this group to start tracking.</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    @endforeach

    <!-- Add Account Modal -->
    <div id="addAccountModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="w-full max-w-lg mx-4 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Add Account</h2>
                <button onclick="closeModal('addAccountModal')" class="btn-icon" aria-label="Close"><i data-lucide="x" aria-hidden="true"></i></button>
            </div>
            <form action="{{ route('accounts.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Code</label>
                        <input type="text" name="code" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Type</label>
                        <select name="type" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600">
                            <option value="asset">Asset</option>
                            <option value="liability">Liability</option>
                            <option value="equity">Equity</option>
                            <option value="revenue">Revenue</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Name</label>
                    <input type="text" name="name" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600">
                </div>
                <div>
                    <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Sub-Type</label>
                    <input type="text" name="sub_type" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600" placeholder="e.g. current_asset, operating">
                </div>
                <div>
                    <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Description</label>
                    <textarea name="description" rows="2" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600"></textarea>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Parent Account</label>
                    <select name="parent_id" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600">
                        <option value="">None (Top-level)</option>
                        @foreach($accounts->flatten() as $a)
                            <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('addAccountModal')" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="save" aria-hidden="true"></i> Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Account Modal -->
    <div id="editAccountModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="w-full max-w-lg mx-4 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Edit Account</h2>
                <button onclick="closeModal('editAccountModal')" class="btn-icon" aria-label="Close"><i data-lucide="x" aria-hidden="true"></i></button>
            </div>
            <form id="editAccountForm" action="" method="POST" class="p-6 space-y-4">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Code</label>
                        <input type="text" name="code" id="edit_code" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Type</label>
                        <select name="type" id="edit_type" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600">
                            <option value="asset">Asset</option>
                            <option value="liability">Liability</option>
                            <option value="equity">Equity</option>
                            <option value="revenue">Revenue</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Name</label>
                    <input type="text" name="name" id="edit_name" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600">
                </div>
                <div>
                    <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Sub-Type</label>
                    <input type="text" name="sub_type" id="edit_sub_type" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600">
                </div>
                <div>
                    <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Description</label>
                    <textarea name="description" id="edit_description" rows="2" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600"></textarea>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Parent Account</label>
                    <select name="parent_id" id="edit_parent_id" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600">
                        <option value="">None (Top-level)</option>
                        @foreach($accounts->flatten() as $a)
                            <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500">
                        Active
                    </label>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('editAccountModal')" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="save" aria-hidden="true"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleGroup(type) {
    const el = document.getElementById('group-' + type);
    const chevron = document.getElementById('chevron-' + type);
    el.classList.toggle('hidden');
    chevron.style.transform = el.classList.contains('hidden') ? '' : 'rotate(180deg)';
}

function openModal(id) {
    const modal = document.getElementById(id);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeModal(id) {
    const modal = document.getElementById(id);
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function openEditModal(account) {
    const base = '{{ url('/') }}';
    document.getElementById('editAccountForm').action = base + '/accounts/' + account.id;
    document.getElementById('edit_code').value = account.code;
    document.getElementById('edit_type').value = account.type;
    document.getElementById('edit_name').value = account.name;
    document.getElementById('edit_sub_type').value = account.sub_type || '';
    document.getElementById('edit_description').value = account.description || '';
    document.getElementById('edit_parent_id').value = account.parent_id || '';
    document.getElementById('edit_is_active').checked = account.is_active;

    // Disable code and type for system accounts
    document.getElementById('edit_code').disabled = account.is_system;
    document.getElementById('edit_type').disabled = account.is_system;

    openModal('editAccountModal');
}

// Close modals on backdrop click
document.querySelectorAll('[id$="Modal"]').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});
</script>
@endsection
