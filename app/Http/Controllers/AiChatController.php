<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Quote;
use App\Models\Tax;
use App\Models\Vendor;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiChatController extends Controller
{
    use LogsActivity;

    public function chat(Request $request)
    {
        $userMessage = trim($request->input('message', ''));
        $history = $request->input('history', []);

        if (empty($userMessage)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a message or voice prompt.',
            ], 400);
        }

        // Fetch live accounting system snapshot to give context to the LLM
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$', 'name' => 'OpenBooks Enterprise']);
        $currencySymbol = $company->currency_symbol ?? 'S$';

        $apiKey = ! empty($company->nvidia_api_key) ? trim($company->nvidia_api_key) : trim(config('services.nvidia.api_key', ''));
        $model = ! empty($company->nvidia_model) ? trim($company->nvidia_model) : config('services.nvidia.model', 'meta/llama-3.2-11b-vision-instruct');

        if (empty($apiKey)) {
            $settingsUrl = route('settings.index');

            return response()->json([
                'success' => true,
                'reply' => "**NVIDIA API Key Required**\n\nNo default key is configured. Please enter your personal free NVIDIA API Key in Settings to activate the AI Copilot and Voice Engine.\n\n**How to get a free key (2 minutes):**\n1. Go to https://build.nvidia.com and create a free account (or sign in).\n2. Open the **API Keys** page from your avatar menu (build.nvidia.com/settings/api-keys).\n3. Click **Generate API Key** and copy it — keys start with `nvapi-`.\n4. Paste it in Settings → AI Assistant and save. NVIDIA includes free inference credits with every account.\n\n[Go to Settings to enter your NVIDIA API Key]({$settingsUrl})",
                'action' => 'none',
                'action_data' => null,
            ]);
        }

        $customers = Customer::where('is_active', true)->select('id', 'name', 'balance')->get();
        $vendors = Vendor::where('is_active', true)->select('id', 'name', 'balance')->get();
        $items = Item::where('is_active', true)->select('id', 'name', 'sku', 'sale_price', 'purchase_price')->get();
        $taxes = Tax::where('is_active', true)->select('id', 'name', 'rate')->get();
        $bankAccounts = BankAccount::select('id', 'name', 'account_number', 'current_balance')->get();

        $systemPrompt = <<<EOT
You are OpenBooks AI, an intelligent, proactive executive accounting assistant built into the OpenBooks SG platform.
Your job is to understand natural language and voice requests from the user, determine the appropriate accounting action, and output a structured JSON response.

CRITICAL FORMATTING RULES:
1. STRICTLY NO EMOJIS: Do not use any emojis under any circumstances in your responses. Keep all responses clean, professional, and purely text-based.
2. Voice-Friendly: Replies will be spoken aloud, so write clear and concise sentences without emoji icons.

CURRENT SYSTEM CONTEXT:
- Currency Symbol: {$currencySymbol}
- Active Customers: {$customers->toJson()}
- Active Vendors: {$vendors->toJson()}
- Active Products & Services: {$items->toJson()}
- Active Taxes: {$taxes->toJson()}
- Bank Accounts: {$bankAccounts->toJson()}
- Current Date: %CURRENT_DATE%

SUPPORTED ACTIONS:
1. create_invoice: Create a customer sales invoice / client bill (a binding receivable).
   Use when the user asks to "bill someone", "create invoice for client", or "bill of rs 10000 for website designing services to mr rampal".
   Do NOT use for quotes, quotations, estimates, proposals or bids — those are create_quote.
   Parameters:
     - party_name: string (e.g. "Mr. Rampal")
     - item_name: string (e.g. "Website Designing Services")
     - amount: number (e.g. 10000)
     - quantity: number (default 1)
     - tax_rate: number (optional, percentage e.g. 18 or 0)
     - notes: string (optional)

2. create_quote: Create a sales quote / quotation / estimate / proposal for a customer.
   Use when the user says "quote", "quotation", "estimate", "proposal", "bid", or "pricing for approval"
   (e.g. "create a quote for Marina Bay for 5000", "send Orchard an estimate for the redesign").
   A quote is a non-binding offer: it does NOT post receivables and can later be converted to an invoice.
   NEVER use create_invoice when the user asks for a quote/estimate/proposal.
   Parameters:
     - party_name: string (e.g. "Marina Bay Trading")
     - item_name: string (e.g. "Website Redesign")
     - amount: number (e.g. 5000)
     - quantity: number (default 1)
     - tax_rate: number (optional, percentage e.g. 9 or 0)
     - notes: string (optional)

