<?php

namespace App\Http\Controllers\Api;

use App\Models\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ApiQuoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $paginator = Quote::with('customer')->latest()->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $quote = Quote::with(['items', 'customer'])->findOrFail($id);

        return response()->json(['data' => $quote]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'quote_date' => 'required|date',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable|exists:items,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
        ]);

        $subtotal = 0;
        $taxTotal = 0;
        foreach ($validated['items'] as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $subtotal += $lineTotal;
            $taxTotal += $lineTotal * (($item['tax_rate'] ?? 0) / 100);
        }
        $total = $subtotal + $taxTotal;

        $quote = Quote::create([
            'customer_id' => $validated['customer_id'],
            'quote_date' => $validated['quote_date'],
            'expiry_date' => $validated['expiry_date'] ?? null,
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'discount_total' => 0,
            'total' => $total,
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
            'terms' => $validated['terms'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $quote->items()->create([
                'item_id' => $item['item_id'] ?? null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'] ?? 0,
                'total' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        return response()->json(['data' => $quote->load('items')], 201);
    }
}
