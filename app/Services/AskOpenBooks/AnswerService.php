<?php

namespace App\Services\AskOpenBooks;

use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\RecurringTemplate;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Deterministic answer engine for Ask OpenBooks.
 * Every figure is computed from the database — never from an LLM.
 */
class AnswerService
{
    const INVOICE_ACTIVE = ['paid', 'partial', 'sent', 'viewed'];
    const BILL_ACTIVE = ['paid', 'partial', 'received'];

    protected string $symbol;

    public function __construct()
    {
        $this->symbol = \App\Models\Company::first()?->currency_symbol ?? 'S$';
    }

    /**
     * Run a catalog handler and return a structured answer payload.
     */
    public function answer(string $key, ?string $askedQuestion = null): array
    {
        $entry = QuestionCatalog::find($key);
        $handler = 'handle' . str_replace('_', '', ucwords($key, '_'));

        $payload = $this->{$handler}();

        return array_merge([
            'intent' => $key,
            'question' => $askedQuestion ?: $entry['label'],
            'label' => $entry['label'],
            'category' => $entry['category'],
            'figure' => null,
            'format' => 'currency',
            'compare' => null,
            'breakdown' => null,
            'chart' => null,
            'caveats' => [],
            'links' => $entry['link'] ? [[
                'label' => $entry['link']['label'],
                'url' => route($entry['link']['route']),
            ]] : [],
        ], $payload, [
            'explain' => $payload['explain'] ?? 'Computed live from your OpenBooks data at ' . now()->format('j M Y, g:i a') . '.',
            'drill_available' => $this->supportsDrill($key),
            'followups' => $this->followupsFor($key),
        ]);
    }

    /**
     * Related questions offered as one-click follow-ups on an answer.
     */
    const FOLLOWUPS = [
        'cash_position' => ['ar_forecast', 'ap_overdue'],
        'unreconciled' => ['cash_position'],
        'ar_summary' => ['ar_overdue', 'dso_average'],
        'ar_overdue' => ['ar_summary', 'dso_average'],
        'ar_forecast' => ['ar_overdue', 'cash_position'],
        'dso_average' => ['ar_overdue', 'ar_summary'],
        'ap_summary' => ['ap_overdue', 'cash_position'],
        'ap_overdue' => ['ap_summary', 'cash_position'],
        'profit_summary' => ['revenue_trend', 'expense_breakdown'],
        'revenue_trend' => ['top_customers', 'profit_summary'],
        'top_customers' => ['revenue_trend', 'ar_summary'],
        'top_items' => ['top_customers', 'revenue_trend'],
        'expense_breakdown' => ['budget_variance', 'profit_summary'],
        'gst_position' => ['profit_summary', 'ap_summary'],
        'low_stock' => ['top_items', 'expense_breakdown'],
        'budget_variance' => ['expense_breakdown', 'profit_summary'],
        'upcoming_recurring' => ['ar_forecast', 'ap_overdue'],
    ];

    protected function followupsFor(string $key): array
    {
        return collect(self::FOLLOWUPS[$key] ?? [])
            ->map(fn ($k) => ['key' => $k, 'label' => QuestionCatalog::find($k)['label']])
            ->values()->all();
    }

    public function supportsDrill(string $key): bool
    {
        return in_array($key, ['ar_summary', 'ar_overdue', 'ap_summary', 'ap_overdue', 'profit_summary', 'expense_breakdown', 'gst_position', 'top_customers'], true);
    }

