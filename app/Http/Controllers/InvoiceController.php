<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Company;
use App\Models\CurrencyRate;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Tax;
use App\Models\Transaction;
use App\Traits\HandlesBulkActions;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    use HandlesBulkActions;
    use LogsActivity;

    protected function bulkModelClass(): string
    {
        return Invoice::class;
    }

    protected function bulkIndexRoute(): string
    {
        return 'invoices.index';
    }

    protected function bulkRestoreRouteName(): string
    {
        return 'invoices.bulk_restore';
    }

    public function index(Request $request)
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';
        $query = Invoice::with('customer')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $invoices = $query->paginate(15);
        $customers = Customer::all();

        return view('invoices.index', compact('invoices', 'customers', 'company', 'currencySymbol'));
    }

    public function create()
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $items = Item::with('tax')->where('is_active', true)->orderBy('name')->get();
        $taxes = Tax::where('is_active', true)->orderBy('name')->get();
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencies = CurrencyRate::where('is_active', true)->orderBy('currency_code')->get();

        // Auto generate next invoice number
        $lastId = Invoice::withTrashed()->max('id') ?? 0;
        $nextNumber = 'INV-'.date('Y').'-'.str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
        $nextInvoiceNumber = $nextNumber;

        return view('invoices.create', compact('customers', 'items', 'taxes', 'company', 'nextNumber', 'nextInvoiceNumber', 'currencies'));
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
        if (! $request->has('discount_total') && $request->has('discount')) {
            $request->merge(['discount_total' => $request->input('discount')]);
        }

        $validated = $request->validate([
            'invoice_number' => 'required|string|unique:invoices,invoice_number',
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable|exists:items,id',
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
            'discount_total' => 'nullable|numeric|min:0',
        ]);

        $isDraft = $request->input('status') === 'draft';

        DB::transaction(function () use ($validated, $isDraft) {
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

            $invoice = Invoice::create([
                'invoice_number' => $validated['invoice_number'],
                'customer_id' => $validated['customer_id'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discount,
                'total' => $grandTotal,
                'paid_amount' => 0.00,
                'due_amount' => $grandTotal,
                'status' => $isDraft ? 'draft' : 'sent',
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? 'Payment due within 30 days.',
                'public_token' => Str::random(40),
                'currency_code' => request('currency_code', 'SGD'),
                'exchange_rate' => request('exchange_rate', 1.000000),
            ]);

            foreach ($itemRows as $itemData) {
                $invoice->items()->create($itemData);
            }

            // Auto-deduct stock for inventory-tracked items
            foreach ($itemRows as $itemData) {
                if (! empty($itemData['item_id'])) {
                    $inventoryItem = Item::find($itemData['item_id']);
                    if ($inventoryItem && $inventoryItem->track_inventory) {
                        $inventoryItem->decrement('stock_quantity', $itemData['quantity']);

                        StockMovement::create([
                            'item_id' => $inventoryItem->id,
                            'type' => 'sale',
                            'quantity' => -$itemData['quantity'],
                            'reference_type' => 'Invoice',
                            'reference_id' => $invoice->id,
                            'notes' => 'Auto-deducted via invoice '.$validated['invoice_number'],
                            'user_id' => auth()->id(),
                        ]);
                    }
                }
            }

            // Only update customer balance for non-draft invoices
            if (! $isDraft) {
                $customer = Customer::find($validated['customer_id']);
                if ($customer) {
                    $customer->increment('balance', $grandTotal);
                }
            }
        });

        $this->logActivity('created', "Created invoice {$validated['invoice_number']}", 'Invoice', null);

        $message = $isDraft ? 'Invoice saved as draft.' : 'Invoice generated successfully.';

        return redirect()->route('invoices.index')->with('success', $message);
    }

    public function show($id)
    {
        $company = Company::first() ?? new Company(['name' => 'OpenBooks Enterprise', 'currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';
        $invoice = $id instanceof Invoice ? $id : Invoice::with(['customer', 'items', 'transactions.bankAccount'])->findOrFail($id);
        $bankAccounts = BankAccount::all();

        return view('invoices.show', compact('invoice', 'company', 'currencySymbol', 'bankAccounts'));
    }

    public function print($id)
    {
        $company = Company::first() ?? new Company(['name' => 'OpenBooks Enterprise', 'currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';
        $invoice = $id instanceof Invoice ? $id : Invoice::with(['customer', 'items'])->findOrFail($id);

        return view('invoices.print', compact('invoice', 'company', 'currencySymbol'));
    }

    public function publicShow($token)
    {
        return $this->publicView($token);
    }

    public function publicView($token)
    {
        $invoice = Invoice::where('public_token', $token)->with(['customer', 'items', 'transactions'])->firstOrFail();
        $company = Company::first() ?? new Company(['name' => 'OpenBooks Enterprise', 'currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';

        return view('invoices.public', compact('invoice', 'company', 'currencySymbol'));
    }

    public function recordPayment(Request $request, $id)
    {
        $invoice = $id instanceof Invoice ? $id : Invoice::findOrFail($id);
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:'.$invoice->due_amount,
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($invoice, $validated) {
            $amount = (float) $validated['amount'];

            // 1. Update Invoice totals
            $invoice->paid_amount += $amount;
            $invoice->due_amount = max(0, $invoice->total - $invoice->paid_amount);

            if ($invoice->due_amount <= 0.001) {
                $invoice->status = 'paid';
            } else {
                $invoice->status = 'partial';
            }
            $invoice->save();

            // 2. Update Customer Balance
            $customer = $invoice->customer;
            if ($customer) {
                $customer->decrement('balance', $amount);
            }

            // 3. Update Bank Account Balance
            $bankAccount = BankAccount::find($validated['bank_account_id']);
            if ($bankAccount) {
                $bankAccount->increment('current_balance', $amount);
            }

            // 4. Create Income Transaction in Ledger
            $incomeCatId = Category::where('type', 'income')->value('id');

            Transaction::create([
                'type' => 'income',
                'bank_account_id' => $validated['bank_account_id'],
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'category_id' => $incomeCatId,
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? ('REC-'.strtoupper(Str::random(8))),
                'transaction_date' => $validated['payment_date'],
                'description' => $validated['description'] ?? ('Payment received for '.$invoice->invoice_number),
            ]);
        });

        $this->logActivity('payment_recorded', "Recorded payment of {$validated['amount']} for invoice {$invoice->invoice_number}", 'Invoice', $invoice->id);

        return back()->with('success', 'Payment of '.$validated['amount'].' recorded successfully.');
    }

    public function edit($id)
    {
        $invoice = Invoice::with('items')->findOrFail($id);
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $items = Item::with('tax')->where('is_active', true)->orderBy('name')->get();
        $taxes = Tax::where('is_active', true)->orderBy('name')->get();
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';
        $currencies = CurrencyRate::where('is_active', true)->orderBy('currency_code')->get();

        return view('invoices.edit', compact('invoice', 'customers', 'items', 'taxes', 'company', 'currencySymbol', 'currencies'));
    }

    public function update(Request $request, $id)
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
        if (! $request->has('discount_total') && $request->has('discount')) {
            $request->merge(['discount_total' => $request->input('discount')]);
        }

        $invoice = Invoice::whereKey($id)->firstOrFail();

        $validated = $request->validate([
            'invoice_number' => 'required|string|unique:invoices,invoice_number,'.$invoice->id,
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable|exists:items,id',
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
            'discount_total' => 'nullable|numeric|min:0',
        ]);

        $requestedStatus = $request->input('status');

        DB::transaction(function () use ($invoice, $validated, $requestedStatus) {
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

            $oldStatus = $invoice->status;
            $newStatus = $requestedStatus ?: $oldStatus;

            // Determine if balance adjustments are needed
            $wasActive = ! in_array($oldStatus, ['draft']);
            $willBeActive = ! in_array($newStatus, ['draft']);

            // Reverse old customer balance if invoice was previously active
            if ($wasActive) {
                $customer = Customer::find($invoice->customer_id);
                if ($customer) {
                    $customer->decrement('balance', (float) $invoice->due_amount);
                }
            }

            $invoice->update([
                'invoice_number' => $validated['invoice_number'],
                'customer_id' => $validated['customer_id'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discount,
                'total' => $grandTotal,
                'due_amount' => max(0, $grandTotal - $invoice->paid_amount),
                'status' => $newStatus,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? 'Payment due within 30 days.',
                'currency_code' => request('currency_code', $invoice->currency_code ?? 'SGD'),
                'exchange_rate' => request('exchange_rate', $invoice->exchange_rate ?? 1.000000),
            ]);

            // Apply new customer balance only if invoice is now active
            if ($willBeActive) {
                $newCustomer = Customer::find($validated['customer_id']);
                if ($newCustomer) {
                    $newCustomer->increment('balance', (float) $invoice->due_amount);
                }
            }

            // Delete old items and re-create
            $invoice->items()->delete();

            foreach ($itemRows as $itemData) {
                $invoice->items()->create($itemData);
            }
        });

        $this->logActivity('updated', "Updated invoice {$invoice->invoice_number}", 'Invoice', $invoice->id);

        return redirect()->route('invoices.show', $invoice->id)->with('success', 'Invoice updated successfully.');
    }

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status === 'paid') {
            return redirect()->back()->with('error', 'Cannot delete a paid invoice.');
        }

        DB::transaction(function () use ($invoice) {
            // Reverse customer balance for unpaid portion
            $customer = Customer::find($invoice->customer_id);
            if ($customer) {
                $customer->decrement('balance', (float) $invoice->due_amount);
            }

            // Line items are kept so "Undo" can put the invoice back intact.
            $invoice->delete();
        });

        $this->logActivity('deleted', "Deleted invoice {$invoice->invoice_number}", 'Invoice', $invoice->id);

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.')
            ->with('undo_url', route('invoices.restore', $invoice->id))
            ->with('undo_label', 'Undo');
    }

    /**
     * Restore a soft-deleted invoice (the "Undo" action on the delete toast),
     * re-applying the customer balance that destroy() reversed.
     */
    public function restore($id)
    {
        $invoice = Invoice::withTrashed()->findOrFail($id);

        if (! $invoice->trashed()) {
            return redirect()->route('invoices.index')->with('info', 'That invoice is already active.');
        }

        DB::transaction(function () use ($invoice) {
            $customer = Customer::withTrashed()->find($invoice->customer_id);
            if ($customer) {
                $customer->increment('balance', (float) $invoice->due_amount);
            }

            $invoice->restore();
        });

        $this->logActivity('restored', "Restored invoice {$invoice->invoice_number}", 'Invoice', $invoice->id);

        return redirect()->route('invoices.index')->with('success', "Invoice {$invoice->invoice_number} restored.");
    }

    public function markSent($id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status !== 'draft') {
            return redirect()->back()->with('error', 'Only draft invoices can be marked as sent.');
        }

        DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => 'sent']);

            // Apply customer balance now that invoice is active
            $customer = Customer::find($invoice->customer_id);
            if ($customer) {
                $customer->increment('balance', (float) $invoice->due_amount);
            }
        });

        $this->logActivity('marked_sent', "Marked invoice {$invoice->invoice_number} as sent", 'Invoice', $invoice->id);

        return redirect()->back()->with('success', 'Invoice marked as sent.');
    }

    public function exportCsv()
    {
        $invoices = Invoice::with('customer')->latest()->get();

        $headers = ['Invoice #', 'Customer', 'Date', 'Due Date', 'Subtotal', 'Tax', 'Discount', 'Total', 'Paid', 'Due', 'Status'];
        $rows = [];
        foreach ($invoices as $inv) {
            $rows[] = [
                $inv->invoice_number,
                $inv->customer->name ?? 'N/A',
                $inv->invoice_date,
                $inv->due_date,
                number_format((float) $inv->subtotal, 2),
                number_format((float) $inv->tax_total, 2),
                number_format((float) $inv->discount_total, 2),
                number_format((float) $inv->total, 2),
                number_format((float) $inv->paid_amount, 2),
                number_format((float) $inv->due_amount, 2),
                $inv->status,
            ];
        }

        return $this->buildCsvResponse('invoices.csv', $headers, $rows);
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

    public function duplicate($id)
    {
        $invoice = Invoice::with('items')->whereKey($id)->firstOrFail();

        $newInvoice = DB::transaction(function () use ($invoice) {
            // Generate next invoice number
            $lastId = Invoice::withTrashed()->max('id') ?? 0;
            $nextNumber = 'INV-'.date('Y').'-'.str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);

            $newInvoice = Invoice::create([
                'invoice_number' => $nextNumber,
                'customer_id' => $invoice->customer_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => $invoice->due_date,
                'subtotal' => $invoice->subtotal,
                'tax_total' => $invoice->tax_total,
                'discount_total' => $invoice->discount_total,
                'total' => $invoice->total,
                'paid_amount' => 0.00,
                'due_amount' => $invoice->total,
                'status' => 'draft',
                'notes' => $invoice->notes,
                'terms' => $invoice->terms,
                'order_number' => $invoice->order_number ?? null,
                'public_token' => Str::random(40),
                'currency_code' => $invoice->currency_code ?? 'SGD',
                'exchange_rate' => $invoice->exchange_rate ?? 1.000000,
            ]);

            foreach ($invoice->items as $item) {
                $newInvoice->items()->create([
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

            // Draft invoices don't affect customer balance
            // Balance will be applied when the invoice is marked as sent

            return $newInvoice;
        });

        $this->logActivity('duplicated', "Duplicated invoice {$invoice->invoice_number} as {$newInvoice->invoice_number}", 'Invoice', $newInvoice->id);

        return redirect()->route('invoices.edit', $newInvoice->id)->with('success', 'Invoice duplicated successfully.');
    }
}
