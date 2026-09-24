<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CustomReport;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomReportController extends Controller
{
    use LogsActivity;

    /**
     * Show the custom report builder page.
     */
    public function index()
    {
        $company = Company::first() ?? new Company(['currency_symbol' => 'S$']);
        $currencySymbol = $company->currency_symbol ?? 'S$';

        $savedReports = CustomReport::with('user')
            ->where(function ($q) {
                $q->where('user_id', Auth::id())->orWhere('is_shared', true);
            })
            ->latest()
            ->get();

        return view('reports.custom', compact('company', 'currencySymbol', 'savedReports'));
    }

    /**
     * Save a report definition.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'source' => 'required|in:invoices,bills,transactions,time_entries',
            'definition' => 'required|array',
            'is_shared' => 'boolean',
        ]);

        $report = CustomReport::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'source' => $validated['source'],
            'definition' => $validated['definition'],
            'user_id' => Auth::id(),
            'is_shared' => $validated['is_shared'] ?? false,
        ]);

        $this->logActivity('created', "Saved custom report: {$report->name}", 'CustomReport', $report->id);

        return response()->json(['success' => true, 'report' => $report]);
    }

    /**
     * Execute a report definition and return JSON results.
     */
    public function run(Request $request)
    {
        $validated = $request->validate([
            'source' => 'required|in:invoices,bills,transactions,time_entries',
            'dimensions' => 'required|array|min:1',
            'dimensions.*' => 'in:date,customer,category,status',
            'metrics' => 'required|array|min:1',
            'metrics.*' => 'in:sum_total,count,avg_total,sum_tax',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'group_by' => 'nullable|string',
        ]);

        $results = $this->buildAndRunQuery($validated);

        return response()->json(['success' => true, 'results' => $results]);
    }

    /**
     * Delete a saved report.
     */
    public function destroy($id)
    {
        $report = CustomReport::where('user_id', Auth::id())->findOrFail($id);
        $report->delete();

        $this->logActivity('deleted', "Deleted custom report: {$report->name}", 'CustomReport', $report->id);

        return redirect()->route('reports.custom')->with('success', 'Report deleted.');
    }

    /**
     * Export report results as CSV.
     */
    public function exportCsv(Request $request)
    {
        $validated = $request->validate([
            'source' => 'required|in:invoices,bills,transactions,time_entries',
            'dimensions' => 'required|array|min:1',
            'metrics' => 'required|array|min:1',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'group_by' => 'nullable|string',
        ]);

        $results = $this->buildAndRunQuery($validated);

        $filename = 'custom-report-'.date('Y-m-d-His').'.csv';

        $output = fopen('php://temp', 'r+');

        if (! empty($results['rows'])) {
            // Header row
            fputcsv($output, array_keys($results['rows'][0]), ',', '"', '\\');
            foreach ($results['rows'] as $row) {
                fputcsv($output, array_values($row), ',', '"', '\\');
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Build and execute the dynamic query based on report definition.
     */
    protected function buildAndRunQuery(array $params): array
    {
        $source = $params['source'];
        $dimensions = $params['dimensions'];
        $metrics = $params['metrics'];
        $dateFrom = $params['date_from'] ?? null;
        $dateTo = $params['date_to'] ?? null;
        $groupBy = $params['group_by'] ?? null;

        // Map source to table/config
        $sourceConfig = $this->getSourceConfig($source);
        $query = DB::table($sourceConfig['table']);

        // Raw queries bypass Eloquent's soft-delete scope, so exclude trashed rows.
        // Qualified by table name because joined party tables may have the column too.
        if (Schema::hasColumn($sourceConfig['table'], 'deleted_at')) {
            $query->whereNull($sourceConfig['table'].'.deleted_at');
        }

        // Apply date filters
        $dateColumn = $sourceConfig['date_column'];
        if ($dateFrom) {
            $query->whereDate($dateColumn, '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate($dateColumn, '<=', $dateTo);
        }

        // Apply status filter if available
        if (! empty($sourceConfig['status_column'])) {
            // Include all statuses by default
        }

        // "Group by" makes the chosen dimension primary (first column, sort key)
        if ($groupBy && in_array($groupBy, $dimensions, true)) {
            $dimensions = array_values(array_unique(array_merge([$groupBy], $dimensions)));
        }

        // Build select and group by
        $selects = [];
        $groups = [];

        foreach ($dimensions as $dim) {
            if ($dim === 'date' && $dateColumn) {
                $selects[] = DB::raw("DATE({$dateColumn}) as report_date");
                $groups[] = DB::raw("DATE({$dateColumn})");
            } elseif ($dim === 'customer' && ! empty($sourceConfig['party_fk'])) {
                $partyTable = $sourceConfig['party_table'];
                $partyFk = $sourceConfig['party_fk'];
                if ($partyTable && $partyFk) {
                    $query->leftJoin($partyTable, "{$sourceConfig['table']}.{$partyFk}", '=', "{$partyTable}.id");
                    $nameExpr = "{$partyTable}.name";
                    if (! empty($sourceConfig['party2_table']) && ! empty($sourceConfig['party2_fk'])) {
                        $p2Table = $sourceConfig['party2_table'];
                        $p2Fk = $sourceConfig['party2_fk'];
                        $query->leftJoin($p2Table, "{$sourceConfig['table']}.{$p2Fk}", '=', "{$p2Table}.id");
                        $nameExpr .= ", {$p2Table}.name";
                    }
                    $expr = "COALESCE({$nameExpr}, 'Unknown')";
                    $selects[] = DB::raw("$expr as party_name");
                    $groups[] = DB::raw($expr);
                }
            } elseif ($dim === 'category' && ! empty($sourceConfig['category_column'])) {
                $catCol = $sourceConfig['category_column'];
                $query->leftJoin('categories', "{$sourceConfig['table']}.{$catCol}", '=', 'categories.id');
                $selects[] = DB::raw("COALESCE(categories.name, 'Uncategorized') as category_name");
                $groups[] = DB::raw("COALESCE(categories.name, 'Uncategorized')");
            } elseif ($dim === 'status' && ! empty($sourceConfig['status_column'])) {
                $statusCol = $sourceConfig['status_column'];
                $selects[] = "{$sourceConfig['table']}.{$statusCol} as status";
                $groups[] = "{$sourceConfig['table']}.{$statusCol}";
            }
        }

        // Build metrics
        $totalColumn = $sourceConfig['total_column'];
        $taxColumn = $sourceConfig['tax_column'];

        foreach ($metrics as $metric) {
            if ($metric === 'sum_total' && $totalColumn) {
                $selects[] = DB::raw("COALESCE(SUM({$totalColumn}), 0) as sum_total");
            } elseif ($metric === 'count') {
                $selects[] = DB::raw('COUNT(*) as record_count');
            } elseif ($metric === 'avg_total' && $totalColumn) {
                $selects[] = DB::raw("COALESCE(AVG({$totalColumn}), 0) as avg_total");
            } elseif ($metric === 'sum_tax' && $taxColumn) {
                $selects[] = DB::raw("COALESCE(SUM({$taxColumn}), 0) as sum_tax");
            }
        }

        if (empty($selects)) {
            return ['rows' => [], 'summary' => [], 'message' => 'No valid dimensions or metrics selected.'];
        }

        $query->select($selects);

        if (! empty($groups)) {
            $query->groupBy($groups);
            $query->orderBy($groups[0]);
        }

        $rows = $query->limit(500)->get()->map(function ($row) {
            $r = (array) $row;
            // MySQL returns DECIMAL aggregates as strings; normalise for the UI
            foreach (['sum_total', 'avg_total', 'sum_tax'] as $key) {
                if (array_key_exists($key, $r)) {
                    $r[$key] = (float) $r[$key];
                }
            }
            if (array_key_exists('record_count', $r)) {
                $r['record_count'] = (int) $r['record_count'];
            }

            return $r;
        })->toArray();

        // Build summary
        $summary = [];
        foreach ($metrics as $metric) {
            if ($metric === 'sum_total') {
                $summary['sum_total'] = array_sum(array_column($rows, 'sum_total'));
            } elseif ($metric === 'count') {
                $summary['record_count'] = array_sum(array_column($rows, 'record_count'));
            } elseif ($metric === 'avg_total') {
                $vals = array_column($rows, 'avg_total');
                $summary['avg_total'] = count($vals) > 0 ? array_sum($vals) / count($vals) : 0;
            } elseif ($metric === 'sum_tax') {
                $summary['sum_tax'] = array_sum(array_column($rows, 'sum_tax'));
            }
        }

        return ['rows' => $rows, 'summary' => $summary];
    }

    /**
     * Get table/column configuration for a data source.
     */
    protected function getSourceConfig(string $source): array
    {
        return match ($source) {
            'invoices' => [
                'table' => 'invoices',
                'date_column' => 'invoice_date',
                'total_column' => 'total',
                'tax_column' => 'tax_total',
                'status_column' => 'status',
                'party_table' => 'customers',
                'party_column' => 'name',
                'party_fk' => 'customer_id',
                'category_column' => null,
            ],
            'bills' => [
                'table' => 'bills',
                'date_column' => 'bill_date',
                'total_column' => 'total',
                'tax_column' => 'tax_total',
                'status_column' => 'status',
                'party_table' => 'vendors',
                'party_column' => 'name',
                'party_fk' => 'vendor_id',
                'category_column' => null,
            ],
            'transactions' => [
                'table' => 'transactions',
                'date_column' => 'transaction_date',
                'total_column' => 'amount',
                'tax_column' => null,
                'status_column' => null,
                'party_table' => 'customers',
                'party_column' => 'name',
                'party_fk' => 'customer_id',
                'party2_table' => 'vendors',
                'party2_fk' => 'vendor_id',
                'category_column' => 'category_id',
            ],
            'time_entries' => [
                'table' => 'time_entries',
                'date_column' => 'entry_date',
                'total_column' => 'amount',
                'tax_column' => null,
                'status_column' => null,
                'party_table' => 'customers',
                'party_column' => 'name',
                'party_fk' => 'customer_id',
                'category_column' => null,
            ],
            default => [
                'table' => 'invoices',
                'date_column' => 'invoice_date',
                'total_column' => 'total',
                'tax_column' => 'tax_total',
                'status_column' => 'status',
                'party_table' => 'customers',
                'party_column' => 'name',
                'party_fk' => 'customer_id',
                'category_column' => null,
            ],
        };
    }
}