    /**
     * Row-level detail behind a headline answer ("why? show me the documents").
     */
    public function drill(string $key): array
    {
        $cap = 25;
        $entry = QuestionCatalog::find($key);

        switch ($key) {
            case 'ar_summary':
            case 'ar_overdue':
                $q = Invoice::with('customer')->whereIn('status', self::INVOICE_ACTIVE)->where('due_amount', '>', 0);
                if ($key === 'ar_overdue') $q->whereDate('due_date', '<', today());
                $rows = $q->orderBy('due_date')->limit($cap)->get();
                return $this->drillPayload($entry, 'Open invoice documents behind the receivables figure', [
                    'columns' => ['Invoice', 'Customer', 'Issued', 'Due', 'Status', 'Amount due'],
                    'rows' => $rows->map(fn ($i) => [
                        $i->invoice_number, $i->customer->name ?? '--',
                        Carbon::parse($i->invoice_date)->format('j M Y'),
                        Carbon::parse($i->due_date)->format('j M Y'),
                        $i->status, $this->money((float) $i->due_amount),
                    ])->all(),
                ], $rows->count());

            case 'ap_summary':
            case 'ap_overdue':
                $q = Bill::with('vendor')->whereIn('status', self::BILL_ACTIVE)->where('due_amount', '>', 0);
                if ($key === 'ap_overdue') $q->whereDate('due_date', '<', today());
                $rows = $q->orderBy('due_date')->limit($cap)->get();
                return $this->drillPayload($entry, 'Open bill documents behind the payables figure', [
                    'columns' => ['Bill', 'Vendor', 'Received', 'Due', 'Status', 'Amount due'],
                    'rows' => $rows->map(fn ($b) => [
                        $b->bill_number, $b->vendor->name ?? '--',
                        Carbon::parse($b->bill_date)->format('j M Y'),
                        Carbon::parse($b->due_date)->format('j M Y'),
                        $b->status, $this->money((float) $b->due_amount),
                    ])->all(),
                ], $rows->count());

            case 'profit_summary':
                $range = [now()->startOfMonth(), now()->endOfMonth()];
                $invoices = Invoice::with('customer')->whereIn('status', self::INVOICE_ACTIVE)
                    ->whereBetween('invoice_date', $range)->limit($cap)->get()
                    ->map(fn ($i) => ['Income', $i->invoice_number, $i->customer->name ?? '--', Carbon::parse($i->invoice_date)->format('j M Y'), $this->money((float) $i->total)]);
                $bills = Bill::with('vendor')->whereIn('status', self::BILL_ACTIVE)
                    ->whereBetween('bill_date', $range)->limit($cap)->get()
                    ->map(fn ($b) => ['Expense', $b->bill_number, $b->vendor->name ?? '--', Carbon::parse($b->bill_date)->format('j M Y'), $this->money((float) $b->total)]);
                $rows = $invoices->concat($bills)->take($cap)->all();
                return $this->drillPayload($entry, 'Every invoice and bill counted in this month\'s profit', [
                    'columns' => ['Type', 'Number', 'Party', 'Date', 'Amount'],
                    'rows' => $rows,
                ], count($rows));

            case 'expense_breakdown':
                $rows = Transaction::with('category')->where('type', 'expense')
                    ->whereDate('transaction_date', '>=', now()->startOfYear())
                    ->orderByDesc('transaction_date')->limit($cap)->get();
                return $this->drillPayload($entry, 'Individual expense transactions recorded this year', [
                    'columns' => ['Date', 'Category', 'Description', 'Amount'],
                    'rows' => $rows->map(fn ($t) => [
                        Carbon::parse($t->transaction_date)->format('j M Y'),
                        $t->category->name ?? 'Uncategorised',
                        \Illuminate\Support\Str::limit($t->description ?? $t->reference_number ?? '--', 45),
                        $this->money((float) $t->amount),
                    ])->all(),
                ], $rows->count());

            case 'gst_position':
                $range = [now()->startOfQuarter(), now()->endOfQuarter()];
                $out = Invoice::with('customer')->whereIn('status', self::INVOICE_ACTIVE)
                    ->whereBetween('invoice_date', $range)->where('tax_total', '>', 0)->limit($cap)->get()
                    ->map(fn ($i) => ['Output (sale)', $i->invoice_number, $i->customer->name ?? '--', Carbon::parse($i->invoice_date)->format('j M Y'), $this->money((float) $i->tax_total)]);
                $in = Bill::with('vendor')->whereIn('status', self::BILL_ACTIVE)
                    ->whereBetween('bill_date', $range)->where('tax_total', '>', 0)->limit($cap)->get()
                    ->map(fn ($b) => ['Input (purchase)', $b->bill_number, $b->vendor->name ?? '--', Carbon::parse($b->bill_date)->format('j M Y'), $this->money((float) $b->tax_total)]);
                $rows = $out->concat($in)->take($cap)->all();
                return $this->drillPayload($entry, 'Documents carrying GST this quarter', [
                    'columns' => ['GST side', 'Number', 'Party', 'Date', 'GST amount'],
                    'rows' => $rows,
                ], count($rows));

            case 'top_customers':
                $since = now()->subMonthsNoOverflow(12)->startOfMonth();
                $rows = Invoice::with('customer')->whereIn('status', self::INVOICE_ACTIVE)
                    ->whereDate('invoice_date', '>=', $since)->orderByDesc('total')->limit($cap)->get();
                return $this->drillPayload($entry, 'Largest invoices in the last 12 months', [
                    'columns' => ['Invoice', 'Customer', 'Date', 'Total'],
                    'rows' => $rows->map(fn ($i) => [
                        $i->invoice_number, $i->customer->name ?? '--',
                        Carbon::parse($i->invoice_date)->format('j M Y'),
                        $this->money((float) $i->total),
                    ])->all(),
                ], $rows->count());
        }

        return $this->drillPayload($entry, 'No row-level detail available for this question.', ['columns' => [], 'rows' => []], 0);
    }

