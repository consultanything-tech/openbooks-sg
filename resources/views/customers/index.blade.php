@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<div class="space-y-6">
    @if($errors->any())
    <div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-700 dark:text-red-400 text-xs" role="alert" aria-live="assertive">
        <ul class="list-disc pl-4 space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Customers</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage client contacts, billing addresses, and accounts receivable</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <button onclick="document.getElementById('importModal').showModal()" class="btn btn-secondary">
                <i data-lucide="file-input" aria-hidden="true"></i> Import CSV
            </button>
            <a href="{{ route('customers.export_csv') }}" class="btn btn-secondary">
                <i data-lucide="file-spreadsheet" aria-hidden="true"></i> Export CSV
            </a>
            <button onclick="openCustomerModal()" class="btn btn-primary">
                <i data-lucide="plus" aria-hidden="true"></i> New Customer
            </button>
        </div>
    </div>

    <!-- Search -->
    <div>
        <input type="text" id="searchInput" placeholder="Search by name or email..." aria-label="Search customers" oninput="filterTable()" class="w-full sm:w-80 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 placeholder-slate-400 dark:placeholder-slate-500">
    </div>

    <!-- Customers List -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="ob-bulk-bar" data-bulk-delete="{{ route('customers.bulk_delete') }}">
            <span class="ob-bulk-count">0 selected</span>
            <div class="ob-bulk-actions">
                <button type="button" class="btn btn-danger" data-bulk="delete">
                    <i data-lucide="trash-2" aria-hidden="true"></i> Delete selected
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table id="dataTable" class="w-full text-left text-xs responsive-table">
                <caption class="sr-only">List of customers with contact details, invoice counts, and outstanding balances</caption>
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th scope="col" class="ob-check-col px-4 py-3">
                            <input type="checkbox" id="selectAll" aria-label="Select all customers"
                                class="w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-0 focus:ring-offset-0 cursor-pointer">
                        </th>
                        <th scope="col" class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Name</th>
                        <th scope="col" class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Email</th>
                        <th scope="col" class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Phone</th>
                        <th scope="col" class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Location</th>
                        <th scope="col" class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Invoices</th>
                        <th scope="col" class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Outstanding</th>
                        <th scope="col" class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($customers as $c)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="ob-check-col px-4 py-3" data-label="">
                                <input type="checkbox" class="ob-row-check w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-0 focus:ring-offset-0 cursor-pointer"
                                    value="{{ $c->id }}" aria-label="Select customer {{ $c->name }}">
                            </td>
                            <td data-label="Name" class="px-4 py-3 text-xs font-bold text-slate-900 dark:text-white">
                                <a href="{{ route('customers.show', $c->id) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">{{ $c->name }}</a>
                            </td>
                            <td data-label="Email" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">{{ $c->email }}</td>
                            <td data-label="Phone" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">{{ $c->phone ?? '—' }}</td>
                            <td data-label="Location" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">{{ $c->city ? $c->city . ', ' . $c->country : '—' }}</td>
                            <td data-label="Invoices" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300 text-right font-semibold">{{ $c->invoices_count }}</td>
                            <td data-label="Outstanding" class="px-4 py-3 text-xs text-right font-bold {{ $c->outstanding_balance > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400' }}">
                                {{ $company->currency_symbol ?? ($currencySymbol ?? 'S$') }}{{ number_format($c->outstanding_balance, 2) }}
                            </td>
                            <td data-label="Actions" class="px-4 py-3 text-xs text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('customers.show', $c->id) }}" class="btn-icon" title="View" aria-label="View customer {{ $c->name }}">
                                        <i data-lucide="eye" aria-hidden="true"></i>
                                    </a>
                                    <button onclick="editCustomer({{ $c->id }})" class="btn-icon" title="Edit" aria-label="Edit customer {{ $c->name }}">
                                        <i data-lucide="pencil" aria-hidden="true"></i>
                                    </button>
                                    <form action="{{ route('customers.destroy', $c->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this customer? This cannot be undone.')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete" aria-label="Delete customer {{ $c->name }}">
                                            <i data-lucide="trash-2" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-empty-state
                                    icon="users"
                                    title="No customers yet"
                                    message="Add your first customer to start sending invoices" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-pagination :paginator="$customers" />
    </div>

    <!-- Import CSV Modal -->
    <dialog id="importModal" aria-labelledby="importModalTitle" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 max-w-lg w-full shadow-xl backdrop:bg-black/50 backdrop:backdrop-blur-sm">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 id="importModalTitle" class="text-base font-bold text-slate-900 dark:text-white">Import Customers from CSV</h3>
                <button onclick="document.getElementById('importModal').close()" class="btn-icon" aria-label="Close">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </div>

            <form action="{{ route('customers.import_csv') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label for="csvFileInput" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">CSV File <span aria-hidden="true">*</span></label>
                    <input type="file" name="csv_file" id="csvFileInput" accept=".csv,.txt" required aria-required="true" aria-describedby="csvHelpText" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-500/10 dark:file:text-indigo-400">
                </div>

                <div id="csvHelpText" class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Expected CSV Columns:</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">Name, Email, Phone, Company, Tax Number, Address, City, Country</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2">Duplicate emails will update the existing customer record. Rows without a name will be skipped.</p>
                </div>

                <div class="pt-2 flex items-center justify-between">
                    <a href="{{ route('customers.import_template') }}" class="btn btn-secondary">
                        <i data-lucide="download" aria-hidden="true"></i> Download Template
                    </a>
                    <div class="flex gap-3">
                        <button type="button" onclick="document.getElementById('importModal').close()" class="btn btn-ghost">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i data-lucide="save" aria-hidden="true"></i> Save</button>
                    </div>
                </div>
            </form>
        </div>
    </dialog>

    <!-- Customer Modal (Create / Edit) -->
    <div id="addCustomerModal" role="dialog" aria-modal="true" aria-labelledby="customerModalTitle" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 max-w-lg w-full shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 id="customerModalTitle" class="text-base font-bold text-slate-900 dark:text-white">New Customer</h3>
                <button onclick="closeCustomerModal()" class="btn-icon" aria-label="Close">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </div>

            <form id="customerForm" action="{{ route('customers.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="_method" id="customerMethod" value="POST">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="customerName" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Company / Name <span aria-hidden="true">*</span></label>
                        <input type="text" name="name" id="customerName" value="{{ old('name') }}" required aria-required="true" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        @error('name')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="customerEmail" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Email Address <span aria-hidden="true">*</span></label>
                        <input type="email" name="email" id="customerEmail" value="{{ old('email') }}" required aria-required="true" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        @error('email')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="customerPhone" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Phone Number</label>
                        <input type="text" name="phone" id="customerPhone" value="{{ old('phone') }}" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        @error('phone')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="customerTaxNumber" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Tax / VAT Number</label>
                        <input type="text" name="tax_number" id="customerTaxNumber" value="{{ old('tax_number') }}" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        @error('tax_number')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="customerAddress" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Billing Address</label>
                    <input type="text" name="address" id="customerAddress" placeholder="Street Address" value="{{ old('address') }}" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 mb-2">
                    @error('address')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    <div class="grid grid-cols-2 gap-3">
                        <label for="customerCity" class="sr-only">City</label>
                        <input type="text" name="city" id="customerCity" placeholder="City" value="{{ old('city') }}" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        @error('city')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                        <label for="customerCountry" class="sr-only">Country</label>
                        <input type="text" name="country" id="customerCountry" placeholder="Country" value="{{ old('country', 'United States') }}" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        @error('country')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" onclick="closeCustomerModal()" class="btn btn-ghost">Cancel</button>
                    <button type="submit" id="customerSubmitBtn" class="btn btn-primary"><i data-lucide="save" aria-hidden="true"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let lastFocusedElement = null;

