@extends('layouts.app')

@section('title', 'Products & Services')

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
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Products & Services Catalog</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage item pricing, sales rates, purchase costs, and tax settings</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <button onclick="document.getElementById('importModal').showModal()" class="btn btn-secondary">
                <i data-lucide="file-input" aria-hidden="true"></i> Import CSV
            </button>
            <a href="{{ route('items.export_csv') }}" class="btn btn-secondary">
                <i data-lucide="file-spreadsheet" aria-hidden="true"></i> Export CSV
            </a>
            <button onclick="openItemModal()" class="btn btn-primary">
                <i data-lucide="plus" aria-hidden="true"></i> New Item
            </button>
        </div>
    </div>

    <!-- Search -->
    <div>
        <input type="text" id="searchInput" placeholder="Search by name, SKU, or category..." aria-label="Search items" oninput="filterTable()" class="w-full sm:w-80 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 placeholder-slate-400 dark:placeholder-slate-500">
    </div>

    <!-- Items Table -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="ob-bulk-bar" data-bulk-delete="{{ route('items.bulk_delete') }}">
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
                            <input type="checkbox" id="selectAll" aria-label="Select all items"
                                class="w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-0 focus:ring-offset-0 cursor-pointer">
                        </th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Item Name</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">SKU</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Category</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Sale Price</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Purchase Price</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-center">Tax</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-center">Status</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($items as $it)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="ob-check-col px-4 py-3" data-label="">
                                <input type="checkbox" class="ob-row-check w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-0 focus:ring-offset-0 cursor-pointer"
                                    value="{{ $it->id }}" aria-label="Select item {{ $it->name }}">
                            </td>
                            <td data-label="Item" class="px-4 py-3 text-xs font-bold text-slate-900 dark:text-white">
                                <p>{{ $it->name }}</p>
                                @if($it->description)
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 font-normal">{{ $it->description }}</p>
                                @endif
                            </td>
                            <td data-label="SKU" class="px-4 py-3 text-xs font-mono text-slate-700 dark:text-slate-300">{{ $it->sku ?? '—' }}</td>
                            <td data-label="Category" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">{{ $it->category->name ?? 'General' }}</td>
                            <td data-label="Sale Price" class="px-4 py-3 text-xs text-right font-bold text-emerald-700 dark:text-emerald-400">{{ $currencySymbol }}{{ number_format($it->sale_price, 2) }}</td>
                            <td data-label="Cost" class="px-4 py-3 text-xs text-right font-medium text-slate-700 dark:text-slate-300">{{ $currencySymbol }}{{ number_format($it->purchase_price, 2) }}</td>
                            <td data-label="Tax" class="px-4 py-3 text-xs text-center">
                                @if($it->tax)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">{{ $it->tax->name }} ({{ $it->tax->rate }}%)</span>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500">—</span>
                                @endif
                            </td>
                            <td data-label="Status" class="px-4 py-3 text-xs text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                                    {{ $it->status }}
                                </span>
                            </td>
                            <td data-label="Actions" class="px-4 py-3 text-xs text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button onclick="editItem({{ $it->id }})" class="btn-icon" title="Edit" aria-label="Edit item {{ $it->name }}">
                                        <i data-lucide="pencil" aria-hidden="true"></i>
                                    </button>
                                    <form action="{{ route('items.destroy', $it->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this item? This cannot be undone.')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete" aria-label="Delete item {{ $it->name }}">
                                            <i data-lucide="trash-2" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <x-empty-state
                                    icon="package"
                                    title="No items yet"
                                    message="Add your first product or service to include on invoices and bills" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-pagination :paginator="$items" />
    </div>

    <!-- Import CSV Modal -->
    <dialog id="importModal" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 max-w-lg w-full shadow-xl backdrop:bg-black/50 backdrop:backdrop-blur-sm">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Import Items from CSV</h3>
                <button onclick="document.getElementById('importModal').close()" class="btn-icon" aria-label="Close">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </div>

            <form action="{{ route('items.import_csv') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">CSV File</label>
                    <input type="file" name="csv_file" accept=".csv,.txt" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-500/10 dark:file:text-indigo-400">
                </div>

                <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Expected CSV Columns:</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">Name, SKU, Description, Sale Price, Purchase Price, Unit</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2">Duplicate SKUs will update the existing item record. Rows without a name will be skipped.</p>
                </div>

                <div class="pt-2 flex items-center justify-between">
                    <a href="{{ route('items.import_template') }}" class="btn btn-secondary">
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

    <!-- Item Modal (Create / Edit) -->
    <div id="addItemModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 max-w-lg w-full shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 id="itemModalTitle" class="text-base font-bold text-slate-900 dark:text-white">New Product / Service</h3>
                <button onclick="closeItemModal()" class="btn-icon" aria-label="Close">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </div>

            <form id="itemForm" action="{{ route('items.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="_method" id="itemMethod" value="POST">
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Item Name *</label>
                    <input type="text" name="name" id="itemName" value="{{ old('name') }}" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                    @error('name')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">SKU / Code</label>
                        <input type="text" name="sku" id="itemSku" placeholder="e.g. PRD-001" value="{{ old('sku') }}" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        @error('sku')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Category</label>
                        <select data-combobox name="category_id" id="itemCategoryId" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                            <option value="">None</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Sales Price ({{ $currencySymbol }}) *</label>
                        <input type="number" name="sale_price" id="itemSalePrice" value="{{ old('sale_price', '0.00') }}" step="0.01" min="0" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        @error('sale_price')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Purchase Cost ({{ $currencySymbol }})</label>
                        <input type="number" name="purchase_price" id="itemPurchasePrice" value="{{ old('purchase_price', '0.00') }}" step="0.01" min="0" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        @error('purchase_price')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Tax Rate</label>
                        <select name="tax_id" id="itemTaxId" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                            <option value="">None (0%)</option>
                            @foreach($taxes as $tx)
                                <option value="{{ $tx->id }}">{{ $tx->name }} ({{ $tx->rate }}%)</option>
                            @endforeach
                        </select>
                        @error('tax_id')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Unit</label>
                        <input type="text" name="unit" id="itemUnit" placeholder="e.g. hrs, pcs, licenses" value="{{ old('unit') }}" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        @error('unit')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Description</label>
                    <textarea name="description" id="itemDescription" rows="2" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"></textarea>
                    @error('description')<p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" onclick="closeItemModal()" class="btn btn-ghost">Cancel</button>
                    <button type="submit" id="itemSubmitBtn" class="btn btn-primary"><i data-lucide="save" aria-hidden="true"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openItemModal() {
    document.getElementById('itemModalTitle').textContent = 'New Product / Service';
    document.getElementById('itemSubmitBtn').textContent = 'Save Item';
    document.getElementById('itemForm').action = "{{ route('items.store') }}";
    document.getElementById('itemMethod').value = 'POST';
    document.getElementById('itemName').value = '';
    document.getElementById('itemSku').value = '';
    document.getElementById('itemCategoryId').value = '';
    document.getElementById('itemSalePrice').value = '0.00';
    document.getElementById('itemPurchasePrice').value = '0.00';
    document.getElementById('itemTaxId').value = '';
    document.getElementById('itemUnit').value = '';
    document.getElementById('itemDescription').value = '';
    document.getElementById('addItemModal').classList.remove('hidden');
}