    protected function drillPayload(array $entry, string $headline, array $breakdown, int $count): array
    {
        return [
            'intent' => $entry['key'],
            'question' => 'Details behind: ' . $entry['label'],
            'label' => $entry['label'],
            'category' => $entry['category'],
            'drill' => true,
            'headline' => $headline . ' (' . $count . ' row(s), showing up to 25)',
            'figure' => null,
            'format' => 'currency',
            'compare' => null,
            'breakdown' => empty($breakdown['rows']) ? null : $breakdown,
            'chart' => null,
            'caveats' => $count > 25 ? ['More than 25 rows match — showing the first 25. Use the linked report for the full list.'] : [],
            'links' => $entry['link'] ? [[
                'label' => $entry['link']['label'],
                'url' => route($entry['link']['route']),
            ]] : [],
            'answer' => 'These are the individual documents behind the number you just saw.',
            'explain' => 'Row-level records pulled directly from your books, newest/soonest first.',
            'drill_available' => false,
            'followups' => $this->followupsFor($entry['key']),
        ];
    }

    // ── Cash & Bank ────────────────────────────────────────────────

    protected function handleCashPosition(): array
    {
        $accounts = BankAccount::orderBy('name')->get();
        $total = (float) $accounts->sum('current_balance');
        $negative = $accounts->filter(fn ($a) => (float) $a->current_balance < 0);

        return [
            'headline' => $this->money($total) . ' total cash across ' . $accounts->count() . ' bank account' . ($accounts->count() === 1 ? '' : 's'),
            'figure' => $total,
            'breakdown' => [
                'columns' => ['Account', 'Type', 'Balance'],
                'rows' => $accounts->map(fn ($a) => [
                    $a->name, $a->type ?? '--', $this->money((float) $a->current_balance),
                ])->all(),
            ],
            'chart' => [
                'type' => 'bar',
                'labels' => $accounts->pluck('name')->all(),
                'values' => $accounts->map(fn ($a) => (float) $a->current_balance)->all(),
            ],
            'caveats' => $negative->isNotEmpty()
                ? [$negative->count() . ' account(s) have a negative balance: ' . $negative->pluck('name')->implode(', ')]
                : [],
            'answer' => $accounts->count() === 0
                ? 'You have no bank accounts set up yet, so there is no cash position to report.'
                : 'You currently hold ' . $this->money($total) . ' across ' . $accounts->count() . ' bank account(s).'
                    . ($negative->isNotEmpty() ? ' Note that ' . $negative->pluck('name')->implode(' and ') . ' is in the negative.' : ''),
        ];
    }

    protected function handleUnreconciled(): array
    {
        $cutoff = Carbon::today()->subDays(30);
        $txns = Transaction::with('bankAccount')
            ->where('is_reconciled', false)
            ->whereDate('transaction_date', '<=', $cutoff)
            ->orderBy('transaction_date')
            ->limit(50)
            ->get();
        $total = (float) $txns->sum('amount');
        $allCount = Transaction::where('is_reconciled', false)->count();

        return [
            'headline' => $allCount . ' unreconciled transaction(s), ' . $txns->count() . ' of them older than 30 days',
            'figure' => $allCount,
            'format' => 'count',
            'breakdown' => $txns->isEmpty() ? null : [
                'columns' => ['Date', 'Account', 'Description', 'Amount'],
                'rows' => $txns->map(fn ($t) => [
                    Carbon::parse($t->transaction_date)->format('j M Y'),
                    $t->bankAccount->name ?? '--',
                    \Illuminate\Support\Str::limit($t->description ?? $t->reference_number ?? '--', 40),
                    $this->money((float) $t->amount),
                ])->all(),
            ],
            'answer' => $allCount === 0
                ? 'Great news — every transaction in your books is reconciled.'
                : 'You have ' . $allCount . ' unreconciled transaction(s) totalling ' . $this->money($total)
                    . ', of which ' . $txns->count() . ' are older than 30 days and should be reviewed first.',
            'explain' => 'Counts transactions flagged is_reconciled = false; the list shows those dated on or before ' . $cutoff->format('j M Y') . '.',
        ];
    }

    // ── Receivables ────────────────────────────────────────────────

