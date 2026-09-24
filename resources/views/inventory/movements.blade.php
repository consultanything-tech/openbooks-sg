@extends('layouts.app')

@section('title', 'Stock Movements -- ' . $item->name)

@section('content')
<div class="space-y-6">
    {{-- Back link --}}
    <div>
        <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-2 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Inventory
        </a>
    </div>

    {{-- Page header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white">Stock Movements -- {{ $item->name }}</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Complete history of stock changes for this item</p>
        </div>
    </div>

    {{-- Item info card --}}
    <div class="glass-card rounded-2xl p-5">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Item Name</p>
                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $item->name }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">SKU</p>
                <p class="text-sm font-mono text-slate-700 dark:text-slate-300">{{ $item->sku ?? '--' }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Current Stock</p>
                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ number_format($item->stock_quantity, 2) }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Reorder Level</p>
                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ number_format($item->reorder_level, 2) }}</p>
            </div>
        </div>
    </div>

    {{-- Movements table --}}
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs responsive-table">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Date</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-center">Type</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Quantity</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-right">Running Balance</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Reference</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">Notes</th>
                        <th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-3 text-left">User</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($movements as $movement)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                        <td data-label="Date" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">{{ $movement->created_at->format('d M Y, H:i') }}</td>
                        <td data-label="Type" class="px-4 py-3 text-xs text-center">
                            @php
                                $badgeClasses = match($movement->type) {
                                    'purchase' => 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/20',
                                    'sale' => 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20',
                                    'adjustment' => 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/20',
                                    'return' => 'bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-500/20',
                                    'transfer' => 'bg-slate-50 dark:bg-slate-500/10 text-slate-700 dark:text-slate-400 border-slate-200 dark:border-slate-500/20',
                                    default => 'bg-slate-50 dark:bg-slate-500/10 text-slate-700 dark:text-slate-400 border-slate-200 dark:border-slate-500/20',
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $badgeClasses }}">{{ ucfirst($movement->type) }}</span>
                        </td>
                        <td data-label="Quantity" class="px-4 py-3 text-xs text-right font-semibold {{ (float)$movement->quantity >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400' }}">
                            {{ (float)$movement->quantity >= 0 ? '+' : '' }}{{ number_format($movement->quantity, 2) }}
                        </td>
                        <td data-label="Balance" class="px-4 py-3 text-xs text-right font-medium text-slate-900 dark:text-white">
                            {{ number_format($balances[$movement->id] ?? 0, 2) }}
                        </td>
                        <td data-label="Reference" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">
                            @if($movement->reference_type && $movement->reference_id)
                                {{ $movement->reference_type }} #{{ $movement->reference_id }}
                            @elseif($movement->reference_type)
                                {{ $movement->reference_type }}
                            @else
                                <span class="text-slate-400 dark:text-slate-500">--</span>
                            @endif
                        </td>
                        <td data-label="Notes" class="px-4 py-3 text-xs text-slate-600 dark:text-slate-400 max-w-[200px] truncate">{{ $movement->notes ?? '--' }}</td>
                        <td data-label="User" class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">{{ $movement->user->name ?? 'System' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <x-empty-state
                                icon="history"
                                title="No stock movements yet"
                                message="Movements will appear here once stock is adjusted, received, or sold" />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-pagination :paginator="$movements" />
    </div>
</div>
@endsection
