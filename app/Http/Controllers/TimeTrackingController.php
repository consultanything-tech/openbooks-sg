<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TimeTrackingController extends Controller
{
    use \App\Traits\LogsActivity;

    /**
     * List time entries with filters.
     */
    public function index(Request $request)
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';

        $query = TimeEntry::with('customer')->latest();

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('project')) {
            $query->where('project', 'like', '%' . $request->project . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('entry_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('entry_date', '<=', $request->date_to);
        }

        if ($request->filled('billable')) {
            $query->where('is_billable', $request->billable === '1');
        }

        if ($request->filled('invoiced')) {
            if ($request->invoiced === '1') {
                $query->whereNotNull('invoice_id');
            } else {
                $query->whereNull('invoice_id');
            }
        }

        $entries = $query->paginate(15);
        $customers = Customer::orderBy('name')->get();

        return view('time-tracking.index', compact('entries', 'customers', 'company', 'currencySymbol'));
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('time-tracking.create', compact('customers'));
    }

    /**
     * Validate and create time entry.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'project' => 'required|string|max:255',
            'description' => 'nullable|string',
            'entry_date' => 'required|date',
            'hours' => 'required|numeric|min:0.01|max:24',
            'rate' => 'required|numeric|min:0',
            'is_billable' => 'nullable|boolean',
        ]);

        $validated['amount'] = round((float) $validated['hours'] * (float) $validated['rate'], 2);
        $validated['user_id'] = Auth::id();
        $validated['is_billable'] = $request->boolean('is_billable', true);

        $entry = TimeEntry::create($validated);

        $this->logActivity('created', "Created time entry for project {$entry->project}", 'TimeEntry', $entry->id);

        return redirect()->route('time_tracking.index')->with('success', 'Time entry created successfully.');
    }

    /**
     * Show single entry detail.
     */
    public function show($id)
    {
        $entry = TimeEntry::with('customer')->findOrFail($id);
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';

        return view('time-tracking.show', compact('entry', 'company', 'currencySymbol'));
    }

    /**
     * Edit form.
     */
    public function edit($id)
    {
        $entry = TimeEntry::findOrFail($id);
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('time-tracking.edit', compact('entry', 'customers'));
    }

    /**
     * Update time entry (only if not invoiced).
     */
    public function update(Request $request, $id)
    {
        $entry = TimeEntry::findOrFail($id);

        if ($entry->invoice_id) {
            return redirect()->back()->with('error', 'Cannot update a time entry that has been invoiced.');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'project' => 'required|string|max:255',
            'description' => 'nullable|string',
            'entry_date' => 'required|date',
            'hours' => 'required|numeric|min:0.01|max:24',
            'rate' => 'required|numeric|min:0',
            'is_billable' => 'nullable|boolean',
        ]);

        $validated['amount'] = round((float) $validated['hours'] * (float) $validated['rate'], 2);
        $validated['is_billable'] = $request->boolean('is_billable', true);

        $entry->update($validated);

        $this->logActivity('updated', "Updated time entry for project {$entry->project}", 'TimeEntry', $entry->id);

        return redirect()->route('time_tracking.index')->with('success', 'Time entry updated successfully.');
    }

    /**
     * Delete time entry (only if not invoiced).
     */
    public function destroy($id)
    {
        $entry = TimeEntry::findOrFail($id);

        if ($entry->invoice_id) {
            return redirect()->back()->with('error', 'Cannot delete a time entry that has been invoiced.');
        }

        $entry->delete();

        $this->logActivity('deleted', "Deleted time entry for project {$entry->project}", 'TimeEntry', $entry->id);

        return redirect()->route('time_tracking.index')->with('success', 'Time entry deleted successfully.');
    }

    /**
     * Convert selected time entries to an invoice.
     */
    public function convertToInvoice(Request $request)
    {
        $request->validate([
            'entry_ids' => 'required|array|min:1',
            'entry_ids.*' => 'exists:time_entries,id',
            'customer_id' => 'required|exists:customers,id',
        ]);

        $entries = TimeEntry::whereIn('id', $request->entry_ids)
            ->whereNull('invoice_id')
            ->where('customer_id', $request->customer_id)
            ->get();

        if ($entries->isEmpty()) {
            return redirect()->back()->with('error', 'No eligible time entries found for invoicing.');
        }

        $invoice = DB::transaction(function () use ($entries, $request) {
            $subtotal = 0;
            $itemRows = [];

            foreach ($entries as $entry) {
                $lineTotal = round($entry->hours * $entry->rate, 2);
                $subtotal += $lineTotal;

                $itemRows[] = [
                    'name' => $entry->project,
                    'description' => $entry->description ?? $entry->project,
                    'quantity' => $entry->hours,
                    'price' => $entry->rate,
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'total' => $lineTotal,
                ];
            }

            $lastId = Invoice::withTrashed()->max('id') ?? 0;
            $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $request->customer_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'subtotal' => $subtotal,
                'tax_total' => 0,
                'discount_total' => 0,
                'total' => $subtotal,
                'paid_amount' => 0,
                'due_amount' => $subtotal,
                'status' => 'draft',
                'notes' => 'Generated from time entries.',
                'terms' => 'Payment due within 30 days.',
                'public_token' => Str::random(40),
                'currency_code' => 'SGD',
                'exchange_rate' => 1.000000,
            ]);

            foreach ($itemRows as $itemData) {
                $invoice->items()->create($itemData);
            }

            // Mark time entries as invoiced
            TimeEntry::whereIn('id', $entries->pluck('id'))->update(['invoice_id' => $invoice->id]);

            return $invoice;
        });

        $this->logActivity('created', "Created invoice {$invoice->invoice_number} from time entries", 'Invoice', $invoice->id);

        return redirect()->route('time_tracking.index')->with('success', "Invoice {$invoice->invoice_number} created from " . $entries->count() . " time entries.");
    }

    /**
     * Export time entries to CSV.
     */
    public function exportCsv()
    {
        $entries = TimeEntry::with('customer')->latest()->get();

        $filename = 'time-entries-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($entries) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'Date', 'Customer', 'Project', 'Description', 'Hours', 'Rate', 'Amount', 'Billable', 'Invoiced',
            ], ',', '"', '\\');

            foreach ($entries as $entry) {
                fputcsv($output, [
                    $entry->entry_date,
                    $entry->customer->name ?? '',
                    $entry->project,
                    $entry->description ?? '',
                    $entry->hours,
                    $entry->rate,
                    $entry->amount,
                    $entry->is_billable ? 'Yes' : 'No',
                    $entry->invoice_id ? 'Yes' : 'No',
                ], ',', '"', '\\');
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }
}
