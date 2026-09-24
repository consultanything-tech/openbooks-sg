<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    use LogsActivity;

    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        // Load all expense categories
        $categories = Category::where('type', 'expense')->orderBy('name')->get();

        // Load budgets for the year keyed by category_id
        $budgets = Budget::where('year', $year)
            ->whereIn('category_id', $categories->pluck('id'))
            ->get()
            ->groupBy('category_id');

        // Load actual expenses grouped by category and month
        $yearStart = sprintf('%04d-01-01', $year);
        $yearEnd = sprintf('%04d-12-31', $year);

        $actuals = Transaction::where('type', 'expense')
            ->whereBetween('transaction_date', [$yearStart, $yearEnd])
            ->selectRaw('category_id, MONTH(transaction_date) as month, SUM(amount) as total')
            ->groupBy('category_id', 'month')
            ->get();

        $actualsByCategory = [];
        foreach ($actuals as $row) {
            $actualsByCategory[$row->category_id][$row->month] = (float) $row->total;
        }

        // Build overview data per category
        $overview = [];
        $totalBudget = 0;
        $totalActual = 0;

        foreach ($categories as $category) {
            $catBudgets = $budgets->get($category->id, collect());
            $annualBudget = 0;
            foreach ($catBudgets as $b) {
                $annualBudget += (float) $b->amount;
            }

            $catActuals = $actualsByCategory[$category->id] ?? [];
            $actualSpent = array_sum($catActuals);

            $variance = $annualBudget - $actualSpent;
            $pctUsed = $annualBudget > 0 ? round(($actualSpent / $annualBudget) * 100, 1) : 0;

            $overview[] = [
                'category' => $category,
                'budget' => $annualBudget,
                'actual' => $actualSpent,
                'variance' => $variance,
                'pct_used' => $pctUsed,
            ];

            $totalBudget += $annualBudget;
            $totalActual += $actualSpent;
        }

        $totalVariance = $totalBudget - $totalActual;
        $overallPct = $totalBudget > 0 ? round(($totalActual / $totalBudget) * 100, 1) : 0;

        return view('budgets.index', compact(
            'year', 'overview', 'totalBudget', 'totalActual', 'totalVariance', 'overallPct'
        ));
    }

    public function create(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        $categories = Category::where('type', 'expense')->orderBy('name')->get();

        // Load existing budgets for the year
        $budgets = Budget::where('year', $year)
            ->whereIn('category_id', $categories->pluck('id'))
            ->get();

        // Key by "category_id-month"
        $existing = [];
        foreach ($budgets as $b) {
            $existing[$b->category_id.'-'.$b->month] = (float) $b->amount;
        }

        return view('budgets.create', compact('year', 'categories', 'existing'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'budgets' => 'required|array',
        ]);

        $year = (int) $request->input('year');
        $budgetData = $request->input('budgets', []);
        $saved = 0;
        $deleted = 0;

        foreach ($budgetData as $categoryId => $months) {
            if (! is_array($months)) {
                continue;
            }
            foreach ($months as $month => $amount) {
                $month = (int) $month;
                $amount = (float) str_replace(',', '', $amount ?? '0');

                if ($amount > 0) {
                    Budget::updateOrCreate(
                        ['category_id' => $categoryId, 'year' => $year, 'month' => $month],
                        ['amount' => $amount]
                    );
                    $saved++;
                } else {
                    // Delete zero/empty budgets
                    $del = Budget::where('category_id', $categoryId)
                        ->where('year', $year)
                        ->where('month', $month)
                        ->delete();
                    $deleted += $del;
                }
            }
        }

        $this->logActivity('budget_updated', "Updated budgets for year {$year} ({$saved} entries saved, {$deleted} removed)", 'Budget', null);

        return redirect()->route('budgets.index', ['year' => $year])
            ->with('success', "Budgets for {$year} saved successfully ({$saved} entries).");
    }
}