    protected function handleArSummary(): array
    {
        $open = Invoice::with('customer')->whereIn('status', self::INVOICE_ACTIVE)
            ->where('due_amount', '>', 0)->get();
        $total = (float) $open->sum('due_amount');

        $byCustomer = $open->groupBy('customer_id')
            ->map(fn ($g) => [
                'name' => $g->first()->customer->name ?? 'Unknown',
                'total' => (float) $g->sum('due_amount'),
                'count' => $g->count(),
            ])
            ->sortByDesc('total')->values();

        return [
            'headline' => $this->money($total) . ' outstanding across ' . $open->count() . ' unpaid invoice(s)',
            'figure' => $total,
            'breakdown' => $byCustomer->isEmpty() ? null : [
                'columns' => ['Customer', 'Open invoices', 'Amount due'],
                'rows' => $byCustomer->take(10)->map(fn ($c) => [$c['name'], $c['count'], $this->money($c['total'])])->all(),
            ],
            'chart' => $byCustomer->isEmpty() ? null : [
                'type' => 'bar',
                'labels' => $byCustomer->take(6)->pluck('name')->all(),
                'values' => $byCustomer->take(6)->pluck('total')->all(),
            ],
            'answer' => $open->count() === 0
                ? 'Nothing is outstanding — every issued invoice is fully paid.'
                : 'Customers still owe you ' . $this->money($total) . ' across ' . $open->count() . ' invoice(s). '
                    . ($byCustomer->isNotEmpty() ? 'The largest exposure is ' . $byCustomer[0]['name'] . ' at ' . $this->money($byCustomer[0]['total']) . '.' : ''),
        ];
    }

    protected function handleArOverdue(): array
    {
        $today = Carbon::today();
        $overdue = Invoice::with('customer')->whereIn('status', self::INVOICE_ACTIVE)
            ->where('due_amount', '>', 0)->whereDate('due_date', '<', $today)
            ->orderBy('due_date')->get();
        $total = (float) $overdue->sum('due_amount');

        return [
            'headline' => $overdue->count() ? $this->money($total) . ' overdue across ' . $overdue->count() . ' invoice(s)' : 'No overdue invoices',
            'figure' => $total,
            'breakdown' => $overdue->isEmpty() ? null : [
                'columns' => ['Invoice', 'Customer', 'Due date', 'Days late', 'Amount due'],
                'rows' => $overdue->take(15)->map(fn ($i) => [
                    $i->invoice_number,
                    $i->customer->name ?? '--',
                    Carbon::parse($i->due_date)->format('j M Y'),
                    $today->diffInDays(Carbon::parse($i->due_date)),
                    $this->money((float) $i->due_amount),
                ])->all(),
            ],
            'answer' => $overdue->isEmpty()
                ? 'Nothing is overdue — every open invoice is still within its payment terms.'
                : $this->money($total) . ' is past due across ' . $overdue->count() . ' invoice(s). Oldest: '
                    . $overdue->first()->invoice_number . ' (' . $overdue->first()->customer?->name . '), '
                    . $today->diffInDays(Carbon::parse($overdue->first()->due_date)) . ' days late.',
        ];
    }

    protected function handleArForecast(): array
    {
        $today = Carbon::today();
        $open = Invoice::whereIn('status', self::INVOICE_ACTIVE)
            ->where('due_amount', '>', 0)->whereDate('due_date', '>=', $today)->get();

        $buckets = ['Next 7 days' => 0.0, '8-14 days' => 0.0, '15-30 days' => 0.0];
        foreach ($open as $i) {
            $days = $today->diffInDays(Carbon::parse($i->due_date));
            if ($days <= 7) $buckets['Next 7 days'] += (float) $i->due_amount;
            elseif ($days <= 14) $buckets['8-14 days'] += (float) $i->due_amount;
            else $buckets['15-30 days'] += (float) $i->due_amount;
        }
        $total = array_sum($buckets);

        return [
            'headline' => $this->money($total) . ' expected to collect in the next 30 days',
            'figure' => $total,
            'breakdown' => [
                'columns' => ['Window', 'Expected'],
                'rows' => collect($buckets)->map(fn ($v, $k) => [$k, $this->money($v)])->all(),
            ],
            'chart' => ['type' => 'bar', 'labels' => array_keys($buckets), 'values' => array_values($buckets)],
            'answer' => $total > 0
                ? 'Based on invoice due dates, ' . $this->money($total) . ' should land in your bank over the next 30 days — '
                    . $this->money($buckets['Next 7 days']) . ' of it within a week.'
                : 'No invoices fall due in the next 30 days, so nothing is scheduled to come in.',
            'explain' => 'Sums due_amount of open invoices by due_date window; assumes customers pay exactly on the due date.',
        ];
    }

