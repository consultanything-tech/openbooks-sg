<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\Item;
use App\Models\Tax;
use App\Traits\HandlesBulkActions;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ItemController extends Controller
{
    use HandlesBulkActions;
    use LogsActivity;

    protected function bulkModelClass(): string
    {
        return Item::class;
    }

    protected function bulkIndexRoute(): string
    {
        return 'items.index';
    }

    protected function bulkRestoreRouteName(): string
    {
        return 'items.bulk_restore';
    }

    public function index()
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $items = Item::with(['category', 'tax'])->latest()->paginate(15);
        $categories = Category::all();
        $taxes = Tax::all();

        return view('items.index', compact('items', 'company', 'categories', 'taxes'));
    }

    public function exportCsv()
    {
        $items = Item::with(['category', 'tax'])->latest()->get();

        $headers = ['Name', 'SKU', 'Category', 'Sale Price', 'Purchase Price', 'Tax', 'Unit'];
        $rows = [];
        foreach ($items as $it) {
            $rows[] = [
                $it->name,
                $it->sku,
                $it->category->name ?? 'General',
                number_format((float) $it->sale_price, 2),
                number_format((float) $it->purchase_price, 2),
                $it->tax ? $it->tax->name.' ('.$it->tax->rate.'%)' : 'None',
                $it->unit,
            ];
        }

        return $this->buildCsvResponse('items.csv', $headers, $rows);
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

    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return redirect()->route('items.index')->with('error', 'Unable to read the uploaded file.');
        }

        $header = fgetcsv($handle, 0, ',');
        if ($header === false) {
            fclose($handle);

            return redirect()->route('items.index')->with('error', 'The CSV file is empty or malformed.');
        }

        // Normalize header names
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $imported = 0;
        $skipped = 0;
        $rowNum = 1;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rowNum++;

            // Skip empty rows
            if (count($row) === 1 && trim($row[0]) === '') {
                continue;
            }

            try {
                $row = array_pad($row, count($header), '');
                if (count($row) !== count($header)) {
                    $skipped++;

                    continue;
                }

                $data = array_combine($header, $row);

                $name = trim($data['name'] ?? '');
                $sku = trim($data['sku'] ?? '');

                if (empty($name)) {
                    $skipped++;

                    continue;
                }

                $attributes = [
                    'name' => $name,
                    'description' => trim($data['description'] ?? ''),
                    'sale_price' => floatval(str_replace(',', '', $data['sale price'] ?? '0')),
                    'purchase_price' => floatval(str_replace(',', '', $data['purchase price'] ?? '0')),
                    'unit' => trim($data['unit'] ?? '') ?: 'pcs',
                ];

                if (! empty($sku)) {
                    Item::updateOrCreate(
                        ['sku' => $sku],
                        $attributes
                    );
                } else {
                    Item::create(array_merge($attributes, ['sku' => null]));
                }

                $imported++;
            } catch (\Exception $e) {
                $skipped++;
            }
        }

        fclose($handle);

        $this->logActivity('imported', "Imported {$imported} items from CSV ({$skipped} skipped)", 'Item');

        return redirect()->route('items.index')
            ->with('success', "CSV import complete: {$imported} items imported, {$skipped} rows skipped.");
    }

    public function importTemplate()
    {
        $headers = ['Name', 'SKU', 'Description', 'Sale Price', 'Purchase Price', 'Unit'];
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers, ',', '"', '\\');
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="items_import_template.csv"',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'sku' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'sale_price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'unit' => 'nullable|string|max:20',
        ]);

        $validated['unit'] = $request->unit ?: 'pcs';
        $validated['purchase_price'] = $request->purchase_price ?: 0.00;

        $item = Item::create($validated);
        $this->logActivity('created', "Created item {$item->name}", 'Item', $item->id);

        return redirect()->route('items.index')->with('success', 'Product / Service created successfully.');
    }

    public function update(Request $request, $id)
    {
        // Route parameter is {id}, so bind explicitly — an `Item $item` hint here
        // would silently resolve to an empty model instead of the record.
        $item = Item::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'sku' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'sale_price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'unit' => 'nullable|string|max:20',
        ]);

        $validated['unit'] = $request->unit ?: ($item->unit ?: 'pcs');
        $validated['purchase_price'] = $request->purchase_price ?: 0.00;

        $item->update($validated);
        $this->logActivity('updated', "Updated item {$item->name}", 'Item', $item->id);

        return redirect()->route('items.index')->with('success', 'Product / Service updated successfully.');
    }

    public function destroy($id)
    {
        $item = Item::findOrFail($id);

        if ($item->invoiceItems()->count() > 0 || $item->billItems()->count() > 0) {
            return redirect()->route('items.index')->with('error', 'Cannot delete item used in existing invoices or bills.');
        }

        $this->logActivity('deleted', "Deleted item {$item->name}", 'Item', $item->id);
        $item->delete();

        return redirect()->route('items.index')
            ->with('success', 'Product / Service deleted successfully.')
            ->with('undo_url', route('items.restore', $item->id))
            ->with('undo_label', 'Undo');
    }

    /** Restore a soft-deleted item (the "Undo" action on the delete toast). */
    public function restore($id)
    {
        $item = Item::withTrashed()->findOrFail($id);

        if (! $item->trashed()) {
            return redirect()->route('items.index')->with('info', 'That item is already active.');
        }

        $item->restore();
        $this->logActivity('restored', "Restored item {$item->name}", 'Item', $item->id);

        return redirect()->route('items.index')->with('success', "Item {$item->name} restored.");
    }
}
