<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Category;
use App\Models\Company;
use App\Models\ExpenseClaim;
use App\Models\Vendor;
use App\Services\ReceiptOcrService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class ReceiptOcrController extends Controller
{
    use LogsActivity;

    protected ReceiptOcrService $ocrService;

    public function __construct(ReceiptOcrService $ocrService)
    {
        $this->ocrService = $ocrService;
    }

    /**
     * Show the receipt scan page.
     */
    public function scan()
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';
        $categories = Category::where('type', 'expense')->get();
        $hasAi = ! empty($company->nvidia_api_key) || ! empty(config('services.nvidia.api_key'));

        return view('expense-claims.scan', compact('company', 'currencySymbol', 'categories', 'hasAi'));
    }

    /**
     * Handle receipt image upload and OCR extraction (AJAX).
     */
    public function upload(Request $request)
    {
        $request->validate([
            'receipt_image' => 'required|image|mimes:jpeg,jpg,png,webp|max:10240',
        ]);

        $file = $request->file('receipt_image');
        $path = $file->store('receipts', 'local');
        $fullPath = storage_path('app/'.$path);

        $extracted = $this->ocrService->extractFromImage($fullPath);

        return response()->json([
            'success' => true,
            'data' => $extracted,
            'image_url' => asset('storage/'.$path),
            'storage_path' => $path,
        ]);
    }

    /**
     * Create an ExpenseClaim or Bill from extracted receipt data.
     */
    public function createFromReceipt(Request $request)
    {
        $validated = $request->validate([
            'record_type' => 'required|in:expense_claim,bill',
            'merchant' => 'required|string|max:255',
            'receipt_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'gst_amount' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'notes' => 'nullable|string|max:1000',
            'receipt_path' => 'nullable|string',
        ]);

        if ($validated['record_type'] === 'expense_claim') {
            return $this->createExpenseClaim($validated, $request);
        }

        return $this->createBill($validated, $request);
    }

    protected function createExpenseClaim(array $data, Request $request)
    {
        $lastId = ExpenseClaim::withTrashed()->max('id') ?? 0;
        $claimNumber = 'EXP-'.date('Y').'-'.str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);

        $claim = ExpenseClaim::create([
            'claim_number' => $claimNumber,
            'user_id' => Auth::id(),
            'claim_date' => $data['receipt_date'],
            'title' => 'Receipt: '.$data['merchant'],
            'description' => $data['notes'] ?? 'Created from scanned receipt',
            'total_amount' => $data['amount'],
            'status' => 'submitted',
            'category_id' => $data['category_id'] ?? null,
            'receipt_path' => $data['receipt_path'] ?? null,
        ]);

        $this->logActivity('created', "Created expense claim {$claimNumber} from receipt scan ({$data['merchant']})", 'ExpenseClaim', $claim->id);

        return redirect()->route('expense_claims.show', $claim->id)
            ->with('success', "Expense claim {$claimNumber} created from receipt.");
    }

    protected function createBill(array $data, Request $request)
    {
        // Find or create vendor
        $vendor = Vendor::where('name', 'like', '%'.$data['merchant'].'%')->first();
        if (! $vendor) {
            $vendor = Vendor::create([
                'name' => $data['merchant'],
                'is_active' => true,
                'currency' => 'SGD',
                'balance' => 0.00,
            ]);
        }

        $gstAmount = (float) ($data['gst_amount'] ?? 0);
        $subtotal = (float) $data['amount'] - $gstAmount;

        $lastId = Bill::withTrashed()->max('id') ?? 0;
        $billNumber = 'BILL-'.date('Y').'-'.str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);

        $bill = Bill::create([
            'vendor_id' => $vendor->id,
            'bill_number' => $billNumber,
            'bill_date' => $data['receipt_date'],
            'due_date' => date('Y-m-d', strtotime($data['receipt_date'].' +30 days')),
            'subtotal' => max(0, $subtotal),
            'tax_total' => $gstAmount,
            'discount_total' => 0.00,
            'total' => $data['amount'],
            'paid_amount' => 0.00,
            'due_amount' => $data['amount'],
            'status' => 'received',
            'notes' => ($data['notes'] ?? '').($data['receipt_path'] ? "\nReceipt: ".$data['receipt_path'] : ''),
        ]);

        BillItem::create([
            'bill_id' => $bill->id,
            'name' => 'Receipt items from '.$data['merchant'],
            'quantity' => 1,
            'price' => max(0, $subtotal),
            'tax_rate' => $subtotal > 0 ? round(($gstAmount / $subtotal) * 100, 2) : 0,
            'tax_amount' => $gstAmount,
            'total' => $data['amount'],
        ]);

        $vendor->increment('balance', $data['amount']);

        $this->logActivity('created', "Created bill {$billNumber} from receipt scan ({$data['merchant']})", 'Bill', $bill->id);

        return redirect()->route('bills.show', $bill->id)
            ->with('success', "Bill {$billNumber} created from receipt.");
    }
}
