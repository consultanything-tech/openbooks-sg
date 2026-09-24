<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\EmailLog;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    use \App\Traits\LogsActivity;

    /**
     * Configure SMTP on-the-fly from company settings.
     */
    private function configureSmtp(Company $company): void
    {
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $company->smtp_host);
        Config::set('mail.mailers.smtp.port', $company->smtp_port);
        Config::set('mail.mailers.smtp.username', $company->smtp_username);
        Config::set('mail.mailers.smtp.password', $company->smtp_password);
        Config::set('mail.mailers.smtp.encryption', $company->smtp_encryption);
        Config::set('mail.from.address', $company->smtp_from_email);
        Config::set('mail.from.name', $company->smtp_from_name);
    }

    /**
     * Send invoice as email to customer.
     */
    public function sendInvoice(Request $request, $id)
    {
        $invoice = Invoice::with('customer')->findOrFail($id);
        $company = Company::first();
        $customer = $invoice->customer;

        $this->configureSmtp($company);

        $portalLink = url("/portal/invoice/{$invoice->public_token}");
        $subject = "Invoice {$invoice->invoice_number} from {$company->name}";

        $htmlBody = "
            <div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\">
                <h2 style=\"color: #333;\">Invoice {$invoice->invoice_number}</h2>
                <p>Dear {$customer->name},</p>
                <p>Please find below a summary of your invoice:</p>
                <table style=\"width: 100%; border-collapse: collapse; margin: 20px 0;\">
                    <tr><td style=\"padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;\">Invoice Number</td><td style=\"padding: 8px; border-bottom: 1px solid #eee;\">{$invoice->invoice_number}</td></tr>
                    <tr><td style=\"padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;\">Total Amount</td><td style=\"padding: 8px; border-bottom: 1px solid #eee;\">{$company->currency_symbol}{$invoice->total}</td></tr>
                    <tr><td style=\"padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;\">Due Date</td><td style=\"padding: 8px; border-bottom: 1px solid #eee;\">{$invoice->due_date}</td></tr>
                </table>
                <p><a href=\"{$portalLink}\" style=\"display: inline-block; padding: 12px 24px; background-color: #2563eb; color: #ffffff; text-decoration: none; border-radius: 6px;\">View Invoice</a></p>
                <p style=\"color: #666; font-size: 13px;\">Thank you for your business.<br>{$company->name}</p>
            </div>
        ";

        Mail::html($htmlBody, function ($message) use ($customer, $subject) {
            $message->to($customer->email, $customer->name)
                    ->subject($subject);
        });

        EmailLog::create([
            'type' => 'invoice',
            'related_id' => $invoice->id,
            'related_type' => 'Invoice',
            'to_email' => $customer->email,
            'to_name' => $customer->name,
            'subject' => $subject,
            'status' => 'sent',
            'sent_by' => auth()->id(),
        ]);

        Notification::sendToAdmins(
            'invoice_sent',
            "Invoice {$invoice->invoice_number} emailed",
            "Invoice {$invoice->invoice_number} was sent to {$customer->name} ({$customer->email}).",
            route('invoices.show', $invoice->id),
            'mail'
        );

        $this->logActivity('emailed', "Sent invoice {$invoice->invoice_number} to {$customer->email}", 'Invoice', $invoice->id);

        return redirect()->back()->with('success', "Invoice {$invoice->invoice_number} has been sent to {$customer->email}.");
    }

    /**
     * Send quote as email to customer.
     */
    public function sendQuote(Request $request, $id)
    {
        $quote = Quote::with('customer')->findOrFail($id);
        $company = Company::first();
        $customer = $quote->customer;

        $this->configureSmtp($company);

        $portalLink = url("/portal/quote/{$quote->public_token}");
        $subject = "Quote {$quote->quote_number} from {$company->name}";

        $htmlBody = "
            <div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\">
                <h2 style=\"color: #333;\">Quote {$quote->quote_number}</h2>
                <p>Dear {$customer->name},</p>
                <p>Please find below a summary of your quote:</p>
                <table style=\"width: 100%; border-collapse: collapse; margin: 20px 0;\">
                    <tr><td style=\"padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;\">Quote Number</td><td style=\"padding: 8px; border-bottom: 1px solid #eee;\">{$quote->quote_number}</td></tr>
                    <tr><td style=\"padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;\">Total Amount</td><td style=\"padding: 8px; border-bottom: 1px solid #eee;\">{$company->currency_symbol}{$quote->total}</td></tr>
                    <tr><td style=\"padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;\">Valid Until</td><td style=\"padding: 8px; border-bottom: 1px solid #eee;\">{$quote->expiry_date}</td></tr>
                </table>
                <p><a href=\"{$portalLink}\" style=\"display: inline-block; padding: 12px 24px; background-color: #2563eb; color: #ffffff; text-decoration: none; border-radius: 6px;\">View Quote</a></p>
                <p style=\"color: #666; font-size: 13px;\">Thank you for your interest.<br>{$company->name}</p>
            </div>
        ";

        Mail::html($htmlBody, function ($message) use ($customer, $subject) {
            $message->to($customer->email, $customer->name)
                    ->subject($subject);
        });

        EmailLog::create([
            'type' => 'quote',
            'related_id' => $quote->id,
            'related_type' => 'Quote',
            'to_email' => $customer->email,
            'to_name' => $customer->name,
            'subject' => $subject,
            'status' => 'sent',
            'sent_by' => auth()->id(),
        ]);

        $this->logActivity('emailed', "Sent quote {$quote->quote_number} to {$customer->email}", 'Quote', $quote->id);

        return redirect()->back()->with('success', "Quote {$quote->quote_number} has been sent to {$customer->email}.");
    }

    /**
     * Send payment reminder for overdue invoice.
     */
    public function sendReminder(Request $request, $id)
    {
        $invoice = Invoice::with('customer')->findOrFail($id);
        $company = Company::first();
        $customer = $invoice->customer;

        $this->configureSmtp($company);

        $overdueDays = (int) now()->diffInDays($invoice->due_date, false) * -1;
        $portalLink = url("/portal/invoice/{$invoice->public_token}");
        $subject = "Payment Reminder: Invoice {$invoice->invoice_number} is overdue";

        $htmlBody = "
            <div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\">
                <h2 style=\"color: #dc2626;\">Payment Reminder</h2>
                <p>Dear {$customer->name},</p>
                <p>This is a friendly reminder that the following invoice is <strong>{$overdueDays} days overdue</strong>:</p>
                <table style=\"width: 100%; border-collapse: collapse; margin: 20px 0;\">
                    <tr><td style=\"padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;\">Invoice Number</td><td style=\"padding: 8px; border-bottom: 1px solid #eee;\">{$invoice->invoice_number}</td></tr>
                    <tr><td style=\"padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;\">Amount Due</td><td style=\"padding: 8px; border-bottom: 1px solid #eee;\">{$company->currency_symbol}{$invoice->due_amount}</td></tr>
                    <tr><td style=\"padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;\">Due Date</td><td style=\"padding: 8px; border-bottom: 1px solid #eee;\">{$invoice->due_date}</td></tr>
                    <tr><td style=\"padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;\">Overdue By</td><td style=\"padding: 8px; border-bottom: 1px solid #eee; color: #dc2626;\">{$overdueDays} days</td></tr>
                </table>
                <p>Please make your payment at your earliest convenience.</p>
                <p><a href=\"{$portalLink}\" style=\"display: inline-block; padding: 12px 24px; background-color: #dc2626; color: #ffffff; text-decoration: none; border-radius: 6px;\">Pay Now</a></p>
                <p style=\"color: #666; font-size: 13px;\">If you have already made this payment, please disregard this reminder.<br>{$company->name}</p>
            </div>
        ";

        Mail::html($htmlBody, function ($message) use ($customer, $subject) {
            $message->to($customer->email, $customer->name)
                    ->subject($subject);
        });

        EmailLog::create([
            'type' => 'reminder',
            'related_id' => $invoice->id,
            'related_type' => 'Invoice',
            'to_email' => $customer->email,
            'to_name' => $customer->name,
            'subject' => $subject,
            'status' => 'sent',
            'sent_by' => auth()->id(),
        ]);

        $this->logActivity('reminded', "Sent payment reminder for invoice {$invoice->invoice_number} to {$customer->email}", 'Invoice', $invoice->id);

        return redirect()->back()->with('success', "Payment reminder has been sent to {$customer->email}.");
    }

    /**
     * Show SMTP configuration view.
     */
    public function smtpSettings()
    {
        $company = Company::first() ?? new Company();

        return view('settings.smtp', compact('company'));
    }

    /**
     * Save SMTP settings to company table.
     */
    public function updateSmtp(Request $request)
    {
        $validated = $request->validate([
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'smtp_username' => 'required|string|max:255',
            'smtp_password' => 'required|string|max:255',
            'smtp_encryption' => 'required|in:tls,ssl,none',
            'smtp_from_email' => 'required|email|max:255',
            'smtp_from_name' => 'required|string|max:255',
            'reminder_days_1' => 'nullable|integer|min:1|max:365',
            'reminder_days_2' => 'nullable|integer|min:1|max:365',
            'reminder_days_3' => 'nullable|integer|min:1|max:365',
            'auto_reminders_enabled' => 'nullable|boolean',
        ]);

        $company = Company::first();
        $company->update($validated);

        $this->logActivity('updated', 'Updated SMTP settings', 'Company', $company->id);

        return redirect()->back()->with('success', 'SMTP settings have been updated successfully.');
    }

    /**
     * Send a test email to verify SMTP configuration.
     */
    public function testSmtp(Request $request)
    {
        try {
            $company = Company::first();
            $this->configureSmtp($company);

            $userEmail = auth()->user()->email;
            $subject = "Test Email from {$company->name}";

            Mail::html(
                "<div style=\"font-family: Arial, sans-serif;\"><h2>SMTP Test Successful</h2><p>This is a test email from your OpenBooks SG application. Your SMTP settings are configured correctly.</p><p style=\"color: #666;\">Sent at " . now()->toDateTimeString() . "</p></div>",
                function ($message) use ($userEmail, $subject) {
                    $message->to($userEmail)->subject($subject);
                }
            );

            return response()->json(['success' => true, 'message' => "Test email sent to {$userEmail}."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'SMTP test failed: ' . $e->getMessage()], 422);
        }
    }
}
