<?php

namespace App\Services\AskOpenBooks;

/**
 * Catalog of predefined questions the Ask OpenBooks engine can answer.
 * Every entry maps to a deterministic handler in AnswerService — the LLM
 * (when configured) may only pick a key from this catalog, never invent one.
 */
class QuestionCatalog
{
    public static function all(): array
    {
        return [
            // ── Cash & Bank ──
            [
                'key' => 'cash_position',
                'category' => 'Cash & Bank',
                'label' => 'How much cash do I have across all bank accounts?',
                'keywords' => ['cash', 'bank balance', 'bank accounts', 'money do i have', 'liquidity', 'how much money'],
                'link' => ['label' => 'Open Banking & Cash', 'route' => 'banking.index'],
            ],
            [
                'key' => 'unreconciled',
                'category' => 'Cash & Bank',
                'label' => 'Which transactions are still unreconciled?',
                'keywords' => ['unreconciled', 'not reconciled', 'reconciliation', 'reconcile', 'matching transactions'],
                'link' => ['label' => 'Open Reconciliation', 'route' => 'banking.transactions'],
            ],

            // ── Receivables ──
            [
                'key' => 'ar_summary',
                'category' => 'Receivables',
                'label' => 'Who owes me money and how much?',
                'keywords' => ['owes me', 'owed to me', 'receivable', 'outstanding invoices', 'unpaid invoices', 'collect from customers', 'who owes'],
                'link' => ['label' => 'Open AR Aging', 'route' => 'reports.ar_aging'],
            ],
            [
                'key' => 'ar_overdue',
                'category' => 'Receivables',
                'label' => 'Which invoices are overdue and by how many days?',
                'keywords' => ['overdue invoice', 'late payment', 'overdue', 'past due', 'chasing payment'],
                'link' => ['label' => 'Open AR Aging', 'route' => 'reports.ar_aging'],
            ],
            [
                'key' => 'ar_forecast',
                'category' => 'Receivables',
                'label' => 'How much should I collect in the next 30 days?',
                'keywords' => ['collect in the next', 'coming in', 'expected payment', 'collections next', 'cash coming', 'next 30 days'],
                'link' => ['label' => 'Open Invoices', 'route' => 'invoices.index'],
            ],
            [
                'key' => 'dso_average',
                'category' => 'Receivables',
                'label' => 'On average, how many days do customers take to pay?',
                'keywords' => ['days to pay', 'how fast', 'payment speed', 'average days', 'dso', 'take to pay', 'slow payers'],
                'link' => ['label' => 'Open AR Aging', 'route' => 'reports.ar_aging'],
            ],

            // ── Payables ──
            [
                'key' => 'ap_summary',
                'category' => 'Payables',
                'label' => 'How much do I owe my vendors?',
                'keywords' => ['i owe', 'payable', 'owe vendors', 'owe suppliers', 'unpaid bills', 'outstanding bills', 'how much do i owe'],
                'link' => ['label' => 'Open AP Aging', 'route' => 'reports.ap_aging'],
            ],
            [
                'key' => 'ap_overdue',
                'category' => 'Payables',
                'label' => 'Which bills are overdue or due this week?',
                'keywords' => ['overdue bill', 'bills due', 'due this week', 'late bills', 'pay soon', 'upcoming payments'],
                'link' => ['label' => 'Open AP Aging', 'route' => 'reports.ap_aging'],
            ],

            // ── Performance ──
            [
                'key' => 'profit_summary',
                'category' => 'Performance',
                'label' => 'What is my profit this month compared to last month?',
                'keywords' => ['profit', 'net profit', 'making money', 'earnings', 'margin', 'profit this month'],
                'link' => ['label' => 'Open Profit & Loss', 'route' => 'reports.profit_loss'],
            ],
            [
                'key' => 'revenue_trend',
                'category' => 'Performance',
                'label' => 'How has my revenue trended over the last 6 months?',
                'keywords' => ['revenue trend', 'sales trend', 'revenue over', 'last 6 months', 'growth', 'revenue going'],
                'link' => ['label' => 'Open Income & Expense', 'route' => 'reports.income_expense'],
            ],
            [
                'key' => 'top_customers',
                'category' => 'Performance',
                'label' => 'Who are my top customers by revenue?',
                'keywords' => ['top customer', 'best customer', 'biggest client', 'which customer', 'customer revenue'],
                'link' => ['label' => 'Open Customers', 'route' => 'customers.index'],
            ],
            [
                'key' => 'top_items',
                'category' => 'Performance',
                'label' => 'What are my best selling products or services?',
                'keywords' => ['best selling', 'top product', 'top service', 'top item', 'sells most', 'popular item'],
                'link' => ['label' => 'Open Products & Services', 'route' => 'items.index'],
            ],
            [
                'key' => 'expense_breakdown',
                'category' => 'Performance',
                'label' => 'Where is my money going? Show expenses by category.',
                'keywords' => ['where is my money', 'expense by category', 'spending', 'expenses', 'cost breakdown', 'money going'],
                'link' => ['label' => 'Open Income & Expense', 'route' => 'reports.income_expense'],
            ],

            // ── Tax & Compliance ──
            [
                'key' => 'gst_position',
                'category' => 'Tax & Compliance',
                'label' => 'What is my GST position for this quarter (collected vs paid)?',
                'keywords' => ['gst', 'f5', 'output tax', 'input tax', 'tax quarter', 'iras', 'gst position'],
                'link' => ['label' => 'Open GST F5 Report', 'route' => 'reports.gst_f5'],
            ],

            // ── Controls ──
            [
                'key' => 'low_stock',
                'category' => 'Controls',
                'label' => 'Which items are low on stock?',
                'keywords' => ['low stock', 'stock level', 'reorder', 'inventory low', 'out of stock', 'running low'],
                'link' => ['label' => 'Open Inventory', 'route' => 'items.index'],
            ],
            [
                'key' => 'budget_variance',
                'category' => 'Controls',
                'label' => 'Am I over budget this month?',
                'keywords' => ['budget', 'over budget', 'overspend', 'budget vs actual', 'within budget'],
                'link' => ['label' => 'Open Budgets', 'route' => 'budgets.index'],
            ],
            [
                'key' => 'upcoming_recurring',
                'category' => 'Controls',
                'label' => 'What recurring invoices or bills are due next week?',
                'keywords' => ['recurring', 'subscription', 'repeat invoice', 'next week', 'scheduled', 'auto generate'],
                'link' => ['label' => 'Open Recurring', 'route' => 'recurring.index'],
            ],
        ];
    }

    public static function find(string $key): ?array
    {
        foreach (self::all() as $q) {
            if ($q['key'] === $key) {
                return $q;
            }
        }

        return null;
    }

    public static function keys(): array
    {
        return array_map(fn ($q) => $q['key'], self::all());
    }

    public static function grouped(): array
    {
        $groups = [];
        foreach (self::all() as $q) {
            $groups[$q['category']][] = $q;
        }

        return $groups;
    }

    /**
     * Keyword fallback matcher (used when no AI key is configured).
     * Returns the best matching catalog key, or null.
     */
    public static function matchIntent(string $question): ?string
    {
        $q = ' '.strtolower(trim($question)).' ';
        $best = null;
        $bestScore = 0;

        foreach (self::all() as $entry) {
            $score = 0;
            foreach ($entry['keywords'] as $kw) {
                if (str_contains($q, $kw)) {
                    // Longer keyword phrases are stronger signals
                    $score += strlen($kw) >= 8 ? 3 : 2;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $entry['key'];
            }
        }

        return $best;
    }
}
