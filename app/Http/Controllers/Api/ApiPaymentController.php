<?php

namespace App\Http\Controllers\Api;

use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ApiPaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);

        $paginator = Transaction::where('type', 'income')
            ->with(['customer', 'invoice', 'bankAccount'])
            ->latest('transaction_date')
            ->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'transaction_date' => 'required|date',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'reference_number' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);

        $transaction = Transaction::create([
            'type' => 'income',
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'transaction_date' => $validated['transaction_date'],
            'bank_account_id' => $validated['bank_account_id'] ?? null,
            'reference_number' => $validated['reference_number'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        // Update invoice paid/due amounts
        $newPaid = (float) $invoice->paid_amount + (float) $validated['amount'];
        $newDue = (float) $invoice->total - $newPaid;
        $invoice->update([
            'paid_amount' => $newPaid,
            'due_amount' => max(0, $newDue),
            'status' => $newDue <= 0 ? 'paid' : 'partial',
        ]);

        return response()->json(['data' => $transaction->load('invoice')], 201);
    }
}
