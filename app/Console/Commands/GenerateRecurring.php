<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\RecurringTemplate;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateRecurring extends Command
{
    protected $signature = 'recurring:generate';

    protected $description = 'Generate invoices and bills from active recurring templates whose next_due_date has arrived. Schedule this via cron (e.g. daily).';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        $templates = RecurringTemplate::with('items')
            ->where('is_active', true)
            ->where('next_due_date', '<=', $today)
            ->get();

        if ($templates->isEmpty()) {
            $this->info('No recurring templates due for generation.');
            return self::SUCCESS;
        }

        $generated = 0;
        $errors = 0;

        foreach ($templates as $template) {
            try {
                DB::transaction(function () use ($template, $today) {
                    if ($template->type === 'invoice') {
                        $this->generateInvoice($template, $today);
                    } else {
                        $this->generateBill($template, $today);
                    }

                    $template->update([
                        'last_generated_at' => now(),
                        'next_due_date' => $this->calculateNextDueDate($template->next_due_date, $template->frequency),
                    ]);
                });

                $generated++;
                $this->info("Generated {$template->type} from template #{$template->id}");
            } catch (\Exception $e) {
                $errors++;
                $this->error("Failed to generate from template #{$template->id}: {$e->getMessage()}");
            }
        }

        $this->info("Done. Generated: {$generated}, Errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function generateInvoice(RecurringTemplate $template, string $today): void
    {
        $lastId = Invoice::withTrashed()->max('id') ?? 0;
        $nextNumber = 'INV-' . date('Y') . '-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);

        $dueDate = Carbon::parse($today)->addDays(30)->toDateString();

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

        $customer = Customer::find($template->customer_id);
        if ($customer) {
            $customer->increment('balance', $template->total);
        }
    }

    private function generateBill(RecurringTemplate $template, string $today): void
    {
        $lastId = Bill::withTrashed()->max('id') ?? 0;
        $nextNumber = 'BILL-' . date('Y') . '-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);

        $dueDate = Carbon::parse($today)->addDays(30)->toDateString();

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

        $vendor = Vendor::find($template->vendor_id);
        if ($vendor) {
            $vendor->increment('balance', $template->total);
        }
    }

    private function calculateNextDueDate($currentDate, string $frequency): string
    {
        $date = Carbon::parse($currentDate);

        return match ($frequency) {
            'weekly' => $date->addWeek()->toDateString(),
            'monthly' => $date->addMonth()->toDateString(),
            'quarterly' => $date->addMonths(3)->toDateString(),
            'yearly' => $date->addYear()->toDateString(),
        };
    }
}
