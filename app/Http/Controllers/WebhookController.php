<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class WebhookController extends Controller
{
    use \App\Traits\LogsActivity;

    /**
     * List all webhooks (admin only).
     */
    public function index()
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $webhooks = Webhook::with('user')->latest()->get();

        $events = [
            'invoice.created' => 'Invoice Created',
            'invoice.updated' => 'Invoice Updated',
            'invoice.paid' => 'Invoice Paid',
            'payment.received' => 'Payment Received',
            'bill.created' => 'Bill Created',
            'bill.paid' => 'Bill Paid',
            'customer.created' => 'Customer Created',
            'quote.created' => 'Quote Created',
            'quote.accepted' => 'Quote Accepted',
        ];

        return view('settings.webhooks', compact('company', 'webhooks', 'events'));
    }

    /**
     * Create a new webhook.
     */
    public function store(Request $request)
    {
        $request->validate([
            'url' => 'required|url|max:2048',
            'event' => 'required|string|max:255',
            'secret' => 'nullable|string|max:255',
        ]);

        Webhook::create([
            'user_id' => Auth::id(),
            'url' => $request->input('url'),
            'event' => $request->input('event'),
            'secret' => $request->input('secret'),
            'is_active' => true,
        ]);

        return redirect()->route('webhooks.index')->with('success', 'Webhook created successfully.');
    }

    /**
     * Toggle webhook active/inactive.
     */
    public function update(Request $request, $id)
    {
        $webhook = Webhook::findOrFail($id);
        $webhook->update(['is_active' => !$webhook->is_active]);

        $status = $webhook->is_active ? 'activated' : 'deactivated';

        return redirect()->route('webhooks.index')->with('success', "Webhook {$status}.");
    }

    /**
     * Delete a webhook.
     */
    public function destroy($id)
    {
        $webhook = Webhook::findOrFail($id);
        $webhook->delete();

        return redirect()->route('webhooks.index')->with('success', 'Webhook deleted.');
    }
}