3. create_bill: Create a vendor purchase bill / expense bill.
   Use ONLY when the user specifically mentions a vendor purchase or a bill received from a supplier (e.g. "create vendor bill from AWS for 5000", "bill from supplier", "record our electricity bill").
   Parameters:
     - party_name: string (e.g. "AWS" or vendor name)
     - item_name: string (e.g. "Hosting Services")
     - amount: number
     - quantity: number (default 1)
     - tax_rate: number (optional)

WORD-SENSE RULE FOR "BILL" (OVERRIDES ALL OTHER GUIDANCE):
- "bill" as a VERB directed AT a party ("bill Marina Bay $1000", "bill to customer X for $1000", "bill the client for design work") means SEND A SALES INVOICE => create_invoice.
- "bill" as a NOUN received FROM a party ("bill from AWS", "vendor bill", "electricity bill", "record this bill we owe") means A PURCHASE => create_bill.
- Tie-breaker: if the named party appears in Active Customers => create_invoice. If it appears in Active Vendors => create_bill.

4. add_customer: Add a new client / customer.
   Parameters:
     - name: string
     - email: string (optional)
     - phone: string (optional)

5. add_vendor: Add a new vendor / supplier.
   Parameters:
     - name: string
     - email: string (optional)
     - phone: string (optional)

6. check_party_pending: Check unpaid/pending balances (receivables from customers and payables to vendors).
   Parameters:
     - party_name: string (optional, or null for all parties)
     - party_type: "customer" | "vendor" | "all"

7. add_item: Add a product or service to catalog.
   Parameters:
     - name: string
     - sale_price: number
     - purchase_price: number (optional)
     - unit: string (optional, e.g. "pcs", "service", "month")

8. check_taxes: List available taxes.
   Parameters: {}

9. add_tax: Create a new tax rate.
   Parameters:
     - name: string (e.g. "GST 12%")
     - rate: number (e.g. 12)

10. check_banking: Check bank balances and transactions.
   Parameters: {}

11. check_reports: Check Profit & Loss or financial performance.
    Parameters:
      - report_type: "profit_loss" | "income_expense" | "tax_summary"

12. navigate: Direct the user to a specific sidebar section (invoices, quotes, bills, customers, vendors, items, banking, reports, settings).
    Parameters:
      - destination: string

13. none: General conversation, greeting, explanation, OR asking follow-up questions when required details are missing.
    Also use "none" whenever the requested operation is NOT listed in SUPPORTED ACTIONS — never substitute a similar-but-different action.

CRITICAL INSTRUCTIONS FOR MISSING DETAILS (VOICE & TEXT):
- If the user asks to create a bill or invoice, but did NOT specify:
  a) The party name (who the bill is for), OR
  b) The item/service description, OR
  c) The amount
  Then set "action" to "none", and in "message", politely ask the user to provide the missing details (e.g., "Who should I issue the bill/invoice to, what is the item or service description, and what is the amount?").
- If the user provides the party, item, and amount (like "create a bill of rs 10000 for website designing services to mr rampal"), immediately trigger the corresponding action!

FEW-SHOT EXAMPLES OF LAYMAN REQUESTS (action prediction):
- "Bill Marina Bay Trading $1000 for design work" => create_invoice (bill used as verb toward a customer)
- "Bill to customer for $1000" => create_invoice
- "Send Orchard Retail an invoice for consulting, 2500" => create_invoice
- "Create a quote for Marina Bay for 5000 for the website redesign" => create_quote
- "Give Orchard Retail an estimate of 2500 for consulting, they will approve next week" => create_quote
- "Prepare a quotation / proposal / bid for the client" => create_quote
- A quote or estimate must NEVER be recorded as create_invoice — invoices post receivables, quotes do not.
- "I got a bill from AWS for 500, record it" => create_bill (bill received from a vendor)
- "Our electricity bill this month is 200, add it" => create_bill
- "How much does Marina Bay owe me?" => check_party_pending
- "Am I making money this month?" => check_reports (profit_loss)
- "Take me to my bills" => navigate (bills)
- "What can you do?" => none

