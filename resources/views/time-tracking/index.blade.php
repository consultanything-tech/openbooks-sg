@extends('layouts.app')

@section('title', 'Time Tracking')

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
        {{ session('success') }}
    </div>
    @endif

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Time Tracking</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Log and manage billable hours for customer projects</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('time_tracking.export_csv', request()->query()) }}" class="btn btn-secondary">
                <i data-lucide="file-spreadsheet" aria-hidden="true"></i> Export CSV
            </a>
            @canEdit
            <a href="{{ route('time_tracking.create') }}" class="btn btn-primary">
                <i data-lucide="plus" aria-hidden="true"></i> New Time Entry
            </a>
            @endcanEdit
        </div>
    </div>

    <!-- Search -->
    <div>
        <input type="text" id="searchInput" placeholder="Search by project, description, or customer..." aria-label="Search time entries" oninput="filterTable()" class="w-full sm:w-80 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 placeholder-slate-400 dark:placeholder-slate-500">
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('time_tracking.index') }}" class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm flex flex-wrap items-center gap-4 text-xs">
        <div class="min-w-[150px]">
            <select data-combobox name="customer_id" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                <option value="">All Customers</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[130px]">
            <input type="text" name="project" value="{{ request('project') }}" placeholder="Project" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 placeholder-slate-400 dark:placeholder-slate-500">
        </div>
        <div>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
        </div>
        <div>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
        </div>
        <div class="min-w-[120px]">
            <select name="billable" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                <option value="">All Billable</option>
                <option value="1" {{ request('billable') == '1' ? 'selected' : '' }}>Billable</option>
                <option value="0" {{ request('billable') === '0' && request('billable') !== '' ? 'selected' : '' }}>Non-Billable</option>
            </select>
        </div>
        <div class="min-w-[120px]">
            <select name="invoiced" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                <option value="">All Invoiced</option>
                <option value="1" {{ request('invoiced') == '1' ? 'selected' : '' }}>Invoiced</option>
                <option value="0" {{ request('invoiced') === '0' && request('invoiced') !== '' ? 'selected' : '' }}>Not Invoiced</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">
            <i data-lucide="filter" aria-hidden="true"></i> Filter
        </button>
        @if(request()->hasAny(['customer_id', 'project', 'date_from', 'date_to', 'billable', 'invoiced']))
            <a href="{{ route('time_tracking.index') }}" class="text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">Clear</a>
        @endif
    </form>

    <!-- Convert to Invoice Button -->
    @canEdit
    <div class="flex items-center gap-3">
        <button type="button" onclick="toggleConvertModal()" class="btn btn-primary">
            <i data-lucide="file-text" aria-hidden="true"></i> Convert to Invoice
        </button>
        <span id="selectedCount" class="text-[10px] text-slate-500 dark:text-slate-400"></span>
    </div>
    @endcanEdit

    <!-- Entries Table -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="dataTable" class="w-full text-left text-xs responsive-table">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left w-8">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" class="rounded border-slate-300 dark:border-slate-600">
                        </th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Date</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Customer</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Project</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Hours</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Rate</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Amount</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Billable</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Invoiced</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($entries as $entry)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">
                                @if(!$entry->is_invoiced)
                                <input type="checkbox" name="entry_ids[]" value="{{ $entry->id }}" class="entry-checkbox rounded border-slate-300 dark:border-slate-600" data-customer-id="{{ $entry->customer_id }}" onchange="updateSelectedCount()">
                                @endif
                            </td>
                            <td data-label="Date" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">{{ date('M d, Y', strtotime($entry->entry_date)) }}</td>
                            <td data-label="Customer" class="px-4 py-3 text-xs font-medium text-slate-900 dark:text-white">{{ $entry->customer->name ?? 'N/A' }}</td>
                            <td data-label="Project" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">{{ $entry->project ?? '-' }}</td>
                            <td data-label="Hours" class="px-4 py-3 text-xs text-right font-mono text-slate-900 dark:text-white">{{ number_format($entry->hours, 2) }}</td>
                            <td data-label="Rate" class="px-4 py-3 text-xs text-right font-mono text-slate-700 dark:text-slate-300">{{ $currencySymbol }}{{ number_format($entry->rate, 2) }}</td>
                            <td data-label="Amount" class="px-4 py-3 text-xs text-right font-bold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($entry->amount, 2) }}</td>
                            <td data-label="Billable" class="px-4 py-3 text-xs">
                                @if($entry->is_billable)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">Yes</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">No</span>
                                @endif
                            </td>
                            <td data-label="Invoiced" class="px-4 py-3 text-xs">
                                @if($entry->is_invoiced)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20">Yes</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20">No</span>
                                @endif
                            </td>
                            <td data-label="Actions" class="px-4 py-3 text-xs text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('time_tracking.show', $entry->id) }}" class="btn-icon" title="View" aria-label="View">
                                        <i data-lucide="eye" aria-hidden="true"></i>
                                    </a>
                                    @canEdit
                                    @if(!$entry->is_invoiced)
                                        <a href="{{ route('time_tracking.edit', $entry->id) }}" class="btn-icon" title="Edit" aria-label="Edit">
                                            <i data-lucide="pencil" aria-hidden="true"></i>
                                        </a>
                                        <form action="{{ route('time_tracking.destroy', $entry->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this time entry? This cannot be undone.')" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Delete" aria-label="Delete">
                                                <i data-lucide="trash-2" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @endcanEdit
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                @canEdit
                                    <x-empty-state
                                        icon="clock"
                                        title="No time entries yet"
                                        message="Start logging hours for your customer projects"
                                        :action-url="route('time_tracking.create')"
                                        action-label="New Time Entry" />
                                @else
                                    <x-empty-state
                                        icon="clock"
                                        title="No time entries yet"
                                        message="Start logging hours for your customer projects" />
                                @endcanEdit
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-pagination :paginator="$entries" />
    </div>
