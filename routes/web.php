<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AskController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BankingController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ChartOfAccountsController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\CustomReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\ExpenseClaimController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReceiptOcrController;
use App\Http\Controllers\RecurringController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TimeTrackingController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Installer Routes (Pre-installation & Setup)
|--------------------------------------------------------------------------
*/
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');
    Route::post('/verify-customer', [InstallController::class, 'verifyCustomer'])->name('verify');
    Route::post('/test-db', [InstallController::class, 'testDatabase'])->name('test_db');
    Route::post('/process', [InstallController::class, 'executeInstall'])->name('process');
    Route::get('/complete', [InstallController::class, 'complete'])->name('complete');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Public Invoice View (Client Portal)
|--------------------------------------------------------------------------
*/
Route::get('/invoices/public/{token}', [InvoiceController::class, 'publicShow'])->name('invoices.public');
Route::get('/quotes/public/{token}', [QuoteController::class, 'publicShow'])->name('quotes.public');

/*
|--------------------------------------------------------------------------
| Two-Factor Authentication Challenge (unauthenticated)
|--------------------------------------------------------------------------
*/
Route::get('/2fa/challenge', [TwoFactorController::class, 'challenge'])->name('2fa.challenge');
Route::post('/2fa/challenge', [TwoFactorController::class, 'challengeVerify'])->name('2fa.challenge.verify')->middleware('throttle:10,1');

/*
|--------------------------------------------------------------------------
| Customer Self-Service Portal
|--------------------------------------------------------------------------
*/
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/login', [CustomerPortalController::class, 'showLogin'])->name('login');
    Route::post('/login', [CustomerPortalController::class, 'login'])->name('login.post')->middleware('throttle:5,1');
    Route::post('/logout', [CustomerPortalController::class, 'logout'])->name('logout');
    Route::middleware('portal.auth')->group(function () {
        Route::get('/', [CustomerPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/invoices', [CustomerPortalController::class, 'invoices'])->name('invoices');
        Route::get('/invoices/{id}', [CustomerPortalController::class, 'showInvoice'])->name('invoices.show');
        Route::get('/statement', [CustomerPortalController::class, 'statement'])->name('statement');
    });
});

