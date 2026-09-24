<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Vendor;
use App\Traits\HandlesBulkActions;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class VendorController extends Controller
{
    use HandlesBulkActions;
    use LogsActivity;

    protected function bulkModelClass(): string
    {
        return Vendor::class;
    }

    protected function bulkIndexRoute(): string
    {
        return 'vendors.index';
    }

    protected function bulkRestoreRouteName(): string
    {
        return 'vendors.bulk_restore';
    }

    public function index()
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $vendors = Vendor::withCount('bills')->latest()->paginate(15);

        return view('vendors.index', compact('vendors', 'company'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:150',
            'tax_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        $vendor = Vendor::create($validated);
        $this->logActivity('created', "Created vendor {$vendor->name}", 'Vendor', $vendor->id);

        return redirect()->route('vendors.index')->with('success', 'Vendor created successfully.');
    }

    public function exportCsv()
    {
        $vendors = Vendor::latest()->get();

        $headers = ['Name', 'Email', 'Phone', 'Company', 'Tax Number', 'City', 'Country', 'Balance'];
        $rows = [];
        foreach ($vendors as $v) {
            $rows[] = [
                $v->name,
                $v->email,
                $v->phone,
                $v->company_name,
                $v->tax_number,
                $v->city,
                $v->country,
                number_format((float) $v->balance, 2),
            ];
        }

        return $this->buildCsvResponse('vendors.csv', $headers, $rows);
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

    public function show($id)
    {
        $vendor = $id instanceof Vendor ? $id : Vendor::with(['bills.items', 'transactions.bankAccount'])->findOrFail($id);
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);

        return view('vendors.show', compact('vendor', 'company'));
    }

    public function edit($id)
    {
        $vendor = $id instanceof Vendor ? $id : Vendor::findOrFail($id);

        return response()->json($vendor);
    }

    public function update(Request $request, $id)
    {
        $vendor = $id instanceof Vendor ? $id : Vendor::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:150',
            'tax_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        $vendor->update($validated);
        $this->logActivity('updated', "Updated vendor {$vendor->name}", 'Vendor', $vendor->id);

        return redirect()->route('vendors.index')->with('success', 'Vendor updated successfully.');
    }

    public function destroy($id)
    {
        $vendor = $id instanceof Vendor ? $id : Vendor::findOrFail($id);

        if ($vendor->bills()->count() > 0) {
            return redirect()->route('vendors.index')->with('error', 'Cannot delete vendor with existing bills.');
        }

        $this->logActivity('deleted', "Deleted vendor {$vendor->name}", 'Vendor', $vendor->id);
        $vendor->delete();

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor deleted successfully.')
            ->with('undo_url', route('vendors.restore', $vendor->id))
            ->with('undo_label', 'Undo');
    }

    /** Restore a soft-deleted vendor (the "Undo" action on the delete toast). */
    public function restore($id)
    {
        $vendor = Vendor::withTrashed()->findOrFail($id);

        if (! $vendor->trashed()) {
            return redirect()->route('vendors.index')->with('info', 'That vendor is already active.');
        }

        $vendor->restore();
        $this->logActivity('restored', "Restored vendor {$vendor->name}", 'Vendor', $vendor->id);

        return redirect()->route('vendors.index')->with('success', "Vendor {$vendor->name} restored.");
    }
}
