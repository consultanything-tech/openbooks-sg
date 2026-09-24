<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\RecurringTemplate;
use App\Models\RecurringTemplateItem;
use App\Models\Tax;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecurringController extends Controller
{
    use \App\Traits\LogsActivity;

    public function index(Request $request)
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';

        $query = RecurringTemplate::with(['customer', 'vendor'])->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', function ($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%");
                })->orWhereHas('vendor', function ($vq) use ($search) {
                    $vq->where('name', 'like', "%{$search}%");
                });
            });
        }

        $templates = $query->paginate(15);

        return view('recurring.index', compact('templates', 'company', 'currencySymbol'));
    }

    public function create(Request $request)
    {
        $type = $request->get('type', 'invoice');
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $items = Item::with('tax')->where('is_active', true)->orderBy('name')->get();
        $taxes = Tax::where('is_active', true)->orderBy('name')->get();
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';

        return view('recurring.create', compact('type', 'customers', 'vendors', 'items', 'taxes', 'company', 'currencySymbol'));
    }

    public function store(Request $request)
    {
        $items = $request->input('items', []);
        if (is_array($items)) {
            foreach ($items as $idx => $item) {
                if (!isset($item['name']) && isset($item['item_name'])) {
                    $items[$idx]['name'] = $item['item_name'];
                }
            }
            $request->merge(['items' => $items]);
        }
        if (!$request->has('discount_total') && $request->has('discount')) {
            $request->merge(['discount_total' => $request->input('discount')]);
        }

        $validated = $request->validate([
            'type' => 'required|in:invoice,bill',
            'customer_id' => 'required_if:type,invoice|nullable|exists:customers,id',
            'vendor_id' => 'required_if:type,bill|nullable|exists:vendors,id',
            'frequency' => 'required|in:weekly,monthly,quarterly,yearly',
            'next_due_date' => 'required|date',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
            'discount_total' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
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

            $template = RecurringTemplate::create([
                'type' => $validated['type'],
                'customer_id' => $validated['customer_id'] ?? null,
                'vendor_id' => $validated['vendor_id'] ?? null,
                'frequency' => $validated['frequency'],
                'next_due_date' => $validated['next_due_date'],
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discount,
                'total' => $grandTotal,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'is_active' => true,
            ]);

            foreach ($itemRows as $itemData) {
                $template->items()->create($itemData);
            }
        });

        $this->logActivity('created', "Created recurring {$validated['type']} template", 'RecurringTemplate', null);

        return redirect()->route('recurring.index')->with('success', 'Recurring template created successfully.');
    }

    public function show($id)
    {
        $template = RecurringTemplate::with(['customer', 'vendor', 'items'])->findOrFail($id);
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';

        // Get generated invoices/bills
        if ($template->type === 'invoice') {
            $generated = Invoice::where('recurring_template_id', $template->id)->with('customer')->latest()->get();
        } else {
            $generated = Bill::where('recurring_template_id', $template->id)->with('vendor')->latest()->get();
        }

        return view('recurring.show', compact('template', 'company', 'currencySymbol', 'generated'));
    }

    public function edit($id)
    {
        $template = RecurringTemplate::with('items')->findOrFail($id);
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $items = Item::with('tax')->where('is_active', true)->orderBy('name')->get();
        $taxes = Tax::where('is_active', true)->orderBy('name')->get();
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';

        return view('recurring.edit', compact('template', 'customers', 'vendors', 'items', 'taxes', 'company', 'currencySymbol'));
    }

    public function update(Request $request, $id)
    {
        $template = RecurringTemplate::findOrFail($id);

        $items = $request->input('items', []);
        if (is_array($items)) {
            foreach ($items as $idx => $item) {
                if (!isset($item['name']) && isset($item['item_name'])) {
                    $items[$idx]['name'] = $item['item_name'];
                }
            }
            $request->merge(['items' => $items]);
        }
        if (!$request->has('discount_total') && $request->has('discount')) {
            $request->merge(['discount_total' => $request->input('discount')]);
        }

        $validated = $request->validate([
            'type' => 'required|in:invoice,bill',
            'customer_id' => 'required_if:type,invoice|nullable|exists:customers,id',
            'vendor_id' => 'required_if:type,bill|nullable|exists:vendors,id',
            'frequency' => 'required|in:weekly,monthly,quarterly,yearly',
            'next_due_date' => 'required|date',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
            'discount_total' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($template, $validated) {
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

            $template->update([
                'type' => $validated['type'],
                'customer_id' => $validated['customer_id'] ?? null,
                'vendor_id' => $validated['vendor_id'] ?? null,
                'frequency' => $validated['frequency'],
                'next_due_date' => $validated['next_due_date'],
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discount,
                'total' => $grandTotal,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
            ]);

            $template->items()->delete();

            foreach ($itemRows as $itemData) {
                $template->items()->create($itemData);
            }
        });

        $this->logActivity('updated', "Updated recurring template #{$template->id}", 'RecurringTemplate', $template->id);

        return redirect()->route('recurring.show', $template->id)->with('success', 'Recurring template updated successfully.');
    }

    public function destroy($id)
    {
        $template = RecurringTemplate::findOrFail($id);
        // Line items are kept so "Undo" can put the template back intact.
        $template->delete();

        $this->logActivity('deleted', "Deleted recurring template #{$id}", 'RecurringTemplate', $id);

        return redirect()->route('recurring.index')
            ->with('success', 'Recurring template deleted successfully.')
            ->with('undo_url', route('recurring.restore', $template->id))
            ->with('undo_label', 'Undo');
    }

    /** Restore a soft-deleted recurring template (the "Undo" action on the delete toast). */
    public function restore($id)
    {
        $template = RecurringTemplate::withTrashed()->findOrFail($id);

        if (! $template->trashed()) {
            return redirect()->route('recurring.index')->with('info', 'That template is already active.');
        }

        $template->restore();
        $this->logActivity('restored', "Restored recurring template #{$template->id}", 'RecurringTemplate', $template->id);

        return redirect()->route('recurring.index')->with('success', 'Recurring template restored.');
    }

    public function toggle($id)
    {
        $template = RecurringTemplate::findOrFail($id);
        $template->update(['is_active' => !$template->is_active]);

        $status = $template->is_active ? 'activated' : 'paused';
        $this->logActivity('toggled', "Recurring template #{$id} {$status}", 'RecurringTemplate', $id);

        return back()->with('success', 'Recurring template ' . $status . '.');
    }

    public function generateNow($id)
    {
        $template = RecurringTemplate::with('items')->findOrFail($id);

        if (!$template->is_active) {
            return back()->with('error', 'Cannot generate from a paused template.');
        }

        $result = $this->generateFromTemplate($template);

        $this->logActivity('generated', "Manually generated {$template->type} from recurring template #{$id}", 'RecurringTemplate', $id);

        return back()->with('success', ucfirst($template->type) . ' generated successfully.');
    }

    /**
     * Generate an Invoice or Bill from a recurring template.
     */
    private function generateFromTemplate(RecurringTemplate $template): Invoice|Bill
    {
        return DB::transaction(function () use ($template) {
            $today = now()->toDateString();

            if ($template->type === 'invoice') {
                $result = $this->generateInvoice($template, $today);
            } else {
                $result = $this->generateBill($template, $today);
            }

            // Update template schedule
            $template->update([
                'last_generated_at' => now(),
                'next_due_date' => $this->calculateNextDueDate($template->next_due_date, $template->frequency),
            ]);

            return $result;
        });
    }

    private function generateInvoice(RecurringTemplate $template, string $today): Invoice
    {
        $lastId = Invoice::withTrashed()->max('id') ?? 0;
        $nextNumber = 'INV-' . date('Y') . '-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);

        $dueDate = $this->calculateDueDateFromFrequency($today, $template->frequency);

        $invoice = Invoice::create([
            'invoice_number' => $nextNumber,
            'customer_id' => $template->customer_id,
            'invoice_date' => $today,
            'due_date' => $dueDate,
            'subtotal' => $template->subtotal,
            'tax_total' => $template->tax_total,
            'discount_total' => $template->discount_total,
            'total' => $template->total,
            'paid_amount' => 0.00,
            'due_amount' => $template->total,
            'status' => 'sent',
            'notes' => $template->notes,
            'terms' => $template->terms ?? 'Payment due within 30 days.',
            'public_token' => Str::random(40),
            'recurring_template_id' => $template->id,
        ]);

        foreach ($template->items as $item) {
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

        // Increment customer balance
        $customer = Customer::find($template->customer_id);
        if ($customer) {
            $customer->increment('balance', $template->total);
        }

        return $invoice;
    }

    private function generateBill(RecurringTemplate $template, string $today): Bill
    {
        $lastId = Bill::withTrashed()->max('id') ?? 0;
        $nextNumber = 'BILL-' . date('Y') . '-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);

        $dueDate = $this->calculateDueDateFromFrequency($today, $template->frequency);

        $bill = Bill::create([
            'bill_number' => $nextNumber,
            'vendor_id' => $template->vendor_id,
            'bill_date' => $today,
            'due_date' => $dueDate,
            'subtotal' => $template->subtotal,
            'tax_total' => $template->tax_total,
            'discount_total' => $template->discount_total,
            'total' => $template->total,
            'paid_amount' => 0,
            'due_amount' => $template->total,
            'status' => 'received',
            'notes' => $template->notes,
            'recurring_template_id' => $template->id,
        ]);

        foreach ($template->items as $item) {
            BillItem::create([
                'bill_id' => $bill->id,
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

        // Increment vendor balance
        $vendor = Vendor::find($template->vendor_id);
        if ($vendor) {
            $vendor->increment('balance', $template->total);
        }

        return $bill;
    }

    private function calculateNextDueDate($currentDate, string $frequency): string
    {
        $date = \Carbon\Carbon::parse($currentDate);

        return match ($frequency) {
            'weekly' => $date->addWeek()->toDateString(),
            'monthly' => $date->addMonth()->toDateString(),
            'quarterly' => $date->addMonths(3)->toDateString(),
            'yearly' => $date->addYear()->toDateString(),
        };
    }

    private function calculateDueDateFromFrequency(string $today, string $frequency): string
    {
        $date = \Carbon\Carbon::parse($today);

        return match ($frequency) {
            'weekly' => $date->addWeek()->toDateString(),
            'monthly' => $date->addDays(30)->toDateString(),
            'quarterly' => $date->addDays(30)->toDateString(),
            'yearly' => $date->addDays(30)->toDateString(),
        };
    }
}