</div>

<!-- Convert to Invoice Modal -->
<div id="convertModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Convert to Invoice</h3>
            <button onclick="toggleConvertModal()" class="btn-icon" aria-label="Close">
                <i data-lucide="x" aria-hidden="true"></i>
            </button>
        </div>

        <form action="{{ route('time_tracking.convert') }}" method="POST" id="convertForm" class="space-y-4 text-xs">
            @csrf
            <div id="convertEntryIds"></div>

            <p class="text-xs text-slate-500 dark:text-slate-400">
                Selected <span id="convertCount" class="font-bold text-slate-900 dark:text-white">0</span> time entries will be converted to an invoice.
            </p>

            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5 block">Customer *</label>
                <select data-combobox name="customer_id" id="convertCustomerId" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    <option value="">Select Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="toggleConvertModal()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="file-text" aria-hidden="true"></i> Create Invoice
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSelectAll(el) {
    document.querySelectorAll('.entry-checkbox').forEach(cb => cb.checked = el.checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const checked = document.querySelectorAll('.entry-checkbox:checked');
    document.getElementById('selectedCount').textContent = checked.length ? checked.length + ' entries selected' : '';
}

function toggleConvertModal() {
    const modal = document.getElementById('convertModal');
    const checked = document.querySelectorAll('.entry-checkbox:checked');

    if (modal.classList.contains('hidden')) {
        if (checked.length === 0) {
            alert('Please select at least one time entry to convert.');
            return;
        }

        // Build hidden inputs
        const container = document.getElementById('convertEntryIds');
        container.innerHTML = '';
        let customerIds = new Set();
        checked.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'entry_ids[]';
            input.value = cb.value;
            container.appendChild(input);
            if (cb.dataset.customerId) customerIds.add(cb.dataset.customerId);
        });

        document.getElementById('convertCount').textContent = checked.length;

        // Auto-select customer if all entries belong to one
        if (customerIds.size === 1) {
            document.getElementById('convertCustomerId').value = [...customerIds][0];
        }

        modal.classList.remove('hidden');
    } else {
        modal.classList.add('hidden');
    }
}
</script>
@endsection