OUTPUT FORMAT:
Always reply ONLY with a valid JSON object. Do not wrap in markdown or anything else if possible, or wrap in ```json ... ```:
{
  "message": "Human-friendly reply that will also be spoken by voice speech synthesis",
  "action": "action_name_or_none",
  "confidence": 0.0-1.0 (how sure you are about the predicted action; below 0.5 prefer action "none" and ask a clarifying question),
  "parameters": { ... }
}
EOT;

        $systemPrompt = str_replace('%CURRENT_DATE%', date('Y-m-d'), $systemPrompt);

        // Build messages array
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Include last 6 history messages for conversation context
        if (is_array($history)) {
            $recent = array_slice($history, -6);
            foreach ($recent as $msg) {
                if (isset($msg['role']) && isset($msg['content']) && in_array($msg['role'], ['user', 'assistant'])) {
                    $messages[] = [
                        'role' => $msg['role'],
                        'content' => (string) $msg['content'],
                    ];
                }
            }
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        // Call NVIDIA NIM API
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post(config('services.nvidia.api_url', 'https://integrate.api.nvidia.com/v1/chat/completions'), [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.1,
                'max_tokens' => 600,
            ]);

            if (! $response->successful()) {
                Log::error('NVIDIA API Error: '.$response->body());

                return response()->json([
                    'success' => false,
                    'message' => 'AI Service error ('.$response->status().'). '.$response->body(),
                ], 502);
            }

            $jsonResp = $response->json();
            $rawContent = $jsonResp['choices'][0]['message']['content'] ?? '';

            // Clean up possible markdown code blocks
            $cleanJson = trim($rawContent);
            if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $cleanJson, $matches)) {
                $cleanJson = $matches[1];
            } elseif (preg_match('/(\{.*\})/s', $cleanJson, $matches)) {
                $cleanJson = $matches[1];
            }

            $parsed = json_decode($cleanJson, true);

            if (! is_array($parsed) || ! isset($parsed['message'])) {
                // Fallback if parsing failed
                return response()->json([
                    'success' => true,
                    'reply' => $rawContent ?: 'I processed your request, but could not determine an action.',
                    'action' => 'none',
                    'action_data' => null,
                ]);
            }

            $action = $parsed['action'] ?? 'none';
            $params = $parsed['parameters'] ?? [];
            $replyMessage = $parsed['message'] ?? 'Done!';

            // Quote-sense guard: a request mentioning quotes/estimates/proposals must
            // never be written as an invoice or bill, even if the model mispredicted.
            if (in_array($action, ['create_bill', 'create_invoice'], true)
                && preg_match('/\b(quote|quotation|quotations|estimate|estimation|proposal|proposals|bid)\b/i', $userMessage) === 1) {
                $action = 'create_quote';
                $replyMessage .= "\n\n(Prepared as a quote — no receivable was posted. You can convert it to an invoice once approved.)";
            }

            // Word-sense guard: "bill <party> for $X" / "bill to <party>" means invoice
            // that customer; "bill from <party>" means record a purchase. When the named
            // party exists as both customer and vendor, the message's own wording
            // ("to X" vs "from X") breaks the tie.
            if (in_array($action, ['create_bill', 'create_invoice'], true)) {
                $party = trim((string) ($params['party_name'] ?? ''));
                if ($party !== '') {
                    $norm = mb_strtolower($party);
                    $match = fn ($name) => mb_strtolower($name) === $norm
                        || str_contains($norm, mb_strtolower($name))
                        || str_contains(mb_strtolower($name), $norm);
                    $isCustomer = $customers->contains(fn ($c) => $match($c->name));
                    $isVendor = $vendors->contains(fn ($v) => $match($v->name));

                    $msg = mb_strtolower($userMessage);
                    $fromSense = str_contains($msg, 'from '.$norm)
                        || str_contains($msg, 'received from')
                        || str_contains($msg, 'we owe')
                        || str_contains($msg, 'our bill')
                        || str_contains($msg, 'supplier')
                        || str_contains($msg, 'vendor bill');
                    $toSense = str_contains($msg, 'to '.$norm)
                        || str_contains($msg, 'bill to')
                        || str_contains($msg, 'invoice to')
                        || preg_match('/\bbills?\s+'.preg_quote($norm, '/').'/', $msg) === 1;
                    $salesSense = $toSense || str_contains($msg, 'invoice');

                    if ($action === 'create_bill' && $isCustomer && (! $isVendor || $salesSense || ! $fromSense)) {
                        $action = 'create_invoice';
                        $replyMessage .= "\n\n(Interpreted as invoicing the customer, since {$party} is one of your customers.)";
                    } elseif ($action === 'create_invoice' && $isVendor && (! $isCustomer || ($fromSense && ! $salesSense))) {
                        $action = 'create_bill';
                        $replyMessage .= "\n\n(Interpreted as a vendor purchase bill, since {$party} is one of your vendors.)";
                    }
                }
            }

            // Confidence gate: never let a low-confidence prediction perform
            // autonomous writes — ask the user to confirm/clarify instead.
            $writeActions = ['create_invoice', 'create_quote', 'create_bill', 'add_customer', 'add_vendor', 'add_item', 'add_tax'];
            $confidence = (float) ($parsed['confidence'] ?? 1.0);
            if (in_array($action, $writeActions, true) && $confidence < 0.5) {
                $action = 'none';
                $replyMessage = 'I want to make sure I get this right before touching your books. '
                    .'Could you confirm what you need — for example "create an invoice for <customer> for <amount>" '
                    .'or "record a bill from <vendor> for <amount>"?';
            }

            // Execute action if present
            $actionResult = $this->executeAction($action, $params, $currencySymbol);

            // If action produced additional text or links, merge nicely
            if (! empty($actionResult['reply_append'])) {
                $replyMessage .= "\n\n".$actionResult['reply_append'];
            }

            // Strictly strip any emojis from the output
            $replyMessage = $this->stripEmojis($replyMessage);

            return response()->json([
                'success' => true,
                'reply' => $replyMessage,
                'action' => $action,
                'action_data' => $actionResult['data'] ?? null,
                'action_card' => $actionResult['card'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('AI Chatbot Exception: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while contacting the AI assistant: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Autonomous Action Execution Engine
     */
    protected function executeAction(string $action, array $params, string $currencySymbol): array
    {
        $res = [
            'data' => null,
            'card' => null,
            'reply_append' => '',
        ];

        try {
            switch ($action) {
                case 'create_invoice':
                    $partyName = trim($params['party_name'] ?? '');
                    $itemName = trim($params['item_name'] ?? 'Consulting & Professional Services');
                    $amount = (float) ($params['amount'] ?? 0);
                    $qty = max(1, (float) ($params['quantity'] ?? 1));
                    $taxRate = (float) ($params['tax_rate'] ?? 0);

                    if (empty($partyName) || $amount <= 0) {
                        return [
                            'reply_append' => '*Please provide the client name and billing amount so I can generate the invoice.*',
                        ];
                    }

                    // Find or create customer
                    $customer = Customer::where('name', 'like', '%'.$partyName.'%')->first();
                    if (! $customer) {
                        $customer = Customer::create([
                            'name' => $partyName,
                            'is_active' => true,
                            'currency' => 'SGD',
                            'balance' => 0.00,
                        ]);
                    }

                    // Calculate totals
                    $subtotal = $qty * $amount;
                    $lineTax = ($subtotal * $taxRate) / 100;
                    $grandTotal = $subtotal + $lineTax;

                    // Next invoice number
                    $lastId = Invoice::withTrashed()->max('id') ?? 0;
                    $invoiceNumber = 'INV-'.date('Y').'-'.str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);

                    $invoice = Invoice::create([
                        'invoice_number' => $invoiceNumber,
                        'customer_id' => $customer->id,
                        'invoice_date' => date('Y-m-d'),
                        'due_date' => date('Y-m-d', strtotime('+30 days')),
                        'subtotal' => $subtotal,
                        'tax_total' => $lineTax,
                        'discount_total' => 0.00,
                        'total' => $grandTotal,
                        'paid_amount' => 0.00,
                        'due_amount' => $grandTotal,
                        'status' => 'sent',
                        'notes' => $params['notes'] ?? 'Generated via OpenBooks AI Assistant',
                        'terms' => 'Payment due within 30 days.',
                        'public_token' => Str::random(40),
                    ]);

                    $invoice->items()->create([
                        'name' => $itemName,
                        'quantity' => $qty,
                        'price' => $amount,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $lineTax,
                        'total' => $grandTotal,
                    ]);

                    $customer->increment('balance', $grandTotal);

                    $this->logActivity('created', "Created invoice {$invoiceNumber} for {$customer->name} via AI Assistant", 'Invoice', $invoice->id, ['source' => 'ai_assistant', 'total' => $grandTotal]);

                    $invoiceUrl = route('invoices.show', $invoice->id);
                    $printUrl = route('invoices.print', $invoice->id);

                    $res['data'] = [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoiceNumber,
                        'total' => $grandTotal,
                        'url' => $invoiceUrl,
                    ];

                    $res['card'] = [
                        'type' => 'invoice',
                        'title' => 'Invoice '.$invoiceNumber.' Created',
                        'party' => $customer->name,
                        'amount' => $currencySymbol.number_format($grandTotal, 2),
                        'item' => $itemName,
                        'status' => 'Issued / Due',
                        'url' => $invoiceUrl,
                        'print_url' => $printUrl,
                    ];

                    $res['reply_append'] = "**Invoice Link**: [View Invoice #{$invoiceNumber}]({$invoiceUrl}) | [Print / PDF]({$printUrl})";
                    break;

                case 'create_quote':
                    $partyName = trim($params['party_name'] ?? '');
                    $itemName = trim($params['item_name'] ?? 'Consulting & Professional Services');
                    $amount = (float) ($params['amount'] ?? 0);
                    $qty = max(1, (float) ($params['quantity'] ?? 1));
                    $taxRate = (float) ($params['tax_rate'] ?? 0);

                    if (empty($partyName) || $amount <= 0) {
                        return [
                            'reply_append' => '*Please provide the client name and the quoted amount so I can prepare the quote.*',
                        ];
                    }

                    // Find or create customer
                    $customer = Customer::where('name', 'like', '%'.$partyName.'%')->first();
                    if (! $customer) {
                        $customer = Customer::create([
                            'name' => $partyName,
                            'is_active' => true,
                            'currency' => 'SGD',
                            'balance' => 0.00,
                        ]);
                    }

                    // Calculate totals
                    $subtotal = $qty * $amount;
                    $lineTax = ($subtotal * $taxRate) / 100;
                    $grandTotal = $subtotal + $lineTax;

                    // Next quote number (same convention as QuoteController)
                    $lastQuoteId = Quote::withTrashed()->max('id') ?? 0;
                    $quoteNumber = 'QUO-'.date('Y').'-'.str_pad((string) ($lastQuoteId + 1), 4, '0', STR_PAD_LEFT);

                    // Quotes are non-binding: status sent, no receivable posted.
                    $quote = Quote::create([
                        'quote_number' => $quoteNumber,
                        'customer_id' => $customer->id,
                        'quote_date' => date('Y-m-d'),
                        'expiry_date' => date('Y-m-d', strtotime('+30 days')),
                        'subtotal' => $subtotal,
                        'tax_total' => $lineTax,
                        'discount_total' => 0.00,
                        'total' => $grandTotal,
                        'status' => 'sent',
                        'notes' => $params['notes'] ?? 'Generated via OpenBooks AI Assistant',
                        'terms' => 'This quote is valid for 30 days.',
                        'public_token' => Str::random(40),
                    ]);

                    $quote->items()->create([
                        'name' => $itemName,
                        'quantity' => $qty,
                        'price' => $amount,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $lineTax,
                        'total' => $grandTotal,
                    ]);

                    $this->logActivity('created', "Created quote {$quoteNumber} for {$customer->name} via AI Assistant", 'Quote', $quote->id, ['source' => 'ai_assistant', 'total' => $grandTotal]);

                    $quoteUrl = route('quotes.show', $quote->id);
                    $printUrl = route('quotes.print', $quote->id);

                    $res['data'] = [
                        'quote_id' => $quote->id,
                        'quote_number' => $quoteNumber,
                        'total' => $grandTotal,
                        'url' => $quoteUrl,
                    ];

                    $res['card'] = [
                        'type' => 'quote',
                        'title' => 'Quote '.$quoteNumber.' Created',
                        'party' => $customer->name,
                        'amount' => $currencySymbol.number_format($grandTotal, 2),
                        'item' => $itemName,
                        'status' => 'Awaiting Approval',
                        'url' => $quoteUrl,
                        'print_url' => $printUrl,
                    ];

                    $res['reply_append'] = "**Quote Link**: [View Quote #{$quoteNumber}]({$quoteUrl}) | [Print / PDF]({$printUrl})";
                    break;

                case 'create_bill':
                    $partyName = trim($params['party_name'] ?? '');
                    $itemName = trim($params['item_name'] ?? 'Vendor Operating Expense');
                    $amount = (float) ($params['amount'] ?? 0);
                    $qty = max(1, (float) ($params['quantity'] ?? 1));
                    $taxRate = (float) ($params['tax_rate'] ?? 0);

                    if (empty($partyName) || $amount <= 0) {
                        return [
                            'reply_append' => '*Please provide the vendor name and bill amount so I can record the bill.*',
                        ];
                    }

                    // Find or create vendor
                    $vendor = Vendor::where('name', 'like', '%'.$partyName.'%')->first();
                    if (! $vendor) {
                        $vendor = Vendor::create([
                            'name' => $partyName,
                            'is_active' => true,
                            'currency' => 'SGD',
                            'balance' => 0.00,
                        ]);
                    }

                    $subtotal = $qty * $amount;
                    $lineTax = ($subtotal * $taxRate) / 100;
                    $grandTotal = $subtotal + $lineTax;

                    $lastId = Bill::withTrashed()->max('id') ?? 0;
                    $billNumber = 'BILL-'.date('Y').'-'.str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);

                    $bill = Bill::create([
                        'vendor_id' => $vendor->id,
                        'bill_number' => $billNumber,
                        'bill_date' => date('Y-m-d'),
                        'due_date' => date('Y-m-d', strtotime('+30 days')),
                        'subtotal' => $subtotal,
                        'tax_total' => $lineTax,
                        'discount_total' => 0.00,
                        'total' => $grandTotal,
                        'paid_amount' => 0.00,
                        'due_amount' => $grandTotal,
                        'status' => 'received',
                        'notes' => $params['notes'] ?? 'Generated via OpenBooks AI Assistant',
                    ]);

                    BillItem::create([
                        'bill_id' => $bill->id,
                        'name' => $itemName,
                        'quantity' => $qty,
                        'price' => $amount,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $lineTax,
                        'total' => $grandTotal,
                    ]);

                    $vendor->increment('balance', $grandTotal);

                    $this->logActivity('created', "Created bill {$billNumber} from {$vendor->name} via AI Assistant", 'Bill', $bill->id, ['source' => 'ai_assistant', 'total' => $grandTotal]);

                    $billUrl = route('bills.show', $bill->id);

                    $res['data'] = [
                        'bill_id' => $bill->id,
                        'bill_number' => $billNumber,
                        'total' => $grandTotal,
                        'url' => $billUrl,
                    ];

                    $res['card'] = [
                        'type' => 'bill',
                        'title' => 'Vendor Bill '.$billNumber.' Created',
                        'party' => $vendor->name,
                        'amount' => $currencySymbol.number_format($grandTotal, 2),
                        'item' => $itemName,
                        'status' => 'Payable Pending',
                        'url' => $billUrl,
                    ];

                    $res['reply_append'] = "**Vendor Bill Link**: [View Bill #{$billNumber}]({$billUrl})";
                    break;

                case 'add_customer':
                    $name = trim($params['name'] ?? '');
                    if (empty($name)) {
                        return ['reply_append' => '*Please specify customer name.*'];
                    }
                    $c = Customer::create([
                        'name' => $name,
                        'email' => $params['email'] ?? null,
                        'phone' => $params['phone'] ?? null,
                        'currency' => 'SGD',
                        'is_active' => true,
                        'balance' => 0.00,
                    ]);
                    $url = route('customers.show', $c->id);
                    $this->logActivity('created', "Created customer {$c->name} via AI Assistant", 'Customer', $c->id, ['source' => 'ai_assistant']);
                    $res['card'] = [
                        'type' => 'customer',
                        'title' => 'Customer Added: '.$c->name,
                        'party' => $c->name,
                        'amount' => 'Active',
                        'url' => $url,
                    ];
                    $res['reply_append'] = "[View Customer Profile]({$url})";
                    break;

                case 'add_vendor':
                    $name = trim($params['name'] ?? '');
                    if (empty($name)) {
                        return ['reply_append' => '*Please specify vendor name.*'];
                    }
                    $v = Vendor::create([
                        'name' => $name,
                        'email' => $params['email'] ?? null,
                        'phone' => $params['phone'] ?? null,
                        'currency' => 'SGD',
                        'is_active' => true,
                        'balance' => 0.00,
                    ]);
                    $url = route('vendors.show', $v->id);
                    $this->logActivity('created', "Created vendor {$v->name} via AI Assistant", 'Vendor', $v->id, ['source' => 'ai_assistant']);
                    $res['card'] = [
                        'type' => 'vendor',
                        'title' => 'Vendor Added: '.$v->name,
                        'party' => $v->name,
                        'amount' => 'Active',
                        'url' => $url,
                    ];
                    $res['reply_append'] = "[View Vendor Profile]({$url})";
                    break;

                case 'check_party_pending':
                    $partyName = trim($params['party_name'] ?? '');
                    $partyType = $params['party_type'] ?? 'all';

                    $output = [];
                    $totalReceivables = Customer::sum('balance');
                    $totalPayables = Vendor::sum('balance');

                    if (! empty($partyName)) {
                        $cust = Customer::where('name', 'like', '%'.$partyName.'%')->first();
                        $vend = Vendor::where('name', 'like', '%'.$partyName.'%')->first();
                        if ($cust) {
                            $output[] = "**Customer**: [{$cust->name}](".route('customers.show', $cust->id).") — Pending Receivable: **{$currencySymbol}".number_format((float) $cust->balance, 2).'**';
                        }
                        if ($vend) {
                            $output[] = "**Vendor**: [{$vend->name}](".route('vendors.show', $vend->id).") — Outstanding Payable: **{$currencySymbol}".number_format((float) $vend->balance, 2).'**';
                        }
                        if (! $cust && ! $vend) {
                            $output[] = "No party found matching \"{$partyName}\".";
                        }
                    } else {
                        $output[] = "**Total Customer Receivables**: **{$currencySymbol}".number_format($totalReceivables, 2).'**';
                        $output[] = "**Total Vendor Payables**: **{$currencySymbol}".number_format($totalPayables, 2).'**';

                        $dueCustomers = Customer::where('balance', '>', 0)->orderByDesc('balance')->take(5)->get();
                        if ($dueCustomers->count() > 0) {
                            $output[] = "\n*Top Pending Customers*:";
                            foreach ($dueCustomers as $dc) {
                                $output[] = "• [{$dc->name}](".route('customers.show', $dc->id)."): {$currencySymbol}".number_format((float) $dc->balance, 2);
                            }
                        }

                        $dueVendors = Vendor::where('balance', '>', 0)->orderByDesc('balance')->take(5)->get();
                        if ($dueVendors->count() > 0) {
                            $output[] = "\n*Top Pending Vendors*:";
                            foreach ($dueVendors as $dv) {
                                $output[] = "• [{$dv->name}](".route('vendors.show', $dv->id)."): {$currencySymbol}".number_format((float) $dv->balance, 2);
                            }
                        }
                    }

                    $res['reply_append'] = implode("\n", $output);
                    break;

                case 'add_item':
                    $name = trim($params['name'] ?? '');
                    $price = (float) ($params['sale_price'] ?? $params['price'] ?? 0);
                    $cost = (float) ($params['purchase_price'] ?? 0);
                    $unit = trim($params['unit'] ?? 'pcs');

                    if (empty($name)) {
                        return ['reply_append' => '*Please provide item name and price.*'];
                    }

                    $item = Item::create([
                        'name' => $name,
                        'sale_price' => $price,
                        'purchase_price' => $cost,
                        'unit' => $unit,
                        'is_active' => true,
                    ]);

                    $this->logActivity('created', "Created item {$item->name} via AI Assistant", 'Item', $item->id, ['source' => 'ai_assistant']);

                    $res['reply_append'] = "**Item Added**: {$item->name} (Price: {$currencySymbol}".number_format($price, 2).') | [View Catalog]('.route('items.index').')';
                    break;

                case 'check_taxes':
                    $taxes = Tax::where('is_active', true)->get();
                    $taxList = [];
                    foreach ($taxes as $t) {
                        $taxList[] = "• **{$t->name}**: {$t->rate}%";
                    }
                    $res['reply_append'] = "**Configured Tax Rates**:\n".implode("\n", $taxList)."\n[Manage Taxes & Settings](".route('settings.index').')';
                    break;

                case 'add_tax':
                    $name = trim($params['name'] ?? '');
                    $rate = (float) ($params['rate'] ?? 0);
                    if (empty($name)) {
                        return ['reply_append' => '*Please provide tax name and rate.*'];
                    }
                    $tax = Tax::create([
                        'name' => $name,
                        'rate' => $rate,
                        'is_active' => true,
                    ]);
                    $this->logActivity('created', "Created tax rate {$tax->name} ({$tax->rate}%) via AI Assistant", 'Tax', $tax->id, ['source' => 'ai_assistant']);
                    $res['reply_append'] = "**Tax Created**: {$tax->name} ({$tax->rate}%) | [View Settings](".route('settings.index').')';
                    break;

                case 'check_banking':
                    $accounts = BankAccount::all();
                    $totalCash = $accounts->sum('current_balance');
                    $accList = [];
                    foreach ($accounts as $a) {
                        $accList[] = "• **{$a->name}** (A/C: ".($a->account_number ?: 'Cash')."): **{$currencySymbol}".number_format((float) $a->current_balance, 2).'**';
                    }
                    $res['reply_append'] = "**Bank & Cash Balances** (Total: **{$currencySymbol}".number_format($totalCash, 2)."**):\n".implode("\n", $accList)."\n[Open Banking Ledger](".route('banking.index').') | [New Transfer]('.route('banking.transfer').')';
                    break;

                case 'check_reports':
                    $invoicedSales = (float) Invoice::where('status', '!=', 'cancelled')->sum('total');
                    $collectedSales = (float) Invoice::sum('paid_amount');
                    $billsExpense = (float) Bill::sum('total');
                    $netProfit = $invoicedSales - $billsExpense;

                    $res['reply_append'] = "**Profit & Loss Summary**:\n"
                        ."• **Gross Sales Invoiced**: {$currencySymbol}".number_format($invoicedSales, 2)."\n"
                        ."• **Cash Collected**: {$currencySymbol}".number_format($collectedSales, 2)."\n"
                        ."• **Total Operating Expenses & Bills**: {$currencySymbol}".number_format($billsExpense, 2)."\n"
                        ."• **Net Operating Profit**: **{$currencySymbol}".number_format($netProfit, 2)."**\n"
                        .'[Open Detailed Profit & Loss Report]('.route('reports.profit_loss').')';
                    break;

                case 'navigate':
                    $dest = strtolower($params['destination'] ?? '');
                    $routes = [
                        'invoices' => ['name' => 'Invoices & Sales', 'url' => route('invoices.index')],
                        'bills' => ['name' => 'Vendor Bills & Expenses', 'url' => route('bills.index')],
                        'customers' => ['name' => 'Customers', 'url' => route('customers.index')],
                        'vendors' => ['name' => 'Vendors & Suppliers', 'url' => route('vendors.index')],
                        'items' => ['name' => 'Products & Services Catalog', 'url' => route('items.index')],
                        'banking' => ['name' => 'Banking & Ledger', 'url' => route('banking.index')],
                        'transfer' => ['name' => 'Fund Transfer', 'url' => route('banking.transfer')],
                        'reports' => ['name' => 'Reports & Analytics', 'url' => route('reports.profit_loss')],
                        'settings' => ['name' => 'Company & Financial Settings', 'url' => route('settings.index')],
                    ];

                    foreach ($routes as $key => $info) {
                        if (str_contains($dest, $key)) {
                            $res['reply_append'] = "Direct link: [Go to {$info['name']}]({$info['url']})";
                            break;
                        }
                    }
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Action execution failed: '.$e->getMessage());
            $res['reply_append'] = '*Action execution note: '.$e->getMessage().'*';
        }

        return $res;
    }

    /**
     * Remove all emojis, pictographs, and symbols from string
     */
    protected function stripEmojis(string $text): string
    {
        return preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2300}-\x{23FF}\x{2B50}\x{200D}\x{FE0F}]/u', '', $text);
    }
}