    protected function handleDsoAverage(): array
    {
        $today = Carbon::today();
        $arBalance = (float) Invoice::whereIn('status', self::INVOICE_ACTIVE)->where('due_amount', '>', 0)->sum('due_amount');
        $revenue90 = (float) Invoice::whereIn('status', self::INVOICE_ACTIVE)
            ->whereBetween('invoice_date', [$today->copy()->subDays(90), $today])
            ->sum('total');

        $dso = $revenue90 > 0 ? (int) round(($arBalance / $revenue90) * 90) : null;

        return [
            'headline' => $dso === null ? 'Not enough invoicing history to estimate' : 'Customers take about ' . $dso . ' days to pay',
            'figure' => $dso,
            'format' => 'days',
            'breakdown' => [
                'columns' => ['Input', 'Value'],
                'rows' => [
                    ['Current receivables', $this->money($arBalance)],
                    ['Revenue invoiced in last 90 days', $this->money($revenue90)],
                    ['Formula', '(Receivables ÷ 90-day revenue) × 90'],
                ],
            ],
            'answer' => $dso === null
                ? 'There is not enough recent invoicing activity to estimate collection speed yet.'
                : 'On average it takes customers around ' . $dso . ' days to pay you. '
                    . ($dso > 30 ? 'That is slower than typical 30-day terms — worth tightening follow-ups.' : 'That is within healthy 30-day terms.'),
            'explain' => 'Uses the standard DSO formula: (accounts receivable ÷ revenue in the last 90 days) × 90.',
        ];
    }

    // ── Payables ───────────────────────────────────────────────────

    protected function handleApSummary(): array
    {
        $open = Bill::with('vendor')->whereIn('status', self::BILL_ACTIVE)
            ->where('due_amount', '>', 0)->get();
        $total = (float) $open->sum('due_amount');

        $byVendor = $open->groupBy('vendor_id')
            ->map(fn ($g) => [
                'name' => $g->first()->vendor->name ?? 'Unknown',
                'total' => (float) $g->sum('due_amount'),
                'count' => $g->count(),
            ])
            ->sortByDesc('total')->values();

        return [
            'headline' => $this->money($total) . ' payable across ' . $open->count() . ' unpaid bill(s)',
            'figure' => $total,
            'breakdown' => $byVendor->isEmpty() ? null : [
                'columns' => ['Vendor', 'Open bills', 'Amount due'],
                'rows' => $byVendor->take(10)->map(fn ($v) => [$v['name'], $v['count'], $this->money($v['total'])])->all(),
            ],
            'chart' => $byVendor->isEmpty() ? null : [
                'type' => 'bar',
                'labels' => $byVendor->take(6)->pluck('name')->all(),
                'values' => $byVendor->take(6)->pluck('total')->all(),
            ],
            'answer' => $open->count() === 0
                ? 'You have no unpaid bills — nothing is owed to vendors right now.'
                : 'You owe vendors ' . $this->money($total) . ' across ' . $open->count() . ' bill(s). '
                    . ($byVendor->isNotEmpty() ? 'Largest payable: ' . $byVendor[0]['name'] . ' at ' . $this->money($byVendor[0]['total']) . '.' : ''),
        ];
    }

    protected function handleApOverdue(): array
    {
        $today = Carbon::today();
        $week = $today->copy()->addDays(7);

        $overdue = Bill::with('vendor')->whereIn('status', self::BILL_ACTIVE)
            ->where('due_amount', '>', 0)->whereDate('due_date', '<', $today)->get();
        $dueSoon = Bill::with('vendor')->whereIn('status', self::BILL_ACTIVE)
            ->where('due_amount', '>', 0)->whereBetween('due_date', [$today, $week])->get();

        $rows = $overdue->map(fn ($b) => [
            $b->bill_number, $b->vendor->name ?? '--', Carbon::parse($b->due_date)->format('j M Y'),
            'Overdue ' . $today->diffInDays(Carbon::parse($b->due_date)) . 'd', $this->money((float) $b->due_amount),
        ])->concat($dueSoon->map(fn ($b) => [
            $b->bill_number, $b->vendor->name ?? '--', Carbon::parse($b->due_date)->format('j M Y'),
            'Due soon', $this->money((float) $b->due_amount),
        ]))->all();

        $total = (float) $overdue->sum('due_amount') + (float) $dueSoon->sum('due_amount');

        return [
            'headline' => $this->money((float) $overdue->sum('due_amount')) . ' overdue + ' . $this->money((float) $dueSoon->sum('due_amount')) . ' due this week',
            'figure' => $total,
            'breakdown' => empty($rows) ? null : [
                'columns' => ['Bill', 'Vendor', 'Due date', 'State', 'Amount due'],
                'rows' => array_slice($rows, 0, 15),
            ],
            'answer' => empty($rows)
                ? 'Nothing is overdue and no bills fall due this week — your payables are clear.'
                : 'You need ' . $this->money($total) . ' for payables: ' . $overdue->count() . ' overdue bill(s) and '
                    . $dueSoon->count() . ' due within 7 days.',
        ];
    }

    // ── Performance ────────────────────────────────────────────────

