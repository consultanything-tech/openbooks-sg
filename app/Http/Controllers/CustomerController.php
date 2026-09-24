<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Traits\HandlesBulkActions;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class CustomerController extends Controller
{
    use HandlesBulkActions;
    use LogsActivity;

    protected function bulkModelClass(): string
    {
        return Customer::class;
    }

    protected function bulkIndexRoute(): string
    {
        return 'customers.index';
    }

    protected function bulkRestoreRouteName(): string
    {
        return 'customers.bulk_restore';
    }

    public function index()
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';
        $customers = Customer::withCount('invoices')->latest()->paginate(15);

        return view('customers.index', compact('customers', 'company', 'currencySymbol'));
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

        $customer = Customer::create($validated);
        $this->logActivity('created', "Created customer {$customer->name}", 'Customer', $customer->id);

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function exportCsv()
    {
        $customers = Customer::latest()->get();

        $headers = ['Name', 'Email', 'Phone', 'Company', 'Tax Number', 'City', 'Country', 'Balance'];
        $rows = [];
        foreach ($customers as $c) {
            $rows[] = [
                $c->name,
                $c->email,
                $c->phone,
                $c->company_name,
                $c->tax_number,
                $c->city,
                $c->country,
                number_format((float) $c->balance, 2),
            ];
        }

        return $this->buildCsvResponse('customers.csv', $headers, $rows);
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
            return redirect()->route('customers.index')->with('error', 'Unable to read the uploaded file.');
        }

        $header = fgetcsv($handle, 0, ',');
        if ($header === false) {
            fclose($handle);

            return redirect()->route('customers.index')->with('error', 'The CSV file is empty or malformed.');
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
                $email = trim($data['email'] ?? '');

                if (empty($name)) {
                    $skipped++;

                    continue;
                }

                $attributes = [
                    'name' => $name,
                    'phone' => trim($data['phone'] ?? ''),
                    'company_name' => trim($data['company'] ?? ''),
                    'tax_number' => trim($data['tax number'] ?? ''),
                    'address' => trim($data['address'] ?? ''),
                    'city' => trim($data['city'] ?? ''),
                    'country' => trim($data['country'] ?? ''),
                ];

                if (! empty($email)) {
                    Customer::updateOrCreate(
                        ['email' => $email],
                        $attributes
                    );
                } else {
                    Customer::create(array_merge($attributes, ['email' => null]));
                }

                $imported++;
            } catch (\Exception $e) {
                $skipped++;
            }
        }

        fclose($handle);

        $this->logActivity('imported', "Imported {$imported} customers from CSV ({$skipped} skipped)", 'Customer');

        return redirect()->route('customers.index')
            ->with('success', "CSV import complete: {$imported} customers imported, {$skipped} rows skipped.");
    }

    public function importTemplate()
    {
        $headers = ['Name', 'Email', 'Phone', 'Company', 'Tax Number', 'Address', 'City', 'Country'];
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers, ',', '"', '\\');
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="customers_import_template.csv"',
        ]);
    }

    public function show($id)
    {
        $customer = $id instanceof Customer ? $id : Customer::with(['invoices.items', 'transactions.bankAccount'])->findOrFail($id);
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);

        return view('customers.show', compact('customer', 'company'));
    }

    public function edit($id)
    {
        $customer = $id instanceof Customer ? $id : Customer::findOrFail($id);

        return response()->json($customer);
    }

    public function update(Request $request, $id)
    {
        $customer = $id instanceof Customer ? $id : Customer::findOrFail($id);
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

        $customer->update($validated);
        $this->logActivity('updated', "Updated customer {$customer->name}", 'Customer', $customer->id);

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy($id)
    {
        $customer = $id instanceof Customer ? $id : Customer::findOrFail($id);

        if ($customer->invoices()->count() > 0) {
            return redirect()->route('customers.index')->with('error', 'Cannot delete customer with existing invoices.');
        }

        $this->logActivity('deleted', "Deleted customer {$customer->name}", 'Customer', $customer->id);
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully.')
            ->with('undo_url', route('customers.restore', $customer->id))
            ->with('undo_label', 'Undo');
    }

    /** Restore a soft-deleted customer (the "Undo" action on the delete toast). */
    public function restore($id)
    {
        $customer = Customer::withTrashed()->findOrFail($id);

        if (! $customer->trashed()) {
            return redirect()->route('customers.index')->with('info', 'That customer is already active.');
        }

        $customer->restore();
        $this->logActivity('restored', "Restored customer {$customer->name}", 'Customer', $customer->id);

        return redirect()->route('customers.index')->with('success', "Customer {$customer->name} restored.");
    }
}
