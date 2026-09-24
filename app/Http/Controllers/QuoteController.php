<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CurrencyRate;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Quote;
use App\Models\Tax;
use App\Traits\HandlesBulkActions;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuoteController extends Controller
{
    use HandlesBulkActions;
    use LogsActivity;

    protected function bulkModelClass(): string
    {
        return Quote::class;
    }

    protected function bulkIndexRoute(): string
    {
        return 'quotes.index';
    }

    protected function bulkRestoreRouteName(): string
    {
        return 'quotes.bulk_restore';
    }

    public function index(Request $request)
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';
        $query = Quote::with('customer')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $quotes = $query->paginate(15);
        $customers = Customer::all();

        return view('quotes.index', compact('quotes', 'customers', 'company', 'currencySymbol'));
    }

    public function create()
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $items = Item::with('tax')->where('is_active', true)->orderBy('name')->get();
        $taxes = Tax::where('is_active', true)->orderBy('name')->get();
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencies = CurrencyRate::where('is_active', true)->orderBy('currency_code')->get();

        $lastId = Quote::withTrashed()->max('id') ?? 0;
        $nextNumber = 'QUO-'.date('Y').'-'.str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);

        return view('quotes.create', compact('customers', 'items', 'taxes', 'company', 'nextNumber', 'currencies'));
    }

    public function store(Request $request)
    {
        $items = $request->input('items', []);
        if (is_array($items)) {
            foreach ($items as $idx => $item) {
                if (! isset($item['name']) && isset($item['item_name'])) {
                    $items[$idx]['name'] = $item['item_name'];
                }
            }
            $request->merge(['items' => $items]);
        }

        $validated = $request->validate([
            'quote_number' => 'required|string|unique:quotes,quote_number',
            'customer_id' => 'required|exists:customers,id',
            'quote_date' => 'required|date',
            'expiry_date' => 'required|date|after_or_equal:quote_date',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
            'discount_total' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $subtotal = 0;
            $taxTotal = 0;
            $itemRows = [];

            foreach ($validated['items'] as $row) {
                $qty = (float) $row['quantity'];
                $price = (float) $row['price'];
                $taxRate = (float) ($row['tax_rate'] ?? 0);
                $lineSubtotal = $qty * $price;
                $lineTax = $lineSubtotal * ($taxRate / 100);
                $lineTotal = $lineSubtotal + $lineTax;
                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;

                $itemRows[] = [
                    'item_id' => $row['item_id'] ?? null,
                    'name' => $row['name'],
                    'description' => $row['description'] ?? null,
                    'quantity' => $qty,
                    'price' => $price,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                    'total' => $lineTotal,
                ];
            }

            $discount = (float) ($validated['discount_total'] ?? 0);
            $grandTotal = max(0, ($subtotal + $taxTotal) - $discount);

            $quote = Quote::create([
                'quote_number' => $validated['quote_number'],
                'customer_id' => $validated['customer_id'],
                'quote_date' => $validated['quote_date'],
                'expiry_date' => $validated['expiry_date'],
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discount,
                'total' => $grandTotal,
                'status' => $request->input('status', 'draft'),
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'public_token' => Str::random(40),
                'currency_code' => $request->input('currency_code', 'SGD'),
                'exchange_rate' => $request->input('exchange_rate', 1.000000),
            ]);

            foreach ($itemRows as $itemData) {
                $quote->items()->create($itemData);
            }
        });

        $this->logActivity('created', "Created quote {$validated['quote_number']}", 'Quote', null);

        return redirect()->route('quotes.index')->with('success', 'Quotation created successfully.');
    }

    public function show($id)
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';
        $quote = Quote::with(['customer', 'items', 'convertedInvoice'])->findOrFail($id);

        return view('quotes.show', compact('quote', 'company', 'currencySymbol'));
    }

    public function edit($id)
    {
        $quote = Quote::with('items')->findOrFail($id);
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $items = Item::with('tax')->where('is_active', true)->orderBy('name')->get();
        $taxes = Tax::where('is_active', true)->orderBy('name')->get();
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencies = CurrencyRate::where('is_active', true)->orderBy('currency_code')->get();

        return view('quotes.edit', compact('quote', 'customers', 'items', 'taxes', 'company', 'currencies'));
    }

    public function update(Request $request, $id)
    {
        $quote = Quote::findOrFail($id);

        $items = $request->input('items', []);
        if (is_array($items)) {
            foreach ($items as $idx => $item) {
                if (! isset($item['name']) && isset($item['item_name'])) {
                    $items[$idx]['name'] = $item['item_name'];
                }
            }
            $request->merge(['items' => $items]);
        }

        $validated = $request->validate([
            'quote_number' => 'required|string|unique:quotes,quote_number,'.$quote->id,
            'customer_id' => 'required|exists:customers,id',
            'quote_date' => 'required|date',
            'expiry_date' => 'required|date|after_or_equal:quote_date',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
            'discount_total' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($quote, $validated, $request) {
            $subtotal = 0;
            $taxTotal = 0;
            $itemRows = [];

            foreach ($validated['items'] as $row) {
                $qty = (float) $row['quantity'];
                $price = (float) $row['price'];
                $taxRate = (float) ($row['tax_rate'] ?? 0);
                $lineSubtotal = $qty * $price;
                $lineTax = $lineSubtotal * ($taxRate / 100);
                $lineTotal = $lineSubtotal + $lineTax;
                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;

                $itemRows[] = [
                    'item_id' => $row['item_id'] ?? null,
                    'name' => $row['name'],
                    'description' => $row['description'] ?? null,
                    'quantity' => $qty,
                    'price' => $price,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                    'total' => $lineTotal,
                ];
            }

            $discount = (float) ($validated['discount_total'] ?? 0);
            $grandTotal = max(0, ($subtotal + $taxTotal) - $discount);

            $quote->update([
                'quote_number' => $validated['quote_number'],
                'customer_id' => $validated['customer_id'],
                'quote_date' => $validated['quote_date'],
                'expiry_date' => $validated['expiry_date'],
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discount,
                'total' => $grandTotal,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'currency_code' => $request->input('currency_code', $quote->currency_code ?? 'SGD'),
                'exchange_rate' => $request->input('exchange_rate', $quote->exchange_rate ?? 1.000000),
            ]);

            $quote->items()->delete();
            foreach ($itemRows as $itemData) {
                $quote->items()->create($itemData);
            }
        });

        $this->logActivity('updated', "Updated quote {$quote->quote_number}", 'Quote', $quote->id);

        return redirect()->route('quotes.show', $quote->id)->with('success', 'Quotation updated successfully.');
    }

    public function destroy($id)
    {
        $quote = Quote::findOrFail($id);

        if ($quote->status === 'converted') {
            return redirect()->back()->with('error', 'Cannot delete a converted quotation.');
        }

        // Line items are kept so "Undo" can put the quotation back intact.
        $quote->delete();

        $this->logActivity('deleted', "Deleted quote {$quote->quote_number}", 'Quote', $quote->id);

        return redirect()->route('quotes.index')
            ->with('success', 'Quotation deleted.')
            ->with('undo_url', route('quotes.restore', $quote->id))
            ->with('undo_label', 'Undo');
    }

    /** Restore a soft-deleted quotation (the "Undo" action on the delete toast). */
    public function restore($id)
    {
        $quote = Quote::withTrashed()->findOrFail($id);

        if (! $quote->trashed()) {
            return redirect()->route('quotes.index')->with('info', 'That quotation is already active.');
        }

        $quote->restore();
        $this->logActivity('restored', "Restored quote {$quote->quote_number}", 'Quote', $quote->id);

        return redirect()->route('quotes.index')->with('success', "Quotation {$quote->quote_number} restored.");
    }

    public function markSent($id)
    {
        $quote = Quote::findOrFail($id);
        $quote->update(['status' => 'sent']);
        $this->logActivity('marked_sent', "Marked quote {$quote->quote_number} as sent", 'Quote', $quote->id);

        return back()->with('success', 'Quotation marked as sent.');
    }

    public function markAccepted($id)
    {
        $quote = Quote::findOrFail($id);
        $quote->update(['status' => 'accepted']);
        $this->logActivity('accepted', "Quote {$quote->quote_number} accepted by customer", 'Quote', $quote->id);

        return back()->with('success', 'Quotation marked as accepted.');
    }

    public function markDeclined($id)
    {
        $quote = Quote::findOrFail($id);
        $quote->update(['status' => 'declined']);
        $this->logActivity('declined', "Quote {$quote->quote_number} declined", 'Quote', $quote->id);

        return back()->with('success', 'Quotation marked as declined.');
    }

    public function convertToInvoice($id)
    {
        $quote = Quote::with('items')->findOrFail($id);

        if ($quote->status === 'converted') {
            return back()->with('error', 'This quotation has already been converted.');
        }

        $invoice = DB::transaction(function () use ($quote) {
            $lastId = Invoice::withTrashed()->max('id') ?? 0;
            $nextNumber = 'INV-'.date('Y').'-'.str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);

            $invoice = Invoice::create([
                'invoice_number' => $nextNumber,
                'customer_id' => $quote->customer_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'subtotal' => $quote->subtotal,
                'tax_total' => $quote->tax_total,
                'discount_total' => $quote->discount_total,
                'total' => $quote->total,
                'paid_amount' => 0,
                'due_amount' => $quote->total,
                'status' => 'draft',
                'notes' => $quote->notes,
                'terms' => $quote->terms,
                'public_token' => Str::random(40),
                'currency_code' => $quote->currency_code ?? 'SGD',
                'exchange_rate' => $quote->exchange_rate ?? 1.000000,
            ]);

            foreach ($quote->items as $item) {
                $invoice->items()->create([
                    'item_id' => $item->item_id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'tax_rate' => $item->tax_rate,
                    'tax_amount' => $item->tax_amount,
                    'total' => $item->total,
                ]);
            }

            $quote->update([
                'status' => 'converted',
                'converted_invoice_id' => $invoice->id,
            ]);

            return $invoice;
        });

        $this->logActivity('converted', "Converted quote {$quote->quote_number} to invoice {$invoice->invoice_number}", 'Quote', $quote->id);

        return redirect()->route('invoices.edit', $invoice->id)->with('success', "Quotation converted to invoice {$invoice->invoice_number}.");
    }

    public function print($id)
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';
        $quote = Quote::with(['customer', 'items'])->findOrFail($id);

        return view('quotes.print', compact('quote', 'company', 'currencySymbol'));
    }

    public function publicShow($token)
    {
        $quote = Quote::where('public_token', $token)->with(['customer', 'items'])->firstOrFail();
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';

        return view('quotes.public', compact('quote', 'company', 'currencySymbol'));
    }

    public function exportCsv()
    {
        $quotes = Quote::with('customer')->latest()->get();
        $headers = ['Quote #', 'Customer', 'Date', 'Expiry', 'Subtotal', 'Tax', 'Total', 'Status'];
        $rows = [];
        foreach ($quotes as $q) {
            $rows[] = [
                $q->quote_number,
                $q->customer->name ?? 'N/A',
                $q->quote_date,
                $q->expiry_date,
                number_format($q->subtotal, 2),
                number_format($q->tax_total, 2),
                number_format($q->total, 2),
                $q->status,
            ];
        }

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
            'Content-Disposition' => 'attachment; filename="quotations.csv"',
        ]);
    }
}