    protected function handleProfitSummary(): array
    {
        $thisMonth = [now()->startOfMonth(), now()->endOfMonth()];
        $lastMonth = [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()];

        $calc = function (array $range) {
            $income = (float) Invoice::whereIn('status', self::INVOICE_ACTIVE)->whereBetween('invoice_date', $range)->sum('total');
            $expense = (float) Bill::whereIn('status', self::BILL_ACTIVE)->whereBetween('bill_date', $range)->sum('total');
            return [$income, $expense, $income - $expense];
        };

        [$inc, $exp, $net] = $calc($thisMonth);
        [$pInc, $pExp, $pNet] = $calc($lastMonth);

        $delta = $pNet != 0 ? round((($net - $pNet) / abs($pNet)) * 100, 1) : null;

        return [
            'headline' => $this->money($net) . ' net profit this month',
            'figure' => $net,
            'compare' => [
                'label' => 'vs last month (' . $this->money($pNet) . ')',
                'delta_pct' => $delta,
                'text' => $delta === null ? 'no profit last month to compare' : ($delta >= 0 ? 'up ' . abs($delta) . '%' : 'down ' . abs($delta) . '%') . ' vs last month',
            ],
            'breakdown' => [
                'columns' => ['Line', 'This month', 'Last month'],
                'rows' => [
                    ['Invoiced income', $this->money($inc), $this->money($pInc)],
                    ['Billed expenses', $this->money($exp), $this->money($pExp)],
                    ['Net profit', $this->money($net), $this->money($pNet)],
                ],
            ],
            'answer' => 'This month you invoiced ' . $this->money($inc) . ' against ' . $this->money($exp) . ' of bills, leaving '
                . $this->money($net) . ' net profit' . ($delta !== null ? ' — ' . ($delta >= 0 ? 'up' : 'down') . ' ' . abs($delta) . '% on last month.' : '.'),
            'explain' => 'Accrual view: invoices issued this month minus bills received this month (drafts excluded), same basis as the Profit & Loss report.',
        ];
    }