function openCustomerModal() {
    lastFocusedElement = document.activeElement;
    document.getElementById('customerModalTitle').textContent = 'New Customer';
    document.getElementById('customerSubmitBtn').textContent = 'Save Customer';
    document.getElementById('customerForm').action = "{{ route('customers.store') }}";
    document.getElementById('customerMethod').value = 'POST';
    document.getElementById('customerName').value = '';
    document.getElementById('customerEmail').value = '';
    document.getElementById('customerPhone').value = '';
    document.getElementById('customerTaxNumber').value = '';
    document.getElementById('customerAddress').value = '';
    document.getElementById('customerCity').value = '';
    document.getElementById('customerCountry').value = 'United States';
    document.getElementById('addCustomerModal').classList.remove('hidden');
    document.getElementById('customerName').focus();
    if (typeof announceToSR === 'function') announceToSR('New Customer dialog opened');
}

function closeCustomerModal() {
    document.getElementById('addCustomerModal').classList.add('hidden');
    if (lastFocusedElement) lastFocusedElement.focus();
    if (typeof announceToSR === 'function') announceToSR('Dialog closed');
}

// WCAG 2.1 AA: Close modal on Escape key (2.1.2 No Keyboard Trap)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('addCustomerModal').classList.contains('hidden')) {
        closeCustomerModal();
    }
});

function editCustomer(id) {
    var modal = document.getElementById('addCustomerModal');
    modal.classList.remove('hidden');
    obModalLoading(modal.firstElementChild || modal, true);
    fetch('/customers/' + id + '/edit', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        const c = data.customer || data;
        document.getElementById('customerModalTitle').textContent = 'Edit Customer';
        document.getElementById('customerSubmitBtn').textContent = 'Update Customer';
        document.getElementById('customerForm').action = '/customers/' + id;
        document.getElementById('customerMethod').value = 'PUT';
        document.getElementById('customerName').value = c.name || '';
        document.getElementById('customerEmail').value = c.email || '';
        document.getElementById('customerPhone').value = c.phone || '';
        document.getElementById('customerTaxNumber').value = c.tax_number || '';
        document.getElementById('customerAddress').value = c.address || '';
        document.getElementById('customerCity').value = c.city || '';
        document.getElementById('customerCountry').value = c.country || 'United States';
        obModalLoading(modal.firstElementChild || modal, false);
        lastFocusedElement = document.activeElement;
        document.getElementById('customerName').focus();
        if (typeof announceToSR === 'function') announceToSR('Edit Customer dialog opened');
    })
    .catch(function () {
        obModalLoading(modal.firstElementChild || modal, false);
        modal.classList.add('hidden');
        if (typeof obToast === 'function') obToast('Could not load customer details.', { type: 'error' });
    });
}
</script>
@endsection
