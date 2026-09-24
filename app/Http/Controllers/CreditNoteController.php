<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Tax;
use App\Models\Transaction;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class CreditNoteController extends Controller
{
    use LogsActivity;

    public function index(Request $request)
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';
        $query = CreditNote::with('customer', 'invoice')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $creditNotes = $query->paginate(15);
        $customers = Customer::all();

        return view('credit-notes.index', compact('creditNotes', 'customers', 'company', 'currencySymbol'));
    }

    public function create(Request $request)
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $items = Item::with('tax')->where('is_active', true)->orderBy('name')->get();
        $taxes = Tax::where('is_active', true)->orderBy('name')->get();
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';

        // Auto generate next credit note number
        $lastId = CreditNote::withTrashed()->max('id') ?? 0;
        $nextNumber = 'CN-'.date('Y').'-'.str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);

        // Pre-fill from invoice if provided
        $invoice = null;
        $invoiceItems = [];
        if ($request->filled('invoice_id')) {
            $invoice = Invoice::with('items', 'customer')->whereKey($request->invoice_id)->first();
            if ($invoice) {
                $invoiceItems = $invoice->items;
            }
        }

        // Get invoices for dropdown
        $invoices = Invoice::whereIn('status', ['sent', 'partial', 'paid', 'overdue'])
            ->with('customer')
            ->orderBy('invoice_number', 'desc')
            ->get();

        return view('credit-notes.create', compact(
            'customers', 'items', 'taxes', 'company', 'currencySymbol',
            'nextNumber', 'invoice', 'invoiceItems', 'invoices'
        ));
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
            'credit_note_number' => 'required|string|unique:credit_notes,credit_note_number',
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'credit_note_date' => 'required|date',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable|exists:items,id',
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
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

            $grandTotal = $subtotal + $taxTotal;

            $creditNote = CreditNote::create([
                'credit_note_number' => $validated['credit_note_number'],
                'customer_id' => $validated['customer_id'],
                'invoice_id' => $validated['invoice_id'] ?? null,
                'credit_note_date' => $validated['credit_note_date'],
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $grandTotal,
                'status' => $isDraft ? 'draft' : 'issued',
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($itemRows as $itemData) {
                $creditNote->items()->create($itemData);
            }

            // When issued, reduce customer balance
            if (! $isDraft) {
                $customer = Customer::find($validated['customer_id']);
                if ($customer) {
                    $customer->decrement('balance', $grandTotal);
                }
            }
        });

        $this->logActivity('created', "Created credit note {$validated['credit_note_number']}", 'CreditNote', null);

        $message = $isDraft ? 'Credit note saved as draft.' : 'Credit note issued successfully.';

        return redirect()->route('credit_notes.index')->with('success', $message);
    }

    public function show($id)
    {
        $company = Company::first() ?? new Company(['name' => 'OpenBooks Enterprise', 'currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';
        $creditNote = CreditNote::with(['customer', 'items', 'invoice'])->findOrFail($id);

        return view('credit-notes.show', compact('creditNote', 'company', 'currencySymbol'));
    }

    public function print($id)
    {
        $company = Company::first() ?? new Company(['name' => 'OpenBooks Enterprise', 'currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';
        $creditNote = CreditNote::with(['customer', 'items', 'invoice'])->findOrFail($id);

        return view('credit-notes.print', compact('creditNote', 'company', 'currencySymbol'));
    }

    public function edit($id)
    {
        $creditNote = CreditNote::with('items')->findOrFail($id);

        if ($creditNote->status !== 'draft') {
            return redirect()->route('credit_notes.show', $creditNote->id)
                ->with('error', 'Only draft credit notes can be edited.');
        }

        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $items = Item::with('tax')->where('is_active', true)->orderBy('name')->get();
        $taxes = Tax::where('is_active', true)->orderBy('name')->get();
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';

        $invoices = Invoice::whereIn('status', ['sent', 'partial', 'paid', 'overdue'])
            ->with('customer')
            ->orderBy('invoice_number', 'desc')
            ->get();

        return view('credit-notes.edit', compact(
            'creditNote', 'customers', 'items', 'taxes', 'company', 'currencySymbol', 'invoices'
        ));
    }

    public function update(Request $request, $id)
    {
        $creditNote = CreditNote::findOrFail($id);

        if ($creditNote->status !== 'draft') {
            return redirect()->route('credit_notes.show', $creditNote->id)
                ->with('error', 'Only draft credit notes can be updated.');
        }

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
            'credit_note_number' => 'required|string|unique:credit_notes,credit_note_number,'.$creditNote->id,
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'credit_note_date' => 'required|date',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable|exists:items,id',
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
        ]);

        $requestedStatus = $request->input('status', 'draft');

        DB::transaction(function () use ($creditNote, $validated, $requestedStatus) {
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

            $grandTotal = $subtotal + $taxTotal;
            $newStatus = $requestedStatus ?: 'draft';

            $creditNote->update([
                'credit_note_number' => $validated['credit_note_number'],
                'customer_id' => $validated['customer_id'],
                'invoice_id' => $validated['invoice_id'] ?? null,
                'credit_note_date' => $validated['credit_note_date'],
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $grandTotal,
                'status' => $newStatus,
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $creditNote->items()->delete();
            foreach ($itemRows as $itemData) {
                $creditNote->items()->create($itemData);
            }

            // If being issued now, reduce customer balance
            if ($newStatus === 'issued') {
                $customer = Customer::find($validated['customer_id']);
                if ($customer) {
                    $customer->decrement('balance', $grandTotal);
                }
            }
        });

        $this->logActivity('updated', "Updated credit note {$creditNote->credit_note_number}", 'CreditNote', $creditNote->id);

        return redirect()->route('credit_notes.show', $creditNote->id)->with('success', 'Credit note updated successfully.');
    }

    public function destroy($id)
    {
        $creditNote = CreditNote::findOrFail($id);

        if (! in_array($creditNote->status, ['draft', 'cancelled'])) {
            return redirect()->back()->with('error', 'Only draft or cancelled credit notes can be deleted.');
        }

        // Line items are kept so "Undo" can put the credit note back intact.
        $creditNote->delete();

        $this->logActivity('deleted', "Deleted credit note {$creditNote->credit_note_number}", 'CreditNote', $creditNote->id);

        return redirect()->route('credit_notes.index')
            ->with('success', 'Credit note deleted successfully.')
            ->with('undo_url', route('credit_notes.restore', $creditNote->id))
            ->with('undo_label', 'Undo');
    }

    /** Restore a soft-deleted credit note (the "Undo" action on the delete toast). */
    public function restore($id)
    {
        $creditNote = CreditNote::withTrashed()->findOrFail($id);

        if (! $creditNote->trashed()) {
            return redirect()->route('credit_notes.index')->with('info', 'That credit note is already active.');
        }

        $creditNote->restore();
        $this->logActivity('restored', "Restored credit note {$creditNote->credit_note_number}", 'CreditNote', $creditNote->id);

        return redirect()->route('credit_notes.index')->with('success', "Credit note {$creditNote->credit_note_number} restored.");
    }

    public function apply($id)
    {
        $creditNote = CreditNote::with('invoice', 'customer')->findOrFail($id);

        if ($creditNote->status !== 'issued') {
            return redirect()->back()->with('error', 'Only issued credit notes can be applied.');
        }

        DB::transaction(function () use ($creditNote) {
            $amount = (float) $creditNote->total;

            // 1. Mark as applied
            $creditNote->update(['status' => 'applied']);

            // 2. If linked to an invoice, reduce the invoice due_amount and increase paid_amount
            if ($creditNote->invoice) {
                $invoice = $creditNote->invoice;
                $invoice->paid_amount += $amount;
                $invoice->due_amount = max(0, $invoice->total - $invoice->paid_amount);

                if ($invoice->due_amount <= 0.001) {
                    $invoice->status = 'paid';
                } elseif ($invoice->paid_amount > 0) {
                    $invoice->status = 'partial';
                }
                $invoice->save();
            }

            // 3. Create a transaction record for the credit note application
            $incomeCatId = Category::where('type', 'income')->value('id');

            Transaction::create([
                'type' => 'income',
                'customer_id' => $creditNote->customer_id,
                'invoice_id' => $creditNote->invoice_id,
                'category_id' => $incomeCatId,
                'amount' => -$amount,
                'payment_method' => 'Credit Note',
                'reference_number' => $creditNote->credit_note_number,
                'transaction_date' => now()->toDateString(),
                'description' => 'Credit note '.$creditNote->credit_note_number.' applied'
                    .($creditNote->invoice ? ' to '.$creditNote->invoice->invoice_number : ''),
            ]);
        });

        $this->logActivity('applied', "Applied credit note {$creditNote->credit_note_number}".($creditNote->invoice ? " to invoice {$creditNote->invoice->invoice_number}" : ''), 'CreditNote', $creditNote->id);

        return redirect()->route('credit_notes.show', $creditNote->id)->with('success', 'Credit note applied successfully.');
    }
}
