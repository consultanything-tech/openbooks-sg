<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CustomerPortalController extends Controller
{
    // --- Portal auth helper ---

    /**
     * Retrieve the authenticated portal customer from session.
     */
    private function getPortalCustomer(): ?Customer
    {
        $customerId = Session::get('portal_customer_id');

        if (! $customerId) {
            return null;
        }

        return Customer::where('id', $customerId)
            ->where('portal_enabled', true)
            ->first();
    }

    // --- Portal authentication ---

    /**
     * Show customer portal login page.
     */
    public function showLogin()
    {
        if ($this->getPortalCustomer()) {
            return redirect()->route('portal.dashboard');
        }

        return view('portal.login');
    }

    /**
     * Authenticate customer via email + portal password.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $customer = Customer::where('email', $request->email)
            ->where('portal_enabled', true)
            ->first();

        if (! $customer || ! Hash::check($request->password, $customer->portal_password)) {
            return back()->withErrors(['email' => 'Invalid credentials or portal access not enabled.'])->withInput();
        }

        Session::put('portal_customer_id', $customer->id);

        return redirect()->route('portal.dashboard');
    }

    /**
     * Log out of the customer portal.
     */
    public function logout()
    {
        Session::forget('portal_customer_id');

        return redirect()->route('portal.login');
    }

    // --- Portal pages ---

    /**
     * Portal dashboard with recent invoices, payments, and summary totals.
     */
    public function dashboard()
    {
        $customer = $this->getPortalCustomer();

        if (! $customer) {
            return redirect()->route('portal.login');
        }

        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';

        $recentInvoices = Invoice::where('customer_id', $customer->id)
            ->latest('invoice_date')
            ->limit(10)
            ->get();

        $recentPayments = Transaction::where('customer_id', $customer->id)
            ->where('type', 'income')
            ->latest('transaction_date')
            ->limit(10)
            ->get();

        $totalOutstanding = Invoice::where('customer_id', $customer->id)
            ->whereIn('status', ['draft', 'sent', 'partial', 'overdue'])
            ->sum('due_amount');

        $totalPaid = Invoice::where('customer_id', $customer->id)
            ->sum('paid_amount');

        return view('portal.dashboard', compact(
            'customer',
            'company',
            'currencySymbol',
            'recentInvoices',
            'recentPayments',
            'totalOutstanding',
            'totalPaid'
        ));
    }

    /**
     * List all invoices for the portal customer with optional status filter.
     */
    public function invoices(Request $request)
    {
        $customer = $this->getPortalCustomer();

        if (! $customer) {
            return redirect()->route('portal.login');
        }

        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';

        $query = Invoice::where('customer_id', $customer->id)->latest('invoice_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->paginate(15);

        return view('portal.invoices', compact('customer', 'invoices', 'company', 'currencySymbol'));
    }

    /**
     * Show a single invoice detail (must belong to the portal customer).
     */
    public function showInvoice($id)
    {
        $customer = $this->getPortalCustomer();

        if (! $customer) {
            return redirect()->route('portal.login');
        }

        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';

        $invoice = Invoice::where('id', $id)
            ->where('customer_id', $customer->id)
            ->with('items')
            ->firstOrFail();

        return view('portal.invoice-show', compact('customer', 'invoice', 'company', 'currencySymbol'));
    }

    /**
     * Download a customer statement as CSV (all invoices with running balance).
     */
    public function statement()
    {
        $customer = $this->getPortalCustomer();

        if (! $customer) {
            return redirect()->route('portal.login');
        }

        $invoices = Invoice::where('customer_id', $customer->id)
            ->orderBy('invoice_date', 'asc')
            ->get();

        $filename = 'statement-'.Str::slug($customer->name).'-'.date('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($invoices) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Date', 'Invoice #', 'Status', 'Total', 'Paid', 'Due', 'Running Balance']);

            $runningBalance = 0;

            foreach ($invoices as $invoice) {
                $runningBalance += (float) $invoice->due_amount;

                fputcsv($handle, [
                    $invoice->invoice_date->format('Y-m-d'),
                    $invoice->invoice_number,
                    $invoice->status,
                    number_format((float) $invoice->total, 2),
                    number_format((float) $invoice->paid_amount, 2),
                    number_format((float) $invoice->due_amount, 2),
                    number_format($runningBalance, 2),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // --- Admin method ---

    /**
     * Enable portal access for a customer (called from admin side).
     */
    public function enablePortal(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $customer = Customer::findOrFail($id);

        $customer->portal_password = Hash::make($request->password);
        $customer->portal_enabled = true;
        $customer->portal_token = Str::random(64);
        $customer->save();

        return redirect()->back()->with('success', 'Customer portal access enabled successfully.');
    }
}