    protected function handleRevenueTrend(): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = now()->subMonthsNoOverflow($i)->startOfMonth();
            $months[] = [
                'label' => $start->format('M Y'),
                'value' => (float) Invoice::whereIn('status', self::INVOICE_ACTIVE)
                    ->whereBetween('invoice_date', [$start, $start->copy()->endOfMonth()])->sum('total'),
            ];
        }

        $first = $months[0]['value'];
        $last = $months[5]['value'];
        $change = $first > 0 ? round((($last - $first) / $first) * 100, 1) : null;

        return [
            'headline' => $this->money($last) . ' invoiced in ' . $months[5]['label'],
            'figure' => $last,
            'compare' => $change === null ? null : [
                'label' => 'vs ' . $months[0]['label'],
                'delta_pct' => $change,
                'text' => ($change >= 0 ? 'up ' : 'down ') . abs($change) . '% over 6 months',
            ],
            'breakdown' => [
                'columns' => ['Month', 'Invoiced'],
                'rows' => array_map(fn ($m) => [$m['label'], $this->money($m['value'])], $months),
            ],
            'chart' => ['type' => 'line', 'labels' => array_column($months, 'label'), 'values' => array_column($months, 'value')],
            'answer' => 'Revenue moved from ' . $this->money($first) . ' in ' . $months[0]['label'] . ' to ' . $this->money($last)
                . ' in ' . $months[5]['label'] . ($change !== null ? ' (' . ($change >= 0 ? '+' : '-') . abs($change) . '%).' : '.'),
        ];
    }

    protected function handleTopCustomers(): array
    {
        $since = now()->subMonthsNoOverflow(12)->startOfMonth();
        $rows = Invoice::whereIn('status', self::INVOICE_ACTIVE)
            ->whereDate('invoice_date', '>=', $since)
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->whereNull('customers.deleted_at')
            ->groupBy('invoices.customer_id', 'customers.name')
            ->selectRaw('customers.name as name, SUM(invoices.total) as revenue, COUNT(*) as invoices_count')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        $totalAll = (float) Invoice::whereIn('status', self::INVOICE_ACTIVE)->whereDate('invoice_date', '>=', $since)->sum('total');

        return [
            'headline' => $rows->isEmpty() ? 'No invoiced revenue in the last 12 months' : $rows[0]->name . ' is your top customer',
            'figure' => $rows->isEmpty() ? null : (float) $rows[0]->revenue,
            'breakdown' => $rows->isEmpty() ? null : [
                'columns' => ['Customer', 'Invoices', 'Revenue', 'Share'],
                'rows' => $rows->map(fn ($r) => [
                    $r->name, $r->invoices_count, $this->money((float) $r->revenue),
                    $totalAll > 0 ? round(((float) $r->revenue / $totalAll) * 100, 1) . '%' : '--',
                ])->all(),
            ],
            'chart' => $rows->isEmpty() ? null : [
                'type' => 'bar', 'labels' => $rows->pluck('name')->all(), 'values' => $rows->map(fn ($r) => (float) $r->revenue)->all(),
            ],
            'answer' => $rows->isEmpty()
                ? 'There is no invoiced revenue in the last 12 months to rank customers by.'
                : 'Over the last 12 months your top customer is ' . $rows[0]->name . ' with ' . $this->money((float) $rows[0]->revenue)
                    . ($totalAll > 0 ? ' (' . round(((float) $rows[0]->revenue / $totalAll) * 100, 1) . '% of revenue).' : '.'),
        ];
    }

    protected function handleTopItems(): array
    {
        $since = now()->subMonthsNoOverflow(12)->startOfMonth();
        $rows = DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->whereNull('invoices.deleted_at')
            ->whereIn('invoices.status', self::INVOICE_ACTIVE)
            ->whereDate('invoices.invoice_date', '>=', $since)
            ->groupBy('invoice_items.name')
            ->selectRaw('invoice_items.name as name, SUM(invoice_items.quantity) as qty, SUM(invoice_items.total) as revenue')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        return [
            'headline' => $rows->isEmpty() ? 'No sales line items in the last 12 months' : $rows[0]->name . ' leads your sales',
            'figure' => $rows->isEmpty() ? null : (float) $rows[0]->revenue,
            'breakdown' => $rows->isEmpty() ? null : [
                'columns' => ['Product / Service', 'Qty sold', 'Revenue'],
                'rows' => $rows->map(fn ($r) => [$r->name, rtrim(rtrim(number_format((float) $r->qty, 2), '0'), '.'), $this->money((float) $r->revenue)])->all(),
            ],
            'chart' => $rows->isEmpty() ? null : [
                'type' => 'bar', 'labels' => $rows->pluck('name')->all(), 'values' => $rows->map(fn ($r) => (float) $r->revenue)->all(),
            ],
            'answer' => $rows->isEmpty()
                ? 'No invoice line items in the last 12 months, so there is no best-seller yet.'
                : 'Your best seller over the last 12 months is ' . $rows[0]->name . ' with ' . $this->money((float) $rows[0]->revenue) . ' invoiced.',
        ];
    }

    protected function handleExpenseBreakdown(): array
    {
        $start = now()->startOfYear();
        $txns = Transaction::where('type', 'expense')->whereDate('transaction_date', '>=', $start)->get();
        $total = (float) $txns->sum('amount');

        $byCat = $txns->groupBy(fn ($t) => $t->category_id ?? 0)
            ->map(fn ($g) => [
                'name' => $g->first()->category?->name ?? 'Uncategorised',
                'total' => (float) $g->sum('amount'),
            ])
            ->sortByDesc('total')->values();

        return [
            'headline' => $this->money($total) . ' spent since 1 Jan ' . now()->format('Y'),
            'figure' => $total,
            'breakdown' => $byCat->isEmpty() ? null : [
                'columns' => ['Category', 'Amount', 'Share'],
                'rows' => $byCat->take(10)->map(fn ($c) => [
                    $c['name'], $this->money($c['total']),
                    $total > 0 ? round(($c['total'] / $total) * 100, 1) . '%' : '--',
                ])->all(),
            ],
            'chart' => $byCat->isEmpty() ? null : [
                'type' => 'bar', 'labels' => $byCat->take(6)->pluck('name')->all(), 'values' => $byCat->take(6)->pluck('total')->all(),
            ],
            'answer' => $byCat->isEmpty()
                ? 'No expense transactions recorded this year yet.'
                : 'Since January you have spent ' . $this->money($total) . '. Biggest bucket: ' . $byCat[0]['name'] . ' at '
                    . $this->money($byCat[0]['total']) . ($total > 0 ? ' (' . round(($byCat[0]['total'] / $total) * 100, 1) . '% of spend).' : '.'),
            'explain' => 'Cash-basis expense transactions recorded this year, grouped by category (bills are not included here).',
        ];
    }

    // ── Tax & Compliance ───────────────────────────────────────────

    protected function handleGstPosition(): array
    {
        $qStart = now()->startOfQuarter();
        $qEnd = now()->endOfQuarter();

        $output = (float) Invoice::whereIn('status', self::INVOICE_ACTIVE)->whereBetween('invoice_date', [$qStart, $qEnd])->sum('tax_total');
        $input = (float) Bill::whereIn('status', self::BILL_ACTIVE)->whereBetween('bill_date', [$qStart, $qEnd])->sum('tax_total');
        $net = $output - $input;

        return [
            'headline' => $this->money(abs($net)) . ($net >= 0 ? ' payable to IRAS' : ' refundable / credit carried forward') . ' for Q' . now()->quarter,
            'figure' => $net,
            'breakdown' => [
                'columns' => ['GST line', 'Amount'],
                'rows' => [
                    ['Output tax collected (sales)', $this->money($output)],
                    ['Input tax paid (purchases)', $this->money($input)],
                    ['Net position', $this->money($net)],
                ],
            ],
            'answer' => 'For the current quarter you collected ' . $this->money($output) . ' of GST and paid ' . $this->money($input)
                . ', leaving ' . $this->money(abs($net)) . ($net >= 0 ? ' to remit to IRAS.' : ' as input-tax credit.'),
            'explain' => 'Quarter-to-date GST on active invoices (output) vs active bills (input), same basis as the GST F5 report.',
        ];
    }

    // ── Controls ───────────────────────────────────────────────────

    protected function handleLowStock(): array
    {
        $items = Item::where('is_active', true)->whereNotNull('reorder_level')
            ->whereColumn('stock_quantity', '<=', 'reorder_level')
            ->orderBy('stock_quantity')->get();

        return [
            'headline' => $items->count() ? $items->count() . ' item(s) at or below reorder level' : 'All stock levels are healthy',
            'figure' => $items->count(),
            'format' => 'count',
            'breakdown' => $items->isEmpty() ? null : [
                'columns' => ['Item', 'In stock', 'Reorder at'],
                'rows' => $items->take(15)->map(fn ($i) => [$i->name, (float) $i->stock_quantity, (float) $i->reorder_level])->all(),
            ],
            'answer' => $items->isEmpty()
                ? 'No tracked items are below their reorder level right now.'
                : $items->count() . ' item(s) need reordering, lowest being ' . $items->first()->name . ' with ' . (float) $items->first()->stock_quantity . ' left.',
        ];
    }

    protected function handleBudgetVariance(): array
    {
        $year = now()->year;
        $month = now()->month;
        $budgets = Budget::with('category')->where('year', $year)->where('month', $month)->get();

        if ($budgets->isEmpty()) {
            return [
                'headline' => 'No budget set for ' . now()->format('F Y'),
                'answer' => 'You have not set a budget for ' . now()->format('F Y') . ', so there is nothing to compare spending against.',
            ];
        }

        $rows = [];
        $over = 0;
        foreach ($budgets as $b) {
            $actual = (float) Transaction::where('type', 'expense')->where('category_id', $b->category_id)
                ->whereYear('transaction_date', $year)->whereMonth('transaction_date', $month)->sum('amount');
            $budget = (float) $b->amount;
            $rows[] = [$b->category->name ?? 'Category #' . $b->category_id, $this->money($budget), $this->money($actual),
                $actual > $budget ? 'OVER by ' . $this->money($actual - $budget) : 'under by ' . $this->money($budget - $actual)];
            if ($actual > $budget) $over++;
        }

        return [
            'headline' => $over ? $over . ' of ' . $budgets->count() . ' budget(s) overspent this month' : 'All budgets on track this month',
            'figure' => $over,
            'format' => 'count',
            'breakdown' => ['columns' => ['Category', 'Budget', 'Actual', 'Variance'], 'rows' => $rows],
            'answer' => $over
                ? $over . ' categor(ies) have overspent their ' . now()->format('F') . ' budget — see the variance table.'
                : 'Every budgeted category is within its limit for ' . now()->format('F Y') . '.',
        ];
    }

    protected function handleUpcomingRecurring(): array
    {
        $week = now()->addDays(7);
        $templates = RecurringTemplate::with(['customer', 'vendor'])
            ->where('is_active', true)->whereDate('next_due_date', '<=', $week)
            ->orderBy('next_due_date')->get();

        $total = (float) $templates->sum('total');

        return [
            'headline' => $templates->count() ? $templates->count() . ' recurring doc(s) will generate by ' . $week->format('j M') . ' (' . $this->money($total) . ')' : 'Nothing recurring due next week',
            'figure' => $templates->count(),
            'format' => 'count',
            'breakdown' => $templates->isEmpty() ? null : [
                'columns' => ['Type', 'Party', 'Next run', 'Amount'],
                'rows' => $templates->map(fn ($t) => [
                    ucfirst($t->type),
                    $t->customer->name ?? $t->vendor->name ?? '--',
                    $t->next_due_date?->format('j M Y'),
                    $this->money((float) $t->total),
                ])->all(),
            ],
            'answer' => $templates->isEmpty()
                ? 'No recurring invoices or bills are scheduled to generate in the next 7 days.'
                : $templates->count() . ' recurring document(s) worth ' . $this->money($total) . ' will auto-generate within 7 days.',
        ];
    }

    // ── Helpers ────────────────────────────────────────────────────

    protected function money(float $value): string
    {
        return $this->symbol . number_format($value, 2);
    }
}
