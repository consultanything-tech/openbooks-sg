@extends('layouts.app')

@section('title', 'Vendors')

@section('content')
<div class="space-y-6">
    @if($errors->any())
    <div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-700 dark:text-red-400 text-xs">
        <ul class="list-disc pl-4 space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Vendors & Suppliers</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage supplier profiles, purchase bills, and accounts payable</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('vendors.export_csv') }}" class="btn btn-secondary">
                <i data-lucide="file-spreadsheet" aria-hidden="true"></i> Export CSV
            </a>
            <button onclick="openVendorModal()" class="btn btn-primary">
                <i data-lucide="plus" aria-hidden="true"></i> New Vendor
            </button>
        </div>
    </div>

    <!-- Search -->
    <div>
        <input type="text" id="searchInput" placeholder="Search by name or email..." aria-label="Search vendors" oninput="filterTable()" class="w-full sm:w-80 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 placeholder-slate-400 dark:placeholder-slate-500">
    </div>

    <!-- Vendors List -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="ob-bulk-bar" data-bulk-delete="{{ route('vendors.bulk_delete') }}">
            <span class="ob-bulk-count">0 selected</span>
            <div class="ob-bulk-actions">
                <button type="button" class="btn btn-danger" data-bulk="delete">
                    <i data-lucide="trash-2" aria-hidden="true"></i> Delete selected
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table id="dataTable" class="w-full text-left text-xs responsive-table">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th scope="col" class="ob-check-col px-4 py-3">
                            <input type="checkbox" id="selectAll" aria-label="Select all vendors"
                                class="w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-0 focus:ring-offset-0 cursor-pointer">
                        </th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Vendor Name</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Email</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Phone</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Location</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Bills</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Payable Balance</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($vendors as $v)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="ob-check-col px-4 py-3" data-label="">
                                <input type="checkbox" class="ob-row-check w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-0 focus:ring-offset-0 cursor-pointer"
                                    value="{{ $v->id }}" aria-label="Select vendor {{ $v->name }}">
                            </td>
                            <td data-label="Vendor" class="px-4 py-3 text-xs font-bold text-slate-900 dark:text-white">
                                <a href="{{ route('vendors.show', $v->id) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">{{ $v->name }}</a>
                            </td>
                            <td data-label="Email" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">{{ $v->email }}</td>
                            <td data-label="Phone" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">{{ $v->phone ?? '—' }}</td>
                            <td data-label="Location" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">{{ $v->city ? $v->city . ', ' . $v->country : '—' }}</td>
                            <td data-label="Bills" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300 text-right font-semibold">{{ $v->bills_count }}</td>
                            <td data-label="Payable" class="px-4 py-3 text-xs text-right font-bold {{ $v->outstanding_balance > 0 ? 'text-red-700 dark:text-red-400' : 'text-slate-500 dark:text-slate-400' }}">
                                {{ $company->currency_symbol ?? ($currencySymbol ?? 'S$') }}{{ number_format($v->outstanding_balance, 2) }}
                            </td>
                            <td data-label="Actions" class="px-4 py-3 text-xs text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('vendors.show', $v->id) }}" class="btn-icon" title="View" aria-label="View vendor {{ $v->name }}">
                                        <i data-lucide="eye" aria-hidden="true"></i>
                                    </a>
                                    <button onclick="editVendor({{ $v->id }})" class="btn-icon" title="Edit" aria-label="Edit vendor {{ $v->name }}">
                                        <i data-lucide="pencil" aria-hidden="true"></i>
                                    </button>
                                    <form action="{{ route('vendors.destroy', $v->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this vendor? This cannot be undone.')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete" aria-label="Delete vendor {{ $v->name }}">
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
                                    icon="truck"
                                    title="No vendors yet"
                                    message="Add your first vendor to start tracking bills and expenses" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-pagination :paginator="$vendors" />
    </div>

    <!-- Vendor Modal (Create / Edit) -->
    <div id="addVendorModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 max-w-lg w-full shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 id="vendorModalTitle" class="text-base font-bold text-slate-900 dark:text-white">New Vendor</h3>
                <button onclick="closeVendorModal()" class="btn-icon" aria-label="Close">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </div>

            <form id="vendorForm" action="{{ route('vendors.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="_method" id="vendorMethod" value="POST">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Company / Vendor Name *</label>
                        <input type="text" name="name" id="vendorName" value="{{ old('name') }}" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        @error('name')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Email Address *</label>
                        <input type="email" name="email" id="vendorEmail" value="{{ old('email') }}" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        @error('email')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Phone Number</label>
                        <input type="text" name="phone" id="vendorPhone" value="{{ old('phone') }}" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        @error('phone')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Tax Number</label>
                        <input type="text" name="tax_number" id="vendorTaxNumber" value="{{ old('tax_number') }}" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        @error('tax_number')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Address</label>
                    <input type="text" name="address" id="vendorAddress" placeholder="Street Address" value="{{ old('address') }}" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 mb-2">
                    @error('address')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    <div class="grid grid-cols-2 gap-3">
                        <input type="text" name="city" id="vendorCity" placeholder="City" value="{{ old('city') }}" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        @error('city')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                        <input type="text" name="country" id="vendorCountry" placeholder="Country" value="{{ old('country', 'United States') }}" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        @error('country')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" onclick="closeVendorModal()" class="btn btn-ghost">Cancel</button>
                    <button type="submit" id="vendorSubmitBtn" class="btn btn-primary"><i data-lucide="save" aria-hidden="true"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openVendorModal() {
    document.getElementById('vendorModalTitle').textContent = 'New Vendor';
    document.getElementById('vendorSubmitBtn').textContent = 'Save Vendor';
    document.getElementById('vendorForm').action = "{{ route('vendors.store') }}";
    document.getElementById('vendorMethod').value = 'POST';
    document.getElementById('vendorName').value = '';
    document.getElementById('vendorEmail').value = '';
    document.getElementById('vendorPhone').value = '';
    document.getElementById('vendorTaxNumber').value = '';
    document.getElementById('vendorAddress').value = '';
    document.getElementById('vendorCity').value = '';
    document.getElementById('vendorCountry').value = 'United States';
    document.getElementById('addVendorModal').classList.remove('hidden');
}

function closeVendorModal() {
    document.getElementById('addVendorModal').classList.add('hidden');
}

function editVendor(id) {
    var modal = document.getElementById('addVendorModal');
    modal.classList.remove('hidden');
    obModalLoading(modal.firstElementChild || modal, true);
    fetch('/vendors/' + id + '/edit', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        const v = data.vendor || data;
        document.getElementById('vendorModalTitle').textContent = 'Edit Vendor';
        document.getElementById('vendorSubmitBtn').textContent = 'Update Vendor';
        document.getElementById('vendorForm').action = '/vendors/' + id;
        document.getElementById('vendorMethod').value = 'PUT';
        document.getElementById('vendorName').value = v.name || '';
        document.getElementById('vendorEmail').value = v.email || '';
        document.getElementById('vendorPhone').value = v.phone || '';
        document.getElementById('vendorTaxNumber').value = v.tax_number || '';
        document.getElementById('vendorAddress').value = v.address || '';
        document.getElementById('vendorCity').value = v.city || '';
        document.getElementById('vendorCountry').value = v.country || 'United States';
        obModalLoading(modal.firstElementChild || modal, false);
    })
    .catch(function () {
        obModalLoading(modal.firstElementChild || modal, false);
        modal.classList.add('hidden');
        if (typeof obToast === 'function') obToast('Could not load vendor details.', { type: 'error' });
    });
}
</script>
@endsection