function closeItemModal() {
    document.getElementById('addItemModal').classList.add('hidden');
}

function editItem(id) {
    var modal = document.getElementById('addItemModal');
    modal.classList.remove('hidden');
    obModalLoading(modal.firstElementChild || modal, true);
    fetch('/items/' + id + '/edit', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        const it = data.item || data;
        document.getElementById('itemModalTitle').textContent = 'Edit Product / Service';
        document.getElementById('itemSubmitBtn').textContent = 'Update Item';
        document.getElementById('itemForm').action = '/items/' + id;
        document.getElementById('itemMethod').value = 'PUT';
        document.getElementById('itemName').value = it.name || '';
        document.getElementById('itemSku').value = it.sku || '';
        document.getElementById('itemCategoryId').value = it.category_id || '';
        document.getElementById('itemSalePrice').value = it.sale_price || '0.00';
        document.getElementById('itemPurchasePrice').value = it.purchase_price || '0.00';
        document.getElementById('itemTaxId').value = it.tax_id || '';
        document.getElementById('itemUnit').value = it.unit || '';
        document.getElementById('itemDescription').value = it.description || '';
        obModalLoading(modal.firstElementChild || modal, false);
    })
    .catch(function () {
        obModalLoading(modal.firstElementChild || modal, false);
        modal.classList.add('hidden');
        if (typeof obToast === 'function') obToast('Could not load item details.', { type: 'error' });
    });
}
</script>
@endsection
