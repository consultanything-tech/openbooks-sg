<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ApiReportController extends Controller
{
    public function profitLoss(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->input('date_to', now()->endOfYear()->toDateString());

        $income = Transaction::where('type', 'income')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->sum('amount');

        $expense = Transaction::where('type', 'expense')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->sum('amount');

        return response()->json([
            'data' => [
                'period' => ['from' => $dateFrom, 'to' => $dateTo],
                'total_income' => round((float) $income, 2),
                'total_expense' => round((float) $expense, 2),
                'net_profit' => round((float) $income - (float) $expense, 2),
            ],
        ]);
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $asOf = $request->input('as_of', now()->toDateString());

        $assets = Account::where('type', 'asset')->sum('balance');
        $liabilities = Account::where('type', 'liability')->sum('balance');
        $equity = Account::where('type', 'equity')->sum('balance');

        return response()->json([
            'data' => [
                'as_of' => $asOf,
                'total_assets' => round((float) $assets, 2),
                'total_liabilities' => round((float) $liabilities, 2),
                'total_equity' => round((float) $equity, 2),
            ],
        ]);
    }
}