Route::get('/admin', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Authenticated Accounting Application Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Locale switcher
    Route::get('/locale/{locale}', function (string $locale) {
        if (in_array($locale, ['en', 'ms'])) {
            session(['locale' => $locale]);
        }

        return redirect()->back();
    })->name('locale.set');

    // Root redirect to dashboard
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // Idle-session heartbeat: touching this endpoint keeps the PHP session alive
    Route::post('/session/heartbeat', function () {
        return response()->json([
            'ok' => true,
            'lifetime_minutes' => (int) config('session.lifetime', 120),
        ]);
    })->name('session.heartbeat');

    // Dashboard (all authenticated users)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // API Documentation (admin only)
    Route::middleware(['role:ADMIN'])->group(function () {
        Route::get('/api-docs', function () {
            return redirect('/api-docs/index.html');
        })->name('api_docs');
    });

    /*
    |--------------------------------------------------------------------------
    | View-only routes (all roles: ADMIN, ACCOUNTANT, VIEWER)
    |--------------------------------------------------------------------------
    */
    // Invoices - view/list/print/export
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/export-csv', [InvoiceController::class, 'exportCsv'])->name('invoices.export_csv');
    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show')->whereNumber('id');
    Route::get('/invoices/{id}/print', [InvoiceController::class, 'print'])->name('invoices.print')->whereNumber('id');

    // Credit Notes - view/list
    Route::get('/credit-notes', [CreditNoteController::class, 'index'])->name('credit_notes.index');
    Route::get('/credit-notes/{id}', [CreditNoteController::class, 'show'])->name('credit_notes.show')->whereNumber('id');
    Route::get('/credit-notes/{id}/print', [CreditNoteController::class, 'print'])->name('credit_notes.print')->whereNumber('id');

    // Bills - view/list/export
    Route::get('/bills', [BillController::class, 'index'])->name('bills.index');
    Route::get('/bills/export-csv', [BillController::class, 'exportCsv'])->name('bills.export_csv');
    Route::get('/bills/{id}', [BillController::class, 'show'])->name('bills.show')->whereNumber('id');
    Route::get('/bills/{id}/print', [BillController::class, 'print'])->name('bills.print')->whereNumber('id');

    // Customers - view/list/export/import
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/export-csv', [CustomerController::class, 'exportCsv'])->name('customers.export_csv');
    Route::get('/customers/import-template', [CustomerController::class, 'importTemplate'])->name('customers.import_template');
    Route::get('/customers/{id}', [CustomerController::class, 'show'])->name('customers.show')->whereNumber('id');

    // Vendors - view/list/export
    Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
    Route::get('/vendors/export-csv', [VendorController::class, 'exportCsv'])->name('vendors.export_csv');
    Route::get('/vendors/{id}', [VendorController::class, 'show'])->name('vendors.show')->whereNumber('id');

    // Items - view/list/export/import
    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::get('/items/export-csv', [ItemController::class, 'exportCsv'])->name('items.export_csv');
    Route::get('/items/import-template', [ItemController::class, 'importTemplate'])->name('items.import_template');

    // Inventory - view/list/export
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/export-csv', [InventoryController::class, 'exportCsv'])->name('inventory.export_csv');
    Route::get('/inventory/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low_stock');
    Route::get('/inventory/{id}/movements', [InventoryController::class, 'movements'])->name('inventory.movements')->where('id', '[0-9]+');

    // Banking - view/list/export/import
    Route::get('/banking', [BankingController::class, 'index'])->name('banking.index');
    Route::get('/banking/transactions', [BankingController::class, 'transactions'])->name('banking.transactions');
    Route::get('/banking/transactions/export-csv', [BankingController::class, 'exportTransactions'])->name('banking.transactions.export_csv');
    Route::get('/banking/transactions/import-template', [BankingController::class, 'importTemplate'])->name('banking.transactions.import_template');

    // Recurring Templates - view/list
    Route::get('/recurring', [RecurringController::class, 'index'])->name('recurring.index');
    Route::get('/recurring/{id}', [RecurringController::class, 'show'])->name('recurring.show')->where('id', '[0-9]+');

    // Ask OpenBooks - read-only data Q&A (all roles)
    Route::get('/ask', [AskController::class, 'index'])->name('ask.index');
    Route::post('/ask', [AskController::class, 'run'])->middleware('throttle:30,1')->name('ask.run');
    Route::post('/ask/drill', [AskController::class, 'drill'])->middleware('throttle:30,1')->name('ask.drill');

    // Reports (read-only for all roles)
    Route::get('/reports/custom', [CustomReportController::class, 'index'])->name('reports.custom');
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit_loss');
    Route::get('/reports/profit-loss/export-csv', [ReportController::class, 'exportProfitLoss'])->name('reports.profit_loss.export_csv');
    Route::get('/reports/income-expense', [ReportController::class, 'incomeExpense'])->name('reports.income_expense');
    Route::get('/reports/income-expense/export-csv', [ReportController::class, 'exportIncomeExpense'])->name('reports.income_expense.export_csv');
    Route::get('/reports/tax-summary', [ReportController::class, 'taxSummary'])->name('reports.tax_summary');
    Route::get('/reports/tax-summary/export-csv', [ReportController::class, 'exportTaxSummary'])->name('reports.tax_summary.export_csv');
    Route::get('/reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance_sheet');
    Route::get('/reports/balance-sheet/export-csv', [ReportController::class, 'exportBalanceSheet'])->name('reports.balance_sheet.export_csv');
    Route::get('/reports/trial-balance', [ReportController::class, 'trialBalance'])->name('reports.trial_balance');
    Route::get('/reports/trial-balance/export-csv', [ReportController::class, 'exportTrialBalance'])->name('reports.trial_balance.export_csv');
    Route::get('/reports/general-ledger', [ReportController::class, 'generalLedger'])->name('reports.general_ledger');
    Route::get('/reports/general-ledger/export-csv', [ReportController::class, 'exportGeneralLedger'])->name('reports.general_ledger.export_csv');
    Route::get('/reports/ar-aging', [ReportController::class, 'arAging'])->name('reports.ar_aging');
    Route::get('/reports/ar-aging/export-csv', [ReportController::class, 'exportArAging'])->name('reports.ar_aging.export_csv');
    Route::get('/reports/ap-aging', [ReportController::class, 'apAging'])->name('reports.ap_aging');
    Route::get('/reports/ap-aging/export-csv', [ReportController::class, 'exportApAging'])->name('reports.ap_aging.export_csv');
    Route::get('/reports/gst-f5', [ReportController::class, 'gstF5'])->name('reports.gst_f5');
    Route::get('/reports/gst-f5/export-csv', [ReportController::class, 'exportGstF5'])->name('reports.gst_f5.export_csv');

    // Quotations - view/list/export
    Route::get('/quotes', [QuoteController::class, 'index'])->name('quotes.index');
    Route::get('/quotes/export-csv', [QuoteController::class, 'exportCsv'])->name('quotes.export_csv');
    Route::get('/quotes/{id}', [QuoteController::class, 'show'])->name('quotes.show')->where('id', '[0-9]+');
    Route::get('/quotes/{id}/print', [QuoteController::class, 'print'])->name('quotes.print')->where('id', '[0-9]+');

    // Expense Claims - view/list (all users see their own)
    Route::get('/expense-claims', [ExpenseClaimController::class, 'index'])->name('expense_claims.index');
    Route::get('/expense-claims/{id}', [ExpenseClaimController::class, 'show'])->name('expense_claims.show')->where('id', '[0-9]+');

    // Time Tracking - view/list/export
    Route::get('/time-tracking', [TimeTrackingController::class, 'index'])->name('time_tracking.index');
    Route::get('/time-tracking/export-csv', [TimeTrackingController::class, 'exportCsv'])->name('time_tracking.export_csv');
    Route::get('/time-tracking/{id}', [TimeTrackingController::class, 'show'])->name('time_tracking.show')->where('id', '[0-9]+');

    // Chart of Accounts - view/list
    Route::get('/chart-of-accounts', [ChartOfAccountsController::class, 'index'])->name('accounts.index');
    Route::get('/chart-of-accounts/trial-balance', [ChartOfAccountsController::class, 'trialBalance'])->name('accounts.trial_balance');
    Route::get('/chart-of-accounts/journal-entries', [ChartOfAccountsController::class, 'journalEntries'])->name('accounts.journal_entries');

    // Notifications (AJAX)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read_all');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Two-Factor Authentication (authenticated settings)
    Route::get('/settings/two-factor', [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('/settings/two-factor/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::post('/settings/two-factor/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify');
    Route::post('/settings/two-factor/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');

    // Budgets (view for all roles)
    Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');

    // Onboarding Wizard (all roles)
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::post('/onboarding/dismiss', [OnboardingController::class, 'dismiss'])->name('onboarding.dismiss');

    /*
    |--------------------------------------------------------------------------
    | Create/Edit/Delete routes (ADMIN and ACCOUNTANT only)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:ADMIN,ACCOUNTANT'])->group(function () {
        // Invoices - create/edit/delete/actions
        Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::post('/invoices/{id}/payment', [InvoiceController::class, 'recordPayment'])->name('invoices.payment');
        Route::get('/invoices/{id}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
        Route::put('/invoices/{id}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
        Route::post('/invoices/{id}/restore', [InvoiceController::class, 'restore'])->name('invoices.restore');
        Route::post('/invoices/bulk-delete', [InvoiceController::class, 'bulkDelete'])->name('invoices.bulk_delete');
        Route::post('/invoices/bulk-restore', [InvoiceController::class, 'bulkRestore'])->name('invoices.bulk_restore');
        Route::post('/invoices/{id}/mark-sent', [InvoiceController::class, 'markSent'])->name('invoices.mark_sent');
        Route::post('/invoices/{id}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');

        // Recurring Templates - create/edit/delete/actions
        Route::get('/recurring/create', [RecurringController::class, 'create'])->name('recurring.create');
        Route::post('/recurring', [RecurringController::class, 'store'])->name('recurring.store');
        Route::get('/recurring/{id}/edit', [RecurringController::class, 'edit'])->name('recurring.edit');
        Route::put('/recurring/{id}', [RecurringController::class, 'update'])->name('recurring.update');
        Route::delete('/recurring/{id}', [RecurringController::class, 'destroy'])->name('recurring.destroy');
        Route::post('/recurring/{id}/restore', [RecurringController::class, 'restore'])->name('recurring.restore');
        Route::post('/recurring/{id}/toggle', [RecurringController::class, 'toggle'])->name('recurring.toggle');
        Route::post('/recurring/{id}/generate', [RecurringController::class, 'generateNow'])->name('recurring.generate_now');

        // Credit Notes - create/edit/delete/apply
        Route::get('/credit-notes/create', [CreditNoteController::class, 'create'])->name('credit_notes.create');
        Route::post('/credit-notes', [CreditNoteController::class, 'store'])->name('credit_notes.store');
        Route::get('/credit-notes/{id}/edit', [CreditNoteController::class, 'edit'])->name('credit_notes.edit');
        Route::put('/credit-notes/{id}', [CreditNoteController::class, 'update'])->name('credit_notes.update');
        Route::delete('/credit-notes/{id}', [CreditNoteController::class, 'destroy'])->name('credit_notes.destroy');
        Route::post('/credit-notes/{id}/restore', [CreditNoteController::class, 'restore'])->name('credit_notes.restore');
        Route::post('/credit-notes/{id}/apply', [CreditNoteController::class, 'apply'])->name('credit_notes.apply');

        // Bills - create/edit/delete/payment
        Route::get('/bills/create', [BillController::class, 'create'])->name('bills.create');
        Route::post('/bills', [BillController::class, 'store'])->name('bills.store');
        Route::post('/bills/{id}/payment', [BillController::class, 'recordPayment'])->name('bills.payment');
        Route::get('/bills/{id}/edit', [BillController::class, 'edit'])->name('bills.edit');
        Route::put('/bills/{id}', [BillController::class, 'update'])->name('bills.update');
        Route::delete('/bills/{id}', [BillController::class, 'destroy'])->name('bills.destroy');
        Route::post('/bills/{id}/restore', [BillController::class, 'restore'])->name('bills.restore');
        Route::post('/bills/bulk-delete', [BillController::class, 'bulkDelete'])->name('bills.bulk_delete');
        Route::post('/bills/bulk-restore', [BillController::class, 'bulkRestore'])->name('bills.bulk_restore');

        // Customers - create/edit/delete/import
        Route::post('/customers/import-csv', [CustomerController::class, 'importCsv'])->name('customers.import_csv');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{id}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{id}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::post('/customers/{id}/restore', [CustomerController::class, 'restore'])->name('customers.restore');
        Route::post('/customers/bulk-delete', [CustomerController::class, 'bulkDelete'])->name('customers.bulk_delete');
        Route::post('/customers/bulk-restore', [CustomerController::class, 'bulkRestore'])->name('customers.bulk_restore');

        // Vendors - create/edit/delete
        Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');
        Route::get('/vendors/{id}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
        Route::put('/vendors/{id}', [VendorController::class, 'update'])->name('vendors.update');
        Route::delete('/vendors/{id}', [VendorController::class, 'destroy'])->name('vendors.destroy');
        Route::post('/vendors/{id}/restore', [VendorController::class, 'restore'])->name('vendors.restore');
        Route::post('/vendors/bulk-delete', [VendorController::class, 'bulkDelete'])->name('vendors.bulk_delete');
        Route::post('/vendors/bulk-restore', [VendorController::class, 'bulkRestore'])->name('vendors.bulk_restore');

        // Items - create/edit/delete/import
        Route::post('/items/import-csv', [ItemController::class, 'importCsv'])->name('items.import_csv');
        Route::post('/items', [ItemController::class, 'store'])->name('items.store');
        Route::put('/items/{id}', [ItemController::class, 'update'])->name('items.update');
        Route::delete('/items/{id}', [ItemController::class, 'destroy'])->name('items.destroy');
        Route::post('/items/{id}/restore', [ItemController::class, 'restore'])->name('items.restore');
        Route::post('/items/bulk-delete', [ItemController::class, 'bulkDelete'])->name('items.bulk_delete');
        Route::post('/items/bulk-restore', [ItemController::class, 'bulkRestore'])->name('items.bulk_restore');

        // Inventory - adjust/receive
        Route::post('/inventory/{id}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust')->where('id', '[0-9]+');
        Route::post('/inventory/receive', [InventoryController::class, 'receive'])->name('inventory.receive');

        // Banking - create/edit/delete/transfer/reconcile/import
        Route::post('/banking/transactions/import-csv', [BankingController::class, 'importTransactions'])->name('banking.transactions.import_csv');
        Route::post('/banking', [BankingController::class, 'store'])->name('banking.store');
        Route::get('/banking/transfer', [BankingController::class, 'transferForm'])->name('banking.transfer');
        Route::post('/banking/transfer', [BankingController::class, 'transfer'])->name('banking.transfer.post');
        Route::get('/banking/{id}/reconcile', [BankingController::class, 'reconcile'])->name('banking.reconcile');
        Route::post('/banking/{id}/reconcile', [BankingController::class, 'processReconcile'])->name('banking.reconcile.process');
        Route::post('/banking/transactions/{id}/unreconcile', [BankingController::class, 'unreconcile'])->name('banking.unreconcile');
        Route::put('/banking/{id}', [BankingController::class, 'update'])->name('banking.update');
        Route::delete('/banking/{id}', [BankingController::class, 'destroy'])->name('banking.destroy');

        // Budgets - create/store
        Route::get('/budgets/create', [BudgetController::class, 'create'])->name('budgets.create');
        Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');

        // Quotations - create/edit/delete/convert
        Route::get('/quotes/create', [QuoteController::class, 'create'])->name('quotes.create');
        Route::post('/quotes', [QuoteController::class, 'store'])->name('quotes.store');
        Route::get('/quotes/{id}/edit', [QuoteController::class, 'edit'])->name('quotes.edit');
        Route::put('/quotes/{id}', [QuoteController::class, 'update'])->name('quotes.update');
        Route::delete('/quotes/{id}', [QuoteController::class, 'destroy'])->name('quotes.destroy');
        Route::post('/quotes/{id}/restore', [QuoteController::class, 'restore'])->name('quotes.restore');
        Route::post('/quotes/bulk-delete', [QuoteController::class, 'bulkDelete'])->name('quotes.bulk_delete');
        Route::post('/quotes/bulk-restore', [QuoteController::class, 'bulkRestore'])->name('quotes.bulk_restore');
        Route::post('/quotes/{id}/mark-sent', [QuoteController::class, 'markSent'])->name('quotes.mark_sent');
        Route::post('/quotes/{id}/mark-accepted', [QuoteController::class, 'markAccepted'])->name('quotes.mark_accepted');
        Route::post('/quotes/{id}/mark-declined', [QuoteController::class, 'markDeclined'])->name('quotes.mark_declined');
        Route::post('/quotes/{id}/convert-to-invoice', [QuoteController::class, 'convertToInvoice'])->name('quotes.convert');

        // Time Tracking - create/edit/delete/convert
        Route::get('/time-tracking/create', [TimeTrackingController::class, 'create'])->name('time_tracking.create');
        Route::post('/time-tracking', [TimeTrackingController::class, 'store'])->name('time_tracking.store');
        Route::get('/time-tracking/{id}/edit', [TimeTrackingController::class, 'edit'])->name('time_tracking.edit');
        Route::put('/time-tracking/{id}', [TimeTrackingController::class, 'update'])->name('time_tracking.update');
        Route::delete('/time-tracking/{id}', [TimeTrackingController::class, 'destroy'])->name('time_tracking.destroy');
        Route::post('/time-tracking/convert-to-invoice', [TimeTrackingController::class, 'convertToInvoice'])->name('time_tracking.convert');

        // Chart of Accounts - create/edit/delete/journal entries
        Route::post('/chart-of-accounts', [ChartOfAccountsController::class, 'store'])->name('accounts.store');
        Route::put('/chart-of-accounts/{id}', [ChartOfAccountsController::class, 'update'])->name('accounts.update');
        Route::delete('/chart-of-accounts/{id}', [ChartOfAccountsController::class, 'destroy'])->name('accounts.destroy');
        Route::get('/chart-of-accounts/journal-entries/create', [ChartOfAccountsController::class, 'createJournalEntry'])->name('accounts.journal_entries.create');
        Route::post('/chart-of-accounts/journal-entries', [ChartOfAccountsController::class, 'storeJournalEntry'])->name('accounts.journal_entries.store');
        Route::post('/chart-of-accounts/seed-defaults', [ChartOfAccountsController::class, 'seedDefaults'])->name('accounts.seed_defaults');

        // Email - send invoices/quotes/reminders
        Route::post('/invoices/{id}/send-email', [EmailController::class, 'sendInvoice'])->name('invoices.send_email');
        Route::post('/quotes/{id}/send-email', [EmailController::class, 'sendQuote'])->name('quotes.send_email');
        Route::post('/invoices/{id}/send-reminder', [EmailController::class, 'sendReminder'])->name('invoices.send_reminder');

        // Expense Claims - create/delete
        Route::get('/expense-claims/scan', [ReceiptOcrController::class, 'scan'])->name('receipt_ocr.scan');
        Route::get('/expense-claims/create', [ExpenseClaimController::class, 'create'])->name('expense_claims.create');
        Route::post('/expense-claims', [ExpenseClaimController::class, 'store'])->name('expense_claims.store');
        Route::delete('/expense-claims/{id}', [ExpenseClaimController::class, 'destroy'])->name('expense_claims.destroy');
        Route::post('/expense-claims/{id}/restore', [ExpenseClaimController::class, 'restore'])->name('expense_claims.restore');
        Route::post('/expense-claims/{id}/approve', [ExpenseClaimController::class, 'approve'])->name('expense_claims.approve');
        Route::post('/expense-claims/{id}/reject', [ExpenseClaimController::class, 'reject'])->name('expense_claims.reject');
        Route::post('/expense-claims/{id}/mark-paid', [ExpenseClaimController::class, 'markPaid'])->name('expense_claims.mark_paid');

        // AI Assistant
        Route::post('/ai/chat', [AiChatController::class, 'chat'])->name('ai.chat');

        // Receipt OCR
        Route::post('/receipt-ocr/upload', [ReceiptOcrController::class, 'upload'])->name('receipt_ocr.upload');
        Route::post('/receipt-ocr/create', [ReceiptOcrController::class, 'createFromReceipt'])->name('receipt_ocr.create');

        // Custom Report Builder
        Route::post('/reports/custom/run', [CustomReportController::class, 'run'])->name('reports.custom.run');
        Route::post('/reports/custom', [CustomReportController::class, 'store'])->name('reports.custom.store');
        Route::delete('/reports/custom/{id}', [CustomReportController::class, 'destroy'])->name('reports.custom.destroy');
        Route::post('/reports/custom/export', [CustomReportController::class, 'exportCsv'])->name('reports.custom.export');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin-only routes (Settings, User Management, System Updates)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:ADMIN'])->group(function () {
        // Settings
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/company', [SettingController::class, 'updateCompany'])->name('settings.company');
        Route::post('/settings/categories', [SettingController::class, 'storeCategory'])->name('settings.categories');
        Route::post('/settings/taxes', [SettingController::class, 'storeTax'])->name('settings.taxes');
        Route::post('/settings/ai', [SettingController::class, 'updateAi'])->name('settings.ai');
        Route::delete('/settings/logo', [SettingController::class, 'removeLogo'])->name('settings.logo.remove');
        Route::post('/settings/branding', [SettingController::class, 'updateBranding'])->name('settings.branding');

        // SMTP / Email Settings
        Route::get('/settings/smtp', [EmailController::class, 'smtpSettings'])->name('settings.smtp');
        Route::post('/settings/smtp', [EmailController::class, 'updateSmtp'])->name('settings.smtp.update');
        Route::post('/settings/smtp/test', [EmailController::class, 'testSmtp'])->name('settings.smtp.test');

        // Customer Portal Management
        Route::post('/customers/{id}/enable-portal', [CustomerPortalController::class, 'enablePortal'])->name('customers.enable_portal');

        // Currency Management
        Route::get('/settings/currencies', [SettingController::class, 'currencies'])->name('settings.currencies');
        Route::post('/settings/currencies', [SettingController::class, 'storeCurrency'])->name('settings.currencies.store');
        Route::put('/settings/currencies/{id}', [SettingController::class, 'updateCurrency'])->name('settings.currencies.update');
        Route::delete('/settings/currencies/{id}', [SettingController::class, 'destroyCurrency'])->name('settings.currencies.destroy');

        // User Management
        Route::get('/settings/users', [SettingController::class, 'users'])->name('settings.users');
        Route::post('/settings/users', [SettingController::class, 'storeUser'])->name('settings.users.store');
        Route::put('/settings/users/{id}', [SettingController::class, 'updateUser'])->name('settings.users.update');
        Route::delete('/settings/users/{id}', [SettingController::class, 'destroyUser'])->name('settings.users.destroy');

        // Backup & Restore
        Route::get('/settings/backups', [BackupController::class, 'index'])->name('settings.backups');
        Route::post('/settings/backups', [BackupController::class, 'create'])->name('settings.backups.create');
        Route::get('/settings/backups/{filename}/download', [BackupController::class, 'download'])->name('settings.backups.download');
        Route::delete('/settings/backups/{filename}', [BackupController::class, 'destroy'])->name('settings.backups.destroy');

        // Activity Log
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity_log.index');

        // Webhooks
        Route::get('/webhooks', [WebhookController::class, 'index'])->name('webhooks.index');
        Route::post('/webhooks', [WebhookController::class, 'store'])->name('webhooks.store');
        Route::put('/webhooks/{id}', [WebhookController::class, 'update'])->name('webhooks.update');
        Route::delete('/webhooks/{id}', [WebhookController::class, 'destroy'])->name('webhooks.destroy');

        // System Updates
        Route::get('/updates', [UpdateController::class, 'index'])->name('updates.index');
        Route::get('/updates/check', [UpdateController::class, 'check'])->name('updates.check');
        Route::post('/updates/apply', [UpdateController::class, 'apply'])->name('updates.apply');
    });
});
