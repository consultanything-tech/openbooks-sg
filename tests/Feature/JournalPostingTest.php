<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Transaction;
use App\Models\Vendor;
use Tests\TestCase;

/**
 * Stage 1 of the double-entry ledger: posting balanced journal entries alongside
 * invoices/bills, and proving the accounting equation holds from the ledger.
 */
class JournalPostingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
        $this->actingAsAdmin();
    }

    private function makeInvoice(float $subtotal, float $tax, string $status = 'sent'): Invoice
    {
        return Invoice::factory()->create([
            'customer_id' => Customer::factory()->create()->id,
            'subtotal' => $subtotal,
            'tax_total' => $tax,
            'total' => $subtotal + $tax,
            'due_amount' => $subtotal + $tax,
            'paid_amount' => 0,
            'status' => $status,
        ]);
    }

    public function test_issued_invoice_posts_balanced_accrual(): void
    {
        $invoice = $this->makeInvoice(1000.00, 90.00);

        $entry = JournalEntry::where('reference', $invoice->invoice_number . '-ACCRUAL')->first();
        $this->assertNotNull($entry, 'Accrual entry was not posted');
        $this->assertTrue($entry->isBalanced());

        $debit = fn ($code) => (float) JournalEntryLine::where('journal_entry_id', $entry->id)
            ->where('account_id', Account::where('code', $code)->value('id'))->sum('debit');
        $credit = fn ($code) => (float) JournalEntryLine::where('journal_entry_id', $entry->id)
            ->where('account_id', Account::where('code', $code)->value('id'))->sum('credit');

        $this->assertEqualsWithDelta(1090.00, $debit('1100'), 0.01); // AR
        $this->assertEqualsWithDelta(1000.00, $credit('4000'), 0.01); // Revenue
        $this->assertEqualsWithDelta(90.00, $credit('2100'), 0.01);   // GST
    }

    public function test_draft_invoice_does_not_post(): void
    {
        $invoice = $this->makeInvoice(500.00, 45.00, 'draft');
        $this->assertNull(JournalEntry::where('reference', $invoice->invoice_number . '-ACCRUAL')->first());
    }

    public function test_payment_posts_cash_entry(): void
    {
        $invoice = $this->makeInvoice(1000.00, 90.00);
        $bank = BankAccount::factory()->create(['current_balance' => 0.00]);

        // Simulate what InvoiceController::recordPayment does: update paid_amount
        // then create a Transaction. The TransactionObserver posts the cash leg.
        $invoice->update(['paid_amount' => 500.00, 'due_amount' => 590.00, 'status' => 'partial']);
        $txn = Transaction::create([
            'type' => 'income',
            'bank_account_id' => $bank->id,
            'invoice_id' => $invoice->id,
            'amount' => 500.00,
            'transaction_date' => now()->toDateString(),
            'payment_method' => 'Bank Transfer',
            'description' => 'Payment received for ' . $invoice->invoice_number,
        ]);

        $entry = JournalEntry::where('reference', 'TXN-' . $txn->id)->first();
        $this->assertNotNull($entry, 'Payment cash leg was not posted by TransactionObserver');
        $this->assertTrue($entry->isBalanced());

        // The bank ledger account (auto-mapped) should have a debit of 500
        $bank->refresh();
        $bankAccountId = Account::where('id', $bank->account_id)->value('id');
        $this->assertNotNull($bankAccountId, 'Bank account was not mapped to a ledger account');
        $this->assertEqualsWithDelta(
            500.00,
            (float) JournalEntryLine::where('journal_entry_id', $entry->id)->where('account_id', $bankAccountId)->sum('debit'),
            0.01
        );
    }

    public function test_posting_is_idempotent(): void
    {
        $invoice = $this->makeInvoice(800.00, 72.00);
        $invoice->save(); // no material change
        $invoice->refresh()->save();

        $this->assertSame(1, JournalEntry::where('reference', $invoice->invoice_number . '-ACCRUAL')->count());
    }

    public function test_received_bill_posts_accrual(): void
    {
        $bill = Bill::factory()->create([
            'vendor_id' => Vendor::factory()->create()->id,
            'subtotal' => 500.00,
            'tax_total' => 45.00,
            'total' => 545.00,
            'due_amount' => 545.00,
            'status' => 'received',
        ]);

        $entry = JournalEntry::where('reference', $bill->bill_number . '-ACCRUAL')->first();
        $this->assertNotNull($entry);
        $this->assertTrue($entry->isBalanced());
    }

    public function test_accounting_equation_holds_from_ledger(): void
    {
        // A realistic mix: a partially-paid invoice and a fully-paid bill.
        $invoice = $this->makeInvoice(1000.00, 90.00);
        $invoice->update(['paid_amount' => 500.00, 'due_amount' => 590.00, 'status' => 'partial']);

        $bill = Bill::factory()->create([
            'vendor_id' => Vendor::factory()->create()->id,
            'subtotal' => 500.00,
            'tax_total' => 45.00,
            'total' => 545.00,
            'due_amount' => 545.00,
            'status' => 'received',
        ]);
        $bill->update(['paid_amount' => 545.00, 'due_amount' => 0, 'status' => 'paid']);

        // Every posted entry must individually balance.
        foreach (JournalEntry::all() as $entry) {
            $this->assertTrue($entry->isBalanced(), "Entry {$entry->entry_number} is not balanced");
        }

        $sum = function (string $type): array {
            $row = JournalEntryLine::selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
                ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
                ->where('accounts.type', $type)
                ->first();
            return ['d' => (float) $row->d, 'c' => (float) $row->c];
        };

        $asset = $sum('asset');
        $liab = $sum('liability');
        $equity = $sum('equity');
        $revenue = $sum('revenue');
        $expense = $sum('expense');

        $assets = $asset['d'] - $asset['c'];
        $liabilities = $liab['c'] - $liab['d'];
        $equityTotal = $equity['c'] - $equity['d'];
        $netIncome = ($revenue['c'] - $revenue['d']) - ($expense['d'] - $expense['c']);

        // Assets = Liabilities + Equity + (Revenue - Expense)
        $this->assertEqualsWithDelta(
            $liabilities + $equityTotal + $netIncome,
            $assets,
            0.01,
            'The accounting equation does not balance from the ledger'
        );
    }
}
