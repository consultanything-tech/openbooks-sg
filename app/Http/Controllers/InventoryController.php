<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Item;
use App\Models\StockMovement;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    use LogsActivity;

    public function index()
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';

        $items = Item::where('track_inventory', true)
            ->with('category')
            ->orderBy('name')
            ->paginate(20);

        $totalTracked = Item::where('track_inventory', true)->count();
        $lowStockCount = Item::where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'reorder_level')
            ->count();
        $totalStockValue = Item::where('track_inventory', true)
            ->selectRaw('SUM(stock_quantity * cost_price) as total')
            ->value('total') ?? 0;

        return view('inventory.index', compact(
            'items', 'company', 'currencySymbol', 'totalTracked', 'lowStockCount', 'totalStockValue'
        ));
    }

    public function movements($itemId)
    {
        $item = Item::findOrFail($itemId);
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';

        $movements = StockMovement::where('item_id', $itemId)
            ->with('user')
            ->latest()
            ->paginate(25);

        // Calculate running balance (movements are ordered latest first, reverse for balance calc)
        $allMovements = StockMovement::where('item_id', $itemId)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningBalance = 0;
        $balances = [];
        foreach ($allMovements as $movement) {
            $runningBalance += (float) $movement->quantity;
            $balances[$movement->id] = $runningBalance;
        }

        return view('inventory.movements', compact(
            'item', 'movements', 'company', 'currencySymbol', 'balances'
        ));
    }

    public function adjust(Request $request, $itemId)
    {
        $item = Item::findOrFail($itemId);

        $validated = $request->validate([
            'adjustment_type' => 'required|in:increase,decrease,set',
            'quantity' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($item, $validated) {
            $qty = (float) $validated['quantity'];
            $currentQty = (float) $item->stock_quantity;

            $movementQty = match ($validated['adjustment_type']) {
                'increase' => $qty,
                'decrease' => -$qty,
                'set' => $qty - $currentQty,
            };

            $newQty = match ($validated['adjustment_type']) {
                'increase' => $currentQty + $qty,
                'decrease' => $currentQty - $qty,
                'set' => $qty,
            };

            $item->update(['stock_quantity' => $newQty]);

            StockMovement::create([
                'item_id' => $item->id,
                'type' => 'adjustment',
                'quantity' => $movementQty,
                'reference_type' => 'Manual',
                'reference_id' => null,
                'notes' => $validated['notes'] ?? 'Manual stock adjustment',
                'user_id' => auth()->id(),
            ]);
        });

        $this->logActivity('adjusted', "Adjusted stock for {$item->name}: {$validated['adjustment_type']} by {$validated['quantity']}", 'Item', $item->id);

        return redirect()->back()->with('success', "Stock adjusted for {$item->name}. New quantity: {$item->fresh()->stock_quantity}");
    }

    public function receive(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|numeric|min:0.01',
            'bill_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($validated) {
            $item = Item::findOrFail($validated['item_id']);
            $qty = (float) $validated['quantity'];

            $item->increment('stock_quantity', $qty);

            StockMovement::create([
                'item_id' => $item->id,
                'type' => 'purchase',
                'quantity' => $qty,
                'reference_type' => $validated['bill_reference'] ? 'Bill' : 'Manual',
                'reference_id' => null,
                'notes' => $validated['notes'] ?? 'Stock received'.($validated['bill_reference'] ? ' (Ref: '.$validated['bill_reference'].')' : ''),
                'user_id' => auth()->id(),
            ]);
        });

        $this->logActivity('received', "Received stock for item #{$validated['item_id']}: +{$validated['quantity']}", 'Item', $validated['item_id']);

        return redirect()->back()->with('success', 'Stock received successfully.');
    }

    public function lowStock()
    {
        $items = Item::where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'reorder_level')
            ->select('id', 'name', 'sku', 'stock_quantity', 'reorder_level', 'unit')
            ->orderBy('stock_quantity')
            ->get();

        return response()->json([
            'count' => $items->count(),
            'items' => $items,
        ]);
    }

    public function exportCsv()
    {
        $items = Item::where('track_inventory', true)->with('category')->orderBy('name')->get();

        $headers = ['SKU', 'Item Name', 'Category', 'Stock Quantity', 'Reorder Level', 'Cost Price', 'Stock Value', 'Status'];
        $rows = [];
        foreach ($items as $item) {
            $stockValue = (float) $item->stock_quantity * (float) $item->cost_price;

            if ((float) $item->stock_quantity <= 0) {
                $status = 'Out of Stock';
            } elseif ((float) $item->stock_quantity <= (float) $item->reorder_level) {
                $status = 'Low Stock';
            } else {
                $status = 'In Stock';
            }

            $rows[] = [
                $item->sku ?? '',
                $item->name,
                $item->category->name ?? 'General',
                number_format($item->stock_quantity, 2),
                number_format($item->reorder_level, 2),
                number_format($item->cost_price, 2),
                number_format($stockValue, 2),
                $status,
            ];
        }

        return $this->buildCsvResponse('inventory.csv', $headers, $rows);
    }

    private function buildCsvResponse(string $filename, array $headers, array $rows): Response
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers, ',', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($output, $row, ',', '"', '\\');
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
