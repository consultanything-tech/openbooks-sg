<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\EmailLog;
use App\Models\Invoice;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPaymentReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Send automated payment reminders for overdue invoices';

    public function handle(): int
    {
        $company = Company::first();
        if (! $company || ! $company->auto_reminders_enabled) {
            $this->info('Auto reminders disabled or company not configured.');

            return 0;
        }

        if (empty($company->smtp_host)) {
            $this->warn('SMTP not configured. Skipping reminders.');

            return 0;
        }

        // Configure SMTP
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $company->smtp_host);
        Config::set('mail.mailers.smtp.port', $company->smtp_port ?? 587);
        Config::set('mail.mailers.smtp.username', $company->smtp_username);
        Config::set('mail.mailers.smtp.password', $company->smtp_password);
        Config::set('mail.mailers.smtp.encryption', $company->smtp_encryption ?? 'tls');
        Config::set('mail.from.address', $company->smtp_from_email ?? $company->email);
        Config::set('mail.from.name', $company->smtp_from_name ?? $company->name);

        $today = Carbon::today();
        $reminderDays = [
            $company->reminder_days_1 ?? 3,
            $company->reminder_days_2 ?? 7,
            $company->reminder_days_3 ?? 14,
        ];

        $overdueInvoices = Invoice::with('customer')
            ->whereIn('status', ['sent', 'partial'])
            ->where('due_date', '<', $today)
            ->get();

        $sent = 0;

        foreach ($overdueInvoices as $invoice) {
            $customer = $invoice->customer;
            if (! $customer || empty($customer->email)) {
                continue;
            }

            $overdueDays = $today->diffInDays($invoice->due_date);

            // Only send on configured reminder days
            if (! in_array($overdueDays, $reminderDays)) {
                continue;
            }

            $balanceDue = $invoice->total - $invoice->paid_amount;
            if ($balanceDue <= 0.01) {
                continue;
            }

            $currencySymbol = $company->currency_symbol ?? 'S$';
            $portalLink = route('invoices.public', $invoice->public_token);

            try {
                Mail::html(
                    "<div style='font-family:sans-serif;max-width:600px;margin:0 auto'>"
                    ."<h2 style='color:#ef4444'>Payment Reminder</h2>"
                    ."<p>Dear {$customer->name},</p>"
                    ."<p>This is a friendly reminder that invoice <strong>{$invoice->invoice_number}</strong> "
                    ."is <strong>{$overdueDays} days overdue</strong>.</p>"
                    ."<table style='width:100%;border-collapse:collapse;margin:16px 0'>"
                    ."<tr><td style='padding:8px;border:1px solid #e5e7eb'>Invoice</td><td style='padding:8px;border:1px solid #e5e7eb;font-weight:bold'>{$invoice->invoice_number}</td></tr>"
                    ."<tr><td style='padding:8px;border:1px solid #e5e7eb'>Due Date</td><td style='padding:8px;border:1px solid #e5e7eb'>".date('M d, Y', strtotime($invoice->due_date)).'</td></tr>'
                    ."<tr><td style='padding:8px;border:1px solid #e5e7eb'>Amount Due</td><td style='padding:8px;border:1px solid #e5e7eb;font-weight:bold;color:#ef4444'>{$currencySymbol}".number_format($balanceDue, 2).'</td></tr>'
                    .'</table>'
                    ."<p><a href='{$portalLink}' style='display:inline-block;padding:12px 24px;background:#4f46e5;color:white;text-decoration:none;border-radius:8px;font-weight:bold'>View & Pay Invoice</a></p>"
                    ."<p style='color:#6b7280;font-size:12px'>If you have already made this payment, please disregard this reminder.</p>"
                    ."<hr style='border-color:#e5e7eb'>"
                    ."<p style='color:#9ca3af;font-size:11px'>{$company->name} &middot; {$company->email}</p>"
                    .'</div>',
                    function ($message) use ($customer, $invoice, $company) {
                        $message->to($customer->email, $customer->name)
                            ->subject("Payment Reminder: {$invoice->invoice_number} — {$company->name}");
                    }
                );

                EmailLog::create([
                    'to_email' => $customer->email,
                    'to_name' => $customer->name,
                    'subject' => "Payment Reminder: {$invoice->invoice_number}",
                    'type' => 'reminder',
                    'related_id' => $invoice->id,
                    'related_type' => 'Invoice',
                    'status' => 'sent',
                ]);

                $sent++;
            } catch (\Throwable $e) {
                Log::error("Reminder email failed for {$invoice->invoice_number}: ".$e->getMessage());

                EmailLog::create([
                    'to_email' => $customer->email,
                    'to_name' => $customer->name,
                    'subject' => "Payment Reminder: {$invoice->invoice_number}",
                    'type' => 'reminder',
                    'related_id' => $invoice->id,
                    'related_type' => 'Invoice',
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Sent {$sent} payment reminders.");

        if ($sent > 0) {
            Notification::sendToAdmins(
                'reminders_sent',
                "Sent {$sent} payment reminders",
                "{$sent} overdue invoice reminders were sent automatically.",
                null,
                'clock'
            );
        }

        return 0;
    }
}
