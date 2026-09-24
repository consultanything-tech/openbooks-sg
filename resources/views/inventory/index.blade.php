@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
<div class="space-y-6">
    {{-- Flash errors --}}
    @if($errors->any())
    <div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-700 dark:text-red-400 text-xs">
        <ul class="list-disc pl-4 space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Inventory Management</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Track stock levels, manage reorder points, and monitor inventory value</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('inventory.export_csv') }}" class="btn btn-secondary">
                <i data-lucide="file-spreadsheet" aria-hidden="true"></i> Export CSV
            </a>
            @canEdit
            <button onclick="openReceiveModal()" class="btn btn-primary">
                <i data-lucide="truck" aria-hidden="true"></i> Receive Stock
            </button>
            @endcanEdit
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="glass-card rounded-2xl p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-500/15 flex items-center justify-center">
                    <i data-lucide="package" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Items Tracked</p>
                    <p class="text-xl font-bold text-slate-900 dark:text-white">{{ number_format($totalTracked) }}</p>
                </div>
            </div>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl {{ $lowStockCount > 0 ? 'bg-red-100 dark:bg-red-500/15' : 'bg-emerald-100 dark:bg-emerald-500/15' }} flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-4 h-4 {{ $lowStockCount > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}"></i>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Low Stock Items</p>
                    <p class="text-xl font-bold {{ $lowStockCount > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">{{ number_format($lowStockCount) }}</p>
                </div>
            </div>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-500/15 flex items-center justify-center">
                    <i data-lucide="coins" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Stock Value</p>
                    <p class="text-xl font-bold text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($totalStockValue, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Search --}}
    <div>
        <input type="text" id="searchInput" placeholder="Search by name, SKU, or status..." aria-label="Search inventory" oninput="filterTable()" class="w-full sm:w-80 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 placeholder-slate-400 dark:placeholder-slate-500">
    </div>

    {{-- Inventory table --}}
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="dataTable" class="w-full text-left text-xs responsive-table">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">SKU</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Item Name</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Stock Qty</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Reorder Level</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Cost Price</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Stock Value</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-center">Status</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($items as $item)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                        <td data-label="SKU" class="px-4 py-3 text-xs font-mono text-slate-700 dark:text-slate-300">{{ $item->sku ?? '--' }}</td>
                        <td data-label="Item" class="px-4 py-3 text-xs font-bold text-slate-900 dark:text-white">{{ $item->name }}</td>
                        <td data-label="Stock Qty" class="px-4 py-3 text-xs text-right font-semibold text-slate-900 dark:text-white">{{ number_format($item->stock_quantity, 2) }}</td>
                        <td data-label="Reorder" class="px-4 py-3 text-xs text-right text-slate-700 dark:text-slate-300">{{ number_format($item->reorder_level, 2) }}</td>
                        <td data-label="Cost" class="px-4 py-3 text-xs text-right text-slate-700 dark:text-slate-300">{{ $currencySymbol }}{{ number_format($item->cost_price, 2) }}</td>
                        <td data-label="Value" class="px-4 py-3 text-xs text-right font-medium text-slate-900 dark:text-white">{{ $currencySymbol }}{{ number_format($item->stock_quantity * $item->cost_price, 2) }}</td>
                        <td data-label="Status" class="px-4 py-3 text-xs text-center">
                            @if($item->stock_quantity <= 0)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-500/20">Out of Stock</span>
                            @elseif($item->stock_quantity <= $item->reorder_level)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20">Low Stock</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">In Stock</span>
                            @endif
                        </td>
                        <td data-label="Actions" class="px-4 py-3 text-xs text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('inventory.movements', $item->id) }}" class="btn-icon" title="View Movements" aria-label="View movements for {{ $item->name }}">
                                    <i data-lucide="history" aria-hidden="true"></i>
                                </a>
                                @canEdit
                                <button onclick="openAdjustModal({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->stock_quantity }})" class="btn-icon" title="Adjust Stock" aria-label="Adjust stock for {{ $item->name }}">
                                    <i data-lucide="sliders-horizontal" aria-hidden="true"></i>
                                </button>
                                @endcanEdit
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <x-empty-state
                                icon="warehouse"
                                title="No tracked items yet"
                                message="Enable inventory tracking on items to see them here" />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-pagination :paginator="$items" />
    </div>

    {{-- Adjust Stock Modal --}}
    <div id="adjustStockModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 max-w-lg w-full shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Adjust Stock</h3>
                <button onclick="closeAdjustModal()" class="btn-icon" aria-label="Close">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </div>
            <form id="adjustForm" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Item</label>
                    <input type="text" id="adjustItemName" readonly class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Current Stock</label>
                    <input type="text" id="adjustCurrentStock" readonly class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Adjustment Type</label>
                    <select name="adjustment_type" id="adjustType" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        <option value="increase">Increase</option>
                        <option value="decrease">Decrease</option>
                        <option value="set">Set To</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Quantity</label>
                    <input type="number" name="quantity" id="adjustQuantity" step="0.01" min="0" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Notes</label>
                    <textarea name="notes" id="adjustNotes" rows="2" placeholder="Reason for adjustment..." class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"></textarea>
                </div>
                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" onclick="closeAdjustModal()" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="save" aria-hidden="true"></i> Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Receive Stock Modal --}}
    <div id="receiveStockModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 max-w-lg w-full shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Receive Stock</h3>
                <button onclick="closeReceiveModal()" class="btn-icon" aria-label="Close">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </div>
            <form action="{{ route('inventory.receive') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Item *</label>
                    <select data-combobox name="item_id" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        <option value="">Select an item...</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}{{ $item->sku ? ' (' . $item->sku . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Quantity *</label>
                    <input type="number" name="quantity" step="0.01" min="0.01" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Bill Reference</label>
                    <input type="text" name="bill_reference" placeholder="e.g. BILL-2026-0042" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Notes</label>
                    <textarea name="notes" rows="2" placeholder="Optional notes..." class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"></textarea>
                </div>
                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" onclick="closeReceiveModal()" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="save" aria-hidden="true"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAdjustModal(itemId, itemName, currentStock) {
    document.getElementById('adjustForm').action = '/inventory/' + itemId + '/adjust';
    document.getElementById('adjustItemName').value = itemName;
    document.getElementById('adjustCurrentStock').value = parseFloat(currentStock).toFixed(2);
    document.getElementById('adjustQuantity').value = '';
    document.getElementById('adjustNotes').value = '';
    document.getElementById('adjustType').value = 'increase';
    document.getElementById('adjustStockModal').classList.remove('hidden');
}

function closeAdjustModal() {
    document.getElementById('adjustStockModal').classList.add('hidden');
}

function openReceiveModal() {
    document.getElementById('receiveStockModal').classList.remove('hidden');
}

function closeReceiveModal() {
    document.getElementById('receiveStockModal').classList.add('hidden');
}
</script>
@endsection
