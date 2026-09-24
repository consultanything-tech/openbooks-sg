<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tax;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OnboardingController extends Controller
{
    /**
     * Show the onboarding checklist.
     */
    public function index()
    {
        $company = Company::first() ?? new Company(['name' => '', 'currency_symbol' => 'S$']);

        $steps = [
            [
                'label' => 'Set up company profile',
                'description' => 'Add your business name, address, and UEN',
                'icon' => 'building',
                'link' => route('settings.index'),
                'done' => !empty($company->name) && $company->name !== 'OpenBooks SG',
            ],
            [
                'label' => 'Add your first customer',
                'description' => 'Create a customer record to start invoicing',
                'icon' => 'user-plus',
                'link' => route('customers.index'),
                'done' => Customer::exists(),
            ],
            [
                'label' => 'Create an invoice',
                'description' => 'Issue your first invoice with GST',
                'icon' => 'file-text',
                'link' => route('invoices.index'),
                'done' => Invoice::exists(),
            ],
            [
                'label' => 'Configure GST settings',
                'description' => 'Set up Singapore GST rate (9%)',
                'icon' => 'percent',
                'link' => route('settings.index'),
                'done' => Tax::exists(),
            ],
            [
                'label' => 'Set up PayNow QR',
                'description' => 'Add your PayNow UEN or phone for instant payments',
                'icon' => 'qr-code',
                'link' => route('settings.index'),
                'done' => !empty($company->paynow_id),
            ],
            [
                'label' => 'Record a payment',
                'description' => 'Log your first banking transaction',
                'icon' => 'arrow-left-right',
                'link' => route('banking.index'),
                'done' => Transaction::exists(),
            ],
        ];

        $completed = collect($steps)->where('done', true)->count();
        $total = count($steps);

        return view('onboarding.index', compact('company', 'steps', 'completed', 'total'));
    }

    /**
     * Dismiss the onboarding wizard.
     */
    public function dismiss(Request $request)
    {
        $request->session()->put('onboarding_dismissed', true);

        return redirect()->route('dashboard');
    }
}
