<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Stock;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $branch = $request->query('branch', 'all');

        $applyBranchFilter = function($query, $transactionAlias = 'transactions') use ($branch) {
            if ($branch !== 'all') {
                $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT({$transactionAlias}.meta, '$.dist_id')) = ?", [$branch]);
            }
        };

        // 1. Sales Trend (last 30 days)
        $salesTrendQuery = Transaction::query()
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->where('sales.total', '>', 0)
            ->select(
                DB::raw('transactions.transaction_date as date'),
                DB::raw('SUM(sales.total) as total'),
                DB::raw('SUM(sales.qty) as qty'),
                DB::raw('COUNT(DISTINCT transactions.id) as transactions')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->limit(60);
        $applyBranchFilter($salesTrendQuery);
        $salesTrend = $salesTrendQuery->get();

        // 2. Top 10 Products (for bar chart)
        $topProductsQuery = Sale::query()
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->join('transactions', 'sales.transaction_id', '=', 'transactions.id')
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

        // 3. Sales by Principle (for donut chart)
        $byPrincipleQuery = Transaction::query()
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->whereNotNull('transactions.meta')
            ->select(
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name')) as principle"),
                DB::raw('SUM(sales.total) as total')
            )
            ->groupBy('principle')
            ->orderByDesc('total');
        $applyBranchFilter($byPrincipleQuery);
        $byPrinciple = $byPrincipleQuery->get()->filter(fn($r) => $r->principle);

        // 4. Pareto Analysis (80/20)
        $allProductsQuery = Sale::query()
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->join('transactions', 'sales.transaction_id', '=', 'transactions.id')
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
        
        $paretoChartData = array_slice($paretoData, 0, 50); // Top 50 for the chart

        // 5. Gross vs Net trend
        $grossNetTrendQuery = Transaction::query()
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->where('sales.total', '>', 0)
            ->select(
                DB::raw('transactions.transaction_date as date'),
                DB::raw('SUM(sales.gross_price) as gross'),
                DB::raw('SUM(sales.total) as net')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->limit(30);
        $applyBranchFilter($grossNetTrendQuery);
        $grossNetTrend = $grossNetTrendQuery->get();

        // 6. Stock summary per branch
        $stockMap = ['OBM_01' => 'Banjarmasin', 'OBM_02' => 'Barabai', 'OBM_03' => 'Batulicin'];
        $latestUploadId = Stock::max('upload_history_id');
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

        // 7. Summary cards
        // Total Sales (Gross Sales only)
        $totalSalesQuery = Sale::where('sales.total', '>', 0)
                            ->join('transactions', 'sales.transaction_id', '=', 'transactions.id');
        $applyBranchFilter($totalSalesQuery);
        $totalSales = $totalSalesQuery->sum('sales.total');

        // Net Sales (Gross - Returns)
        $netSalesQuery = Sale::join('transactions', 'sales.transaction_id', '=', 'transactions.id');
        $applyBranchFilter($netSalesQuery);
        $netSales = $netSalesQuery->sum('sales.total');

        $totalReturnsQuery = Sale::where('sales.total', '<', 0)
                            ->join('transactions', 'sales.transaction_id', '=', 'transactions.id');
        $applyBranchFilter($totalReturnsQuery);
        $totalReturns = abs($totalReturnsQuery->sum('sales.total'));

        $totalTransactionsQuery = Transaction::query();
        $applyBranchFilter($totalTransactionsQuery);
        $totalTransactions = $totalTransactionsQuery->count();

        $summary = [
            'total_sales' => $totalSales,
            'net_sales' => $netSales,
            'total_returns' => $totalReturns,
            'total_transactions' => $totalTransactions,
            'avg_daily_sales' => $salesTrend->count() > 0 ? $salesTrend->avg('total') : 0,
            'selected_branch' => $branch,
        ];

        return view('analytics.index', compact(
            'salesTrend', 'topProducts', 'byPrinciple',
            'paretoData', 'paretoCount80', 'totalProducts', 'paretoChartData',
            'grossNetTrend', 'stockByBranch', 'summary'
        ));
    }
}
