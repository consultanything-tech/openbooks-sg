<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Invoice;
use App\Models\Bill;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function profitLoss(Request $request, \App\Services\Accounting\LedgerReportService $ledger)
    {
        $startDate = $request->input('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->input('end_date', now()->endOfYear()->toDateString());

        // Accrual revenue/expense NET of GST, from the double-entry ledger.
        // (GST collected/paid is a liability/recoverable, not income/expense.)
        $pl = $ledger->profitLoss($startDate, $endDate);
        $netRevenue = $pl['netRevenue'];
        $netExpense = $pl['netExpense'];
        $netProfit = $pl['netProfit'];

        // Descriptive gross (GST-inclusive) document totals for context.
        $grossSales = (float) Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'partial', 'sent', 'viewed'])->sum('total');
        $grossBills = (float) Bill::whereBetween('bill_date', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'partial', 'received'])->sum('total');

        // Cash flow transactions
        $cashIncome = (float) Transaction::where('type', 'income')
            ->whereBetween('transaction_date', [$startDate, $endDate])->sum('amount');
        $cashExpense = (float) Transaction::where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate])->sum('amount');

        // Grouped expenses by category
        $expensesByCategory = Category::where('type', 'expense')
            ->withSum(['transactions' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('transaction_date', [$startDate, $endDate]);
            }], 'amount')
            ->get();

        $netCashFlow = $cashIncome - $cashExpense;

        return view('reports.profit_loss', compact(
            'startDate', 'endDate', 'grossSales', 'grossBills', 'netRevenue', 'netExpense',
            'cashIncome', 'cashExpense', 'expensesByCategory', 'netProfit', 'netCashFlow'
        ));
    }

    public function incomeExpense(Request $request)
    {
        $year = $request->input('year', now()->year);

        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthStart = sprintf('%04d-%02d-01', $year, $m);
            $monthEnd = date('Y-m-t', strtotime($monthStart));

            $income = Transaction::where('type', 'income')
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $expense = Transaction::where('type', 'expense')
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $monthlyData[] = [
                'month' => date('M', strtotime($monthStart)),
                'income' => (float) $income,
                'expense' => (float) $expense,
                'profit' => (float) ($income - $expense),
            ];
        }

        return view('reports.income_expense', compact('year', 'monthlyData'));
    }

    public function taxSummary(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->input('end_date', now()->endOfYear()->toDateString());

        $collectedTax = Invoice::whereBetween('invoice_date', [$startDate, $endDate])->sum('tax_total');
        $paidTax = Bill::whereBetween('bill_date', [$startDate, $endDate])->sum('tax_total');
        $netTaxDue = $collectedTax - $paidTax;

        $taxes = Tax::all();

        return view('reports.tax_summary', compact('startDate', 'endDate', 'collectedTax', 'paidTax', 'netTaxDue', 'taxes'));
    }

    public function arAging()
    {
        $today = Carbon::today();

        $invoices = Invoice::where('due_amount', '>', 0)
            ->with('customer')
            ->get();

        $buckets = ['current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, '90_plus' => 0];
        $customerBreakdown = [];

        foreach ($invoices as $invoice) {
            $customerName = $invoice->customer->name ?? 'Unknown Customer';
            $customerId = $invoice->customer_id;
            $dueDate = Carbon::parse($invoice->due_date);
            $daysOverdue = $dueDate->isPast() ? $today->diffInDays($dueDate) : 0;
            $amount = (float) $invoice->due_amount;

            if (!$dueDate->isPast()) {
                $bucket = 'current';
            } elseif ($daysOverdue <= 30) {
                $bucket = '1_30';
            } elseif ($daysOverdue <= 60) {
                $bucket = '31_60';
            } elseif ($daysOverdue <= 90) {
                $bucket = '61_90';
            } else {
                $bucket = '90_plus';
            }

            $buckets[$bucket] += $amount;

            if (!isset($customerBreakdown[$customerId])) {
                $customerBreakdown[$customerId] = [
                    'name' => $customerName,
                    'current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, '90_plus' => 0, 'total' => 0,
                ];
            }
            $customerBreakdown[$customerId][$bucket] += $amount;
            $customerBreakdown[$customerId]['total'] += $amount;
        }

        $grandTotal = array_sum($buckets);

        // Sort by total descending
        uasort($customerBreakdown, fn($a, $b) => $b['total'] <=> $a['total']);

        return view('reports.ar_aging', compact('buckets', 'customerBreakdown', 'grandTotal', 'today'));
    }

    public function apAging()
    {
        $today = Carbon::today();

        $bills = Bill::where('due_amount', '>', 0)
            ->with('vendor')
            ->get();

        $buckets = ['current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, '90_plus' => 0];
        $vendorBreakdown = [];

        foreach ($bills as $bill) {
            $vendorName = $bill->vendor->name ?? 'Unknown Vendor';
            $vendorId = $bill->vendor_id;
            $dueDate = Carbon::parse($bill->due_date);
            $daysOverdue = $dueDate->isPast() ? $today->diffInDays($dueDate) : 0;
            $amount = (float) $bill->due_amount;

            if (!$dueDate->isPast()) {
                $bucket = 'current';
            } elseif ($daysOverdue <= 30) {
                $bucket = '1_30';
            } elseif ($daysOverdue <= 60) {
                $bucket = '31_60';
            } elseif ($daysOverdue <= 90) {
                $bucket = '61_90';
            } else {
                $bucket = '90_plus';
            }

            $buckets[$bucket] += $amount;

            if (!isset($vendorBreakdown[$vendorId])) {
                $vendorBreakdown[$vendorId] = [
                    'name' => $vendorName,
                    'current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, '90_plus' => 0, 'total' => 0,
                ];
            }
            $vendorBreakdown[$vendorId][$bucket] += $amount;
            $vendorBreakdown[$vendorId]['total'] += $amount;
        }

        $grandTotal = array_sum($buckets);

        uasort($vendorBreakdown, fn($a, $b) => $b['total'] <=> $a['total']);

        return view('reports.ap_aging', compact('buckets', 'vendorBreakdown', 'grandTotal', 'today'));
    }

    public function balanceSheet(Request $request, \App\Services\Accounting\LedgerReportService $ledger)
    {
        $asOfDate = $request->input('as_of', now()->toDateString());

        // Derived from the double-entry ledger (accrual) with cash from the bank
        // accounts and owner's equity as the residual — balances by construction.
        return view('reports.balance_sheet', $ledger->balanceSheet($asOfDate));
    }

    public function trialBalance(Request $request, \App\Services\Accounting\LedgerReportService $ledger)
    {
        $asOfDate = $request->input('as_of', now()->toDateString());
        return view('reports.trial_balance', $ledger->trialBalance($asOfDate));
    }

    public function exportTrialBalance(Request $request, \App\Services\Accounting\LedgerReportService $ledger)
    {
        $tb = $ledger->trialBalance($request->input('as_of', now()->toDateString()));

        $headers = ['Code', 'Account', 'Type', 'Debit', 'Credit'];
        $rows = [];
        foreach ($tb['accounts'] as $a) {
            $rows[] = [
                $a['code'], $a['name'], ucfirst($a['type']),
                $a['debit'] > 0 ? number_format($a['debit'], 2) : '',
                $a['credit'] > 0 ? number_format($a['credit'], 2) : '',
            ];
        }
        $rows[] = ['', 'TOTAL', '', number_format($tb['totalDebit'], 2), number_format($tb['totalCredit'], 2)];

        return $this->buildCsvResponse('trial_balance.csv', $headers, $rows);
    }

    public function generalLedger(Request $request, \App\Services\Accounting\LedgerReportService $ledger)
    {
        $code = (string) $request->input('account', '');
        $start = $request->input('start_date');
        $end = $request->input('end_date', now()->toDateString());

        $gl = $code !== '' ? $ledger->generalLedger($code, $start, $end) : null;
        if ($gl === null) {
            abort(404, 'Unknown ledger account.');
        }

        return view('reports.general_ledger', $gl);
    }

    public function exportGeneralLedger(Request $request, \App\Services\Accounting\LedgerReportService $ledger)
    {
        $code = (string) $request->input('account', '');
        $gl = $code !== '' ? $ledger->generalLedger($code, $request->input('start_date'), $request->input('end_date', now()->toDateString())) : null;
        if ($gl === null) {
            abort(404, 'Unknown ledger account.');
        }

        $headers = ['Date', 'Entry', 'Description', 'Reference', 'Debit', 'Credit', 'Balance'];
        $rows = [];
        if ($gl['startDate']) {
            $rows[] = ['', '', 'Opening Balance', '', '', '', number_format($gl['opening'], 2)];
        }
        foreach ($gl['lines'] as $l) {
            $rows[] = [
                $l['date'], $l['entry_number'], $l['description'], $l['reference'] ?? '',
                $l['debit'] > 0 ? number_format($l['debit'], 2) : '',
                $l['credit'] > 0 ? number_format($l['credit'], 2) : '',
                number_format($l['balance'], 2),
            ];
        }
        $rows[] = ['', '', 'TOTAL', '', number_format($gl['totalDebit'], 2), number_format($gl['totalCredit'], 2), number_format($gl['closing'], 2)];

        $filename = 'general_ledger_' . $gl['account']->code . '.csv';
        return $this->buildCsvResponse($filename, $headers, $rows);
    }

    public function exportProfitLoss(Request $request, \App\Services\Accounting\LedgerReportService $ledger)
    {
        $startDate = $request->input('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->input('end_date', now()->endOfYear()->toDateString());

        $pl = $ledger->profitLoss($startDate, $endDate);

        $grossSales = (float) Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'partial', 'sent', 'viewed'])->sum('total');
        $grossBills = (float) Bill::whereBetween('bill_date', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'partial', 'received'])->sum('total');
        $cashIncome = (float) Transaction::where('type', 'income')
            ->whereBetween('transaction_date', [$startDate, $endDate])->sum('amount');
        $cashExpense = (float) Transaction::where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate])->sum('amount');

        $expensesByCategory = Category::where('type', 'expense')
            ->withSum(['transactions' => fn($q) => $q->whereBetween('transaction_date', [$startDate, $endDate])], 'amount')
            ->get();

        $headers = ['Category', 'Amount'];
        $rows = [];
        $rows[] = ['--- REVENUE ---', ''];
        $rows[] = ['Gross Sales & Invoiced Billings (incl. GST)', number_format($grossSales, 2)];
        $rows[] = ['Total Revenue (Accrual, excl. GST)', number_format($pl['netRevenue'], 2)];
        $rows[] = ['Cash Collections Received', number_format($cashIncome, 2)];
        $rows[] = ['', ''];
        $rows[] = ['--- EXPENSES ---', ''];
        foreach ($expensesByCategory as $ec) {
            $rows[] = [$ec->name, number_format($ec->transactions_sum_amount ?? 0, 2)];
        }
        $rows[] = ['Gross Vendor Bills (incl. GST)', number_format($grossBills, 2)];
        $rows[] = ['Total Operating Expenses (Accrual, excl. GST)', number_format($pl['netExpense'], 2)];
        $rows[] = ['Total Cash Expenses', number_format($cashExpense, 2)];
        $rows[] = ['', ''];
        $rows[] = ['Net Income (Accrual)', number_format($pl['netProfit'], 2)];
        $rows[] = ['Net Cash Flow', number_format($cashIncome - $cashExpense, 2)];

        return $this->buildCsvResponse('profit_loss.csv', $headers, $rows);
    }

    public function exportArAging()
    {
        $today = Carbon::today();
        $invoices = Invoice::where('due_amount', '>', 0)->with('customer')->get();

        $customerBreakdown = [];
        foreach ($invoices as $invoice) {
            $customerName = $invoice->customer->name ?? 'Unknown';
            $customerId = $invoice->customer_id;
            $dueDate = Carbon::parse($invoice->due_date);
            $daysOverdue = $dueDate->isPast() ? $today->diffInDays($dueDate) : 0;
            $amount = (float) $invoice->due_amount;

            if (!$dueDate->isPast()) { $bucket = 'current'; }
            elseif ($daysOverdue <= 30) { $bucket = '1_30'; }
            elseif ($daysOverdue <= 60) { $bucket = '31_60'; }
            elseif ($daysOverdue <= 90) { $bucket = '61_90'; }
            else { $bucket = '90_plus'; }

            if (!isset($customerBreakdown[$customerId])) {
                $customerBreakdown[$customerId] = ['name' => $customerName, 'current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, '90_plus' => 0, 'total' => 0];
            }
            $customerBreakdown[$customerId][$bucket] += $amount;
            $customerBreakdown[$customerId]['total'] += $amount;
        }

        $headers = ['Customer', 'Current', '1-30 Days', '31-60 Days', '61-90 Days', '90+ Days', 'Total'];
        $rows = [];
        foreach ($customerBreakdown as $row) {
            $rows[] = [
                $row['name'],
                number_format($row['current'], 2),
                number_format($row['1_30'], 2),
                number_format($row['31_60'], 2),
                number_format($row['61_90'], 2),
                number_format($row['90_plus'], 2),
                number_format($row['total'], 2),
            ];
        }

        return $this->buildCsvResponse('ar_aging.csv', $headers, $rows);
    }

    public function exportApAging()
    {
        $today = Carbon::today();
        $bills = Bill::where('due_amount', '>', 0)->with('vendor')->get();

        $vendorBreakdown = [];
        foreach ($bills as $bill) {
            $vendorName = $bill->vendor->name ?? 'Unknown';
            $vendorId = $bill->vendor_id;
            $dueDate = Carbon::parse($bill->due_date);
            $daysOverdue = $dueDate->isPast() ? $today->diffInDays($dueDate) : 0;
            $amount = (float) $bill->due_amount;

            if (!$dueDate->isPast()) { $bucket = 'current'; }
            elseif ($daysOverdue <= 30) { $bucket = '1_30'; }
            elseif ($daysOverdue <= 60) { $bucket = '31_60'; }
            elseif ($daysOverdue <= 90) { $bucket = '61_90'; }
            else { $bucket = '90_plus'; }

            if (!isset($vendorBreakdown[$vendorId])) {
                $vendorBreakdown[$vendorId] = ['name' => $vendorName, 'current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, '90_plus' => 0, 'total' => 0];
            }
            $vendorBreakdown[$vendorId][$bucket] += $amount;
            $vendorBreakdown[$vendorId]['total'] += $amount;
        }

        $headers = ['Vendor', 'Current', '1-30 Days', '31-60 Days', '61-90 Days', '90+ Days', 'Total'];
        $rows = [];
        foreach ($vendorBreakdown as $row) {
            $rows[] = [
                $row['name'],
                number_format($row['current'], 2),
                number_format($row['1_30'], 2),
                number_format($row['31_60'], 2),
                number_format($row['61_90'], 2),
                number_format($row['90_plus'], 2),
                number_format($row['total'], 2),
            ];
        }

        return $this->buildCsvResponse('ap_aging.csv', $headers, $rows);
    }

    public function exportBalanceSheet(Request $request, \App\Services\Accounting\LedgerReportService $ledger)
    {
        $bs = $ledger->balanceSheet($request->input('as_of', now()->toDateString()));

        $headers = ['Account', 'Amount'];
        $rows = [];
        $rows[] = ['--- ASSETS ---', ''];
        foreach ($bs['bankAccounts'] as $acc) {
            $rows[] = [$acc->name . ' (' . ($acc->bank_name ?? $acc->type) . ')', number_format($acc->current_balance, 2)];
        }
        $rows[] = ['Total Cash & Bank', number_format($bs['totalCashBank'], 2)];
        $rows[] = ['Accounts Receivable', number_format($bs['accountsReceivable'], 2)];
        $rows[] = ['Total Assets', number_format($bs['totalAssets'], 2)];
        $rows[] = ['', ''];
        $rows[] = ['--- LIABILITIES ---', ''];
        $rows[] = ['Accounts Payable', number_format($bs['accountsPayable'], 2)];
        $rows[] = ['GST Payable (net)', number_format($bs['gstPayable'], 2)];
        $rows[] = ['Total Liabilities', number_format($bs['totalLiabilities'], 2)];
        $rows[] = ['', ''];
        $rows[] = ['--- EQUITY ---', ''];
        $rows[] = ["Owner's Equity", number_format($bs['ownersEquity'], 2)];
        $rows[] = ['Opening Balance Equity', number_format($bs['openingBalanceEquity'], 2)];
        $rows[] = ['Retained Earnings', number_format($bs['retainedEarnings'], 2)];
        $rows[] = ['Total Equity', number_format($bs['totalEquity'], 2)];
        $rows[] = ['', ''];
        $rows[] = ['Total Liabilities + Equity', number_format($bs['totalLiabilitiesAndEquity'], 2)];

        return $this->buildCsvResponse('balance_sheet.csv', $headers, $rows);
    }

    public function exportIncomeExpense(Request $request)
    {
        $year = $request->input('year', now()->year);

        $headers = ['Month', 'Income', 'Expense', 'Net Flow'];
        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthStart = sprintf('%04d-%02d-01', $year, $m);
            $monthEnd = date('Y-m-t', strtotime($monthStart));
            $income = (float) Transaction::where('type', 'income')->whereBetween('transaction_date', [$monthStart, $monthEnd])->sum('amount');
            $expense = (float) Transaction::where('type', 'expense')->whereBetween('transaction_date', [$monthStart, $monthEnd])->sum('amount');
            $rows[] = [
                date('M', strtotime($monthStart)) . ' ' . $year,
                number_format($income, 2),
                number_format($expense, 2),
                number_format($income - $expense, 2),
            ];
        }

        return $this->buildCsvResponse('income_expense.csv', $headers, $rows);
    }

    public function exportTaxSummary(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->input('end_date', now()->endOfYear()->toDateString());

        $collectedTax = Invoice::whereBetween('invoice_date', [$startDate, $endDate])->sum('tax_total');
        $paidTax = Bill::whereBetween('bill_date', [$startDate, $endDate])->sum('tax_total');

        $headers = ['Description', 'Amount'];
        $rows = [
            ['Output Tax Collected (Sales)', number_format($collectedTax, 2)],
            ['Input Tax Paid (Purchases)', number_format($paidTax, 2)],
            ['Net Tax Liability Due', number_format($collectedTax - $paidTax, 2)],
        ];

        return $this->buildCsvResponse('tax_summary.csv', $headers, $rows);
    }

    private function getGstF5Data(int $quarter, int $year): array
    {
        $quarterMonths = [1 => [1, 3], 2 => [4, 6], 3 => [7, 9], 4 => [10, 12]];
        [$startMonth, $endMonth] = $quarterMonths[$quarter];

        $startDate = sprintf('%04d-%02d-01', $year, $startMonth);
        $endDate = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $endMonth)));

        // Box 1: Standard-rated supplies (invoices with tax > 0)
        $box1 = (float) Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->where('tax_total', '>', 0)
            ->sum('subtotal');

        // Box 2: Zero-rated supplies (invoices with tax_total = 0)
        $box2 = (float) Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->where('tax_total', '=', 0)
            ->sum('subtotal');

        // Box 3: Exempt supplies
        $box3 = 0.00;

        // Box 4: Total supplies
        $box4 = $box1 + $box2 + $box3;

        // Box 5: Total value of taxable purchases
        $box5 = (float) Bill::whereBetween('bill_date', [$startDate, $endDate])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->sum('subtotal');

        // Box 6: Output tax due
        $box6 = (float) Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->sum('tax_total');

        // Box 7: Input tax and refunds claimed
        $box7 = (float) Bill::whereBetween('bill_date', [$startDate, $endDate])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->sum('tax_total');

        // Box 8: Net GST
        $box8 = $box6 - $box7;

        // Box 9: Imports under deferment scheme
        $box9 = 0.00;

        return compact('quarter', 'year', 'startDate', 'endDate',
            'box1', 'box2', 'box3', 'box4', 'box5',
            'box6', 'box7', 'box8', 'box9');
    }

    public function gstF5(Request $request)
    {
        $currentMonth = (int) now()->month;
        $defaultQuarter = (int) ceil($currentMonth / 3);

        $quarter = (int) $request->input('quarter', $defaultQuarter);
        $year = (int) $request->input('year', now()->year);
        $quarter = max(1, min(4, $quarter));

        $data = $this->getGstF5Data($quarter, $year);

        return view('reports.gst_f5', $data);
    }

    public function exportGstF5(Request $request)
    {
        $currentMonth = (int) now()->month;
        $defaultQuarter = (int) ceil($currentMonth / 3);

        $quarter = (int) $request->input('quarter', $defaultQuarter);
        $year = (int) $request->input('year', now()->year);
        $quarter = max(1, min(4, $quarter));

        $data = $this->getGstF5Data($quarter, $year);

        $headers = ['Box', 'Description', 'Amount (SGD)'];
        $rows = [
            ['1', 'Total value of standard-rated supplies', number_format($data['box1'], 2)],
            ['2', 'Total value of zero-rated supplies', number_format($data['box2'], 2)],
            ['3', 'Total value of exempt supplies', number_format($data['box3'], 2)],
            ['4', 'Total value of supplies (Box 1 + 2 + 3)', number_format($data['box4'], 2)],
            ['5', 'Total value of taxable purchases', number_format($data['box5'], 2)],
            ['6', 'Output tax due', number_format($data['box6'], 2)],
            ['7', 'Input tax and refunds claimed', number_format($data['box7'], 2)],
            ['8', 'Net GST to be paid to / claimed from IRAS (Box 6 - Box 7)', number_format($data['box8'], 2)],
            ['9', 'Total value of goods imported under MES / Other Schemes', number_format($data['box9'], 2)],
        ];

        $filename = sprintf('gst_f5_q%d_%d.csv', $data['quarter'], $data['year']);

        return $this->buildCsvResponse($filename, $headers, $rows);
    }

    private function buildCsvResponse(string $filename, array $headers, array $rows): Response
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers, ',', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($output, $row, ',', '"', '\\');
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
