<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Vendor;
use App\Models\BankAccount;
use App\Models\CurrencyRate;
use App\Models\Transaction;
use App\Models\Company;
use App\Models\Item;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{
    use \App\Traits\LogsActivity;
    use \App\Traits\HandlesBulkActions;

    protected function bulkModelClass(): string { return \App\Models\Bill::class; }
    protected function bulkIndexRoute(): string { return 'bills.index'; }
    protected function bulkRestoreRouteName(): string { return 'bills.bulk_restore'; }
    public function index(Request $request)
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $query = Bill::with('vendor')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        $bills = $query->paginate(15);
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();

        return view('bills.index', compact('bills', 'vendors', 'company'));
    }

    public function create()
    {
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $items = Item::with('tax')->where('is_active', true)->orderBy('name')->get();
        $taxes = Tax::where('is_active', true)->get();
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencies = CurrencyRate::where('is_active', true)->orderBy('currency_code')->get();
        $nextBillNumber = 'BILL-' . date('Y') . '-' . str_pad((Bill::withTrashed()->max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT);

        return view('bills.create', compact('vendors', 'items', 'taxes', 'company', 'currencies', 'nextBillNumber'));
    }

    public function store(Request $request)
    {
        $items = $request->input('items', []);
        if (is_array($items)) {
            foreach ($items as $idx => $item) {
                if (!isset($item['item_name']) && isset($item['name'])) {
                    $items[$idx]['item_name'] = $item['name'];
                }
            }
            $request->merge(['items' => $items]);
        }

        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'bill_number' => 'required|unique:bills,bill_number',
            'bill_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:bill_date',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $isDraft = $request->input('status') === 'draft';

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($request->items as $it) {
                $lineSub = $it['quantity'] * $it['price'];
                $lineTax = 0;
                if (!empty($it['tax_rate'])) {
                    $lineTax = ($lineSub * $it['tax_rate']) / 100;
                }
                $subtotal += $lineSub;
                $taxTotal += $lineTax;
            }

            $discount = (float) ($request->discount ?? 0);
            $totalAmount = max(0, $subtotal + $taxTotal - $discount);

            $bill = Bill::create([
                'vendor_id' => $request->vendor_id,
                'bill_number' => $request->bill_number,
                'bill_date' => $request->bill_date,
                'due_date' => $request->due_date,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discount,
                'total' => $totalAmount,
                'paid_amount' => 0,
                'due_amount' => $totalAmount,
                'status' => $isDraft ? 'draft' : 'received',
                'notes' => $request->notes ?? null,
                'currency_code' => $request->input('currency_code', 'SGD'),
                'exchange_rate' => $request->input('exchange_rate', 1.000000),
            ]);

            foreach ($request->items as $it) {
                $lineSub = $it['quantity'] * $it['price'];
                $taxRate = !empty($it['tax_rate']) ? (float)$it['tax_rate'] : 0;
                $lineTax = ($lineSub * $taxRate) / 100;

                BillItem::create([
                    'bill_id' => $bill->id,
                    'item_id' => $it['item_id'] ?? null,
                    'name' => $it['item_name'],
                    'quantity' => $it['quantity'],
                    'price' => $it['price'],
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                    'total' => $lineSub + $lineTax,
                ]);
            }

            // Only increase vendor payable balance for non-draft bills
            if (!$isDraft) {
                $vendor = Vendor::find($request->vendor_id);
                if ($vendor) {
                    $vendor->increment('balance', $totalAmount);
                }
            }

            DB::commit();
            $this->logActivity('created', "Created bill {$bill->bill_number}", 'Bill', $bill->id);
            $message = $isDraft ? 'Vendor bill saved as draft.' : 'Vendor bill created successfully.';
            return redirect()->route('bills.show', $bill->id)->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error creating bill: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $bill = Bill::with(['vendor', 'items'])->findOrFail($id);
        $bankAccounts = BankAccount::all();

        return view('bills.show', compact('bill', 'bankAccounts'));
    }

    public function print($id)
    {
        $company = Company::first() ?? new Company(['name' => 'OpenBooks Enterprise', 'currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';
        $bill = Bill::with(['vendor', 'items'])->findOrFail($id);

        return view('bills.print', compact('bill', 'company', 'currencySymbol'));
    }

    public function edit($id)
    {
        $bill = Bill::with('items')->findOrFail($id);
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $items = Item::with('tax')->where('is_active', true)->orderBy('name')->get();
        $taxes = Tax::where('is_active', true)->get();
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencies = CurrencyRate::where('is_active', true)->orderBy('currency_code')->get();

        return view('bills.edit', compact('bill', 'vendors', 'items', 'taxes', 'company', 'currencies'));
    }

    public function update(Request $request, $id)
    {
        $items = $request->input('items', []);
        if (is_array($items)) {
            foreach ($items as $idx => $item) {
                if (!isset($item['item_name']) && isset($item['name'])) {
                    $items[$idx]['item_name'] = $item['name'];
                }
            }
            $request->merge(['items' => $items]);
        }

        $bill = Bill::findOrFail($id);

        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'bill_number' => 'required|unique:bills,bill_number,' . $bill->id,
            'bill_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:bill_date',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $requestedStatus = $request->input('status');

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($request->items as $it) {
                $lineSub = $it['quantity'] * $it['price'];
                $lineTax = 0;
                if (!empty($it['tax_rate'])) {
                    $lineTax = ($lineSub * $it['tax_rate']) / 100;
                }
                $subtotal += $lineSub;
                $taxTotal += $lineTax;
            }

            $discount = (float) ($request->discount ?? 0);
            $totalAmount = max(0, $subtotal + $taxTotal - $discount);

            $oldStatus = $bill->status;
            $wasActive = !in_array($oldStatus, ['draft']);

            // Reverse old vendor balance adjustment only if bill was active
            if ($wasActive) {
                $oldVendor = Vendor::find($bill->vendor_id);
                if ($oldVendor) {
                    $oldVendor->decrement('balance', $bill->total);
                }
            }

            // Determine new status
            $newStatus = $requestedStatus ?: $oldStatus;

            $bill->update([
                'vendor_id' => $request->vendor_id,
                'bill_number' => $request->bill_number,
                'bill_date' => $request->bill_date,
                'due_date' => $request->due_date,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discount,
                'total' => $totalAmount,
                'due_amount' => max(0, $totalAmount - $bill->paid_amount),
                'status' => $newStatus,
                'notes' => $request->notes ?? null,
                'currency_code' => $request->input('currency_code', $bill->currency_code ?? 'SGD'),
                'exchange_rate' => $request->input('exchange_rate', $bill->exchange_rate ?? 1.000000),
            ]);

            // Update status based on payment (only for non-draft)
            if ($newStatus !== 'draft') {
                if ($bill->due_amount <= 0) {
                    $bill->update(['status' => 'paid']);
                } elseif ($bill->paid_amount > 0) {
                    $bill->update(['status' => 'partial']);
                }
            }

            // Delete old bill items and re-create
            BillItem::where('bill_id', $bill->id)->delete();

            foreach ($request->items as $it) {
                $lineSub = $it['quantity'] * $it['price'];
                $taxRate = !empty($it['tax_rate']) ? (float)$it['tax_rate'] : 0;
                $lineTax = ($lineSub * $taxRate) / 100;

                BillItem::create([
                    'bill_id' => $bill->id,
                    'item_id' => $it['item_id'] ?? null,
                    'name' => $it['item_name'],
                    'quantity' => $it['quantity'],
                    'price' => $it['price'],
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                    'total' => $lineSub + $lineTax,
                ]);
            }

            // Apply new vendor balance adjustment only if bill is now active
            $willBeActive = !in_array($bill->status, ['draft']);
            if ($willBeActive) {
                $vendor = Vendor::find($request->vendor_id);
                if ($vendor) {
                    $vendor->increment('balance', $totalAmount);
                }
            }

            DB::commit();
            $this->logActivity('updated', "Updated bill {$bill->bill_number}", 'Bill', $bill->id);
            return redirect()->route('bills.show', $bill->id)->with('success', 'Vendor bill updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error updating bill: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $bill = Bill::findOrFail($id);

        if ($bill->status === 'paid') {
            return redirect()->route('bills.index')->with('error', 'Cannot delete a paid bill.');
        }

        DB::beginTransaction();
        try {
            // Reverse vendor payable balance
            $vendor = Vendor::find($bill->vendor_id);
            if ($vendor) {
                $vendor->decrement('balance', $bill->due_amount);
            }

            // Line items are kept so "Undo" can put the bill back intact.
            $bill->delete();

            DB::commit();
            $this->logActivity('deleted', "Deleted bill {$bill->bill_number}", 'Bill', $bill->id);

            return redirect()->route('bills.index')
                ->with('success', 'Vendor bill deleted successfully.')
                ->with('undo_url', route('bills.restore', $bill->id))
                ->with('undo_label', 'Undo');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('bills.index')->with('error', 'Error deleting bill: ' . $e->getMessage());
        }
    }

    /**
     * Restore a soft-deleted bill (the "Undo" action on the delete toast),
     * re-applying the vendor payable balance that destroy() reversed.
     */
    public function restore($id)
    {
        $bill = Bill::withTrashed()->findOrFail($id);

        if (! $bill->trashed()) {
            return redirect()->route('bills.index')->with('info', 'That bill is already active.');
        }

        DB::transaction(function () use ($bill) {
            $vendor = Vendor::withTrashed()->find($bill->vendor_id);
            if ($vendor) {
                $vendor->increment('balance', $bill->due_amount);
            }

            $bill->restore();
        });

        $this->logActivity('restored', "Restored bill {$bill->bill_number}", 'Bill', $bill->id);

        return redirect()->route('bills.index')->with('success', "Bill {$bill->bill_number} restored.");
    }

    public function exportCsv()
    {
        $bills = Bill::with('vendor')->latest()->get();

        $headers = ['Bill #', 'Vendor', 'Date', 'Due Date', 'Subtotal', 'Tax', 'Discount', 'Total', 'Paid', 'Due', 'Status'];
        $rows = [];
        foreach ($bills as $b) {
            $rows[] = [
                $b->bill_number,
                $b->vendor->name ?? 'N/A',
                $b->bill_date,
                $b->due_date,
                number_format($b->subtotal, 2),
                number_format($b->tax_total, 2),
                number_format($b->discount_total, 2),
                number_format($b->total, 2),
                number_format($b->paid_amount, 2),
                number_format($b->due_amount, 2),
                $b->status,
            ];
        }

        return $this->buildCsvResponse('bills.csv', $headers, $rows);
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
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function recordPayment(Request $request, $id)
    {
        $bill = Bill::findOrFail($id);

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $bill->due_amount,
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $amount = (float) $request->amount;
            $bill->paid_amount += $amount;
            $bill->due_amount = max(0, $bill->total - $bill->paid_amount);

            if ($bill->due_amount <= 0) {
                $bill->status = 'paid';
            } else {
                $bill->status = 'partial';
            }
            $bill->save();

            // Deduct bank account balance
            $bank = BankAccount::findOrFail($request->bank_account_id);
            $bank->decrement('current_balance', $amount);

            // Record ledger transaction
            Transaction::create([
                'bank_account_id' => $bank->id,
                'vendor_id' => $bill->vendor_id,
                'bill_id' => $bill->id,
                'type' => 'expense',
                'amount' => $amount,
                'transaction_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number ?? ('PAY-' . $bill->bill_number),
                'description' => 'Payment for Bill ' . $bill->bill_number . ' to ' . ($bill->vendor->name ?? 'Vendor'),
            ]);

            // Deduct vendor payable balance
            if ($bill->vendor) {
                $bill->vendor->decrement('balance', $amount);
            }

            DB::commit();
            $this->logActivity('payment_recorded', "Recorded payment of {$request->amount} for bill {$bill->bill_number}", 'Bill', $bill->id);
            return back()->with('success', 'Payment recorded and bank account deducted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Payment recording failed: ' . $e->getMessage());
        }
    }
}
