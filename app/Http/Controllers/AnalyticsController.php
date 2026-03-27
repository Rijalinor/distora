<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\Period;
use App\Models\UploadHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Helper to cache expensive analytic results.
     */
    private function cachedResult(string $key, \App\Models\Period $period, callable $callback)
    {
        $branch = request()->query('branch', 'all');
        $fullKey = "analytics_{$key}_{$period->id}_{$branch}_" . md5(json_encode(request()->all()));

        if ($period->status === 'closed') {
            return Cache::rememberForever($fullKey, $callback);
        }

        return Cache::remember($fullKey, 600, $callback);
    }

    public function index(Request $request)
    {
        $branch = $request->query('branch', 'all');
        $activePeriod = Period::resolveFromRequest($request);
        [$startDate, $endDate] = $activePeriod->getRange();

        $data = $this->cachedResult('dashboard_full', $activePeriod, function() use ($branch, $activePeriod, $startDate, $endDate) {
            $applyBranchFilter = function($query, $transactionAlias = 'transactions') use ($branch) {
                if ($branch !== 'all') {
                    // Optimized: use the virtual column and its index
                    $query->where("{$transactionAlias}.dist_branch_id", $branch);
                }
            };

            // 1. Sales Trend
            $salesTrendQuery = Transaction::query()
                ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
                ->where('sales.total', '>', 0)
                ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
                ->select(
                    DB::raw('transactions.transaction_date as date'),
                    DB::raw('SUM(sales.total) as total'),
                    DB::raw('SUM(sales.qty) as qty'),
                    DB::raw('COUNT(DISTINCT transactions.id) as transactions')
                )
                ->groupBy('date')
                ->orderBy('date');
            $applyBranchFilter($salesTrendQuery);
            $salesTrend = $salesTrendQuery->get();

            // 2. Top 10 Products
            $topProductsQuery = Sale::query()
                ->join('products', 'sales.product_id', '=', 'products.id')
                ->join('transactions', 'sales.transaction_id', '=', 'transactions.id')
                ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
                ->select(
                    'products.name',
                    DB::raw('SUM(sales.total) as total'),
                    DB::raw('SUM(sales.qty) as qty')
                )
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('total')
                ->limit(10);
            $applyBranchFilter($topProductsQuery);
            $topProducts = $topProductsQuery->get();

            // 3. Sales by Principle
            $byPrincipleQuery = Transaction::query()
                ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
                ->whereNotNull('transactions.meta')
                ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
                ->select(
                    DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name')) as principle"),
                    DB::raw('SUM(sales.total) as total')
                )
                ->groupBy('principle')
                ->orderByDesc('total');
            $applyBranchFilter($byPrincipleQuery);
            $byPrinciple = $byPrincipleQuery->get()->filter(fn($r) => $r->principle);

            // 4. Pareto Analysis
            $allProductsQuery = Sale::query()
                ->join('products', 'sales.product_id', '=', 'products.id')
                ->join('transactions', 'sales.transaction_id', '=', 'transactions.id')
                ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
                ->select(
                    'products.name',
                    DB::raw('SUM(sales.total) as total')
                )
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('total');
            $applyBranchFilter($allProductsQuery);
            $allProducts = $allProductsQuery->get();

            $grandTotal = $allProducts->sum('total') ?: 1;
            $cumulative = 0;
            $paretoData = [];
            foreach ($allProducts as $i => $p) {
                $cumulative += $p->total;
                $percent = round(($cumulative / $grandTotal) * 100, 1);
                $abcClass = $percent <= 80 ? 'A' : ($percent <= 95 ? 'B' : 'C');
                $paretoData[] = [
                    'name' => $p->name,
                    'total' => $p->total,
                    'cumulative_pct' => $percent,
                    'rank' => $i + 1,
                    'class' => $abcClass
                ];
            }
            $paretoCount80 = collect($paretoData)->where('class', 'A')->count();
            $totalProducts = $allProducts->count();
            $paretoChartData = array_slice($paretoData, 0, 50);

            // 5. Gross vs Net trend
            $grossNetTrendQuery = Transaction::query()
                ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
                ->where('sales.total', '>', 0)
                ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
                ->select(
                    DB::raw('transactions.transaction_date as date'),
                    DB::raw('SUM(sales.gross_price) as gross'),
                    DB::raw('SUM(sales.total) as net')
                )
                ->groupBy('date')
                ->orderBy('date');
            $applyBranchFilter($grossNetTrendQuery);
            $grossNetTrend = $grossNetTrendQuery->get();

            // 6. Stock summary
            $stockMap = ['OBM_01' => 'Banjarmasin', 'OBM_02' => 'Barabai', 'OBM_03' => 'Batulicin'];
            $latestUploadId = Stock::whereHas('uploadHistory', fn($q) => $q->where('period_id', $activePeriod->id))->max('upload_history_id');
            
            $stockByBranch = collect();
            if ($latestUploadId) {
                $stockQuery = Stock::where('upload_history_id', $latestUploadId)
                    ->select(
                        'branch',
                        DB::raw('SUM(stock_value_on_hand) as value'),
                        DB::raw('COUNT(*) as items')
                    )
                    ->groupBy('branch');
                if ($branch !== 'all' && isset($stockMap[$branch])) {
                    $stockQuery->where('branch', 'like', '%' . $stockMap[$branch] . '%');
                }
                $stockByBranch = $stockQuery->get();
            }

            // 7. Summary cards
            $uploadIds = UploadHistory::where('period_id', $activePeriod->id)->pluck('id');

            $totalSalesQuery = Sale::where('sales.total', '>', 0)
                                ->join('transactions', 'sales.transaction_id', '=', 'transactions.id')
                                ->whereIn('transactions.upload_history_id', $uploadIds);
            $applyBranchFilter($totalSalesQuery);
            $totalSales = $totalSalesQuery->sum('sales.total');

            $netSalesQuery = Sale::join('transactions', 'sales.transaction_id', '=', 'transactions.id')
                                ->whereIn('transactions.upload_history_id', $uploadIds);
            $applyBranchFilter($netSalesQuery);
            $netSales = $netSalesQuery->sum('sales.total');

            $totalReturnsQuery = Sale::where('sales.total', '<', 0)
                                ->join('transactions', 'sales.transaction_id', '=', 'transactions.id')
                                ->whereIn('transactions.upload_history_id', $uploadIds);
            $applyBranchFilter($totalReturnsQuery);
            $totalReturns = abs($totalReturnsQuery->sum('sales.total'));

            $totalTransactionsQuery = Transaction::query()
                                    ->whereIn('transactions.upload_history_id', $uploadIds);
            $applyBranchFilter($totalTransactionsQuery);
            $totalTransactions = $totalTransactionsQuery->count();

            return [
                'salesTrend' => $salesTrend,
                'topProducts' => $topProducts,
                'byPrinciple' => $byPrinciple,
                'paretoData' => $paretoData,
                'paretoCount80' => $paretoCount80,
                'totalProducts' => $totalProducts,
                'paretoChartData' => $paretoChartData,
                'grossNetTrend' => $grossNetTrend,
                'stockByBranch' => $stockByBranch,
                'summary' => [
                    'total_sales' => $totalSales,
                    'net_sales' => $netSales,
                    'total_returns' => $totalReturns,
                    'total_transactions' => $totalTransactions,
                    'avg_daily_sales' => $salesTrend->count() > 0 ? $salesTrend->avg('total') : 0,
                ],
                'netSales' => $netSales,
                'totalSales' => $totalSales,
                'totalReturns' => $totalReturns,
            ];
        });

        $allPeriods = Period::ordered()->get();
        return view('analytics.index', array_merge($data, [
            'branch' => $branch,
            'activePeriod' => $activePeriod,
            'allPeriods' => $allPeriods
        ]));
    }
}
