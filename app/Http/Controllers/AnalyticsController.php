<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Stock;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        // 1. Sales Trend (last 30 days)
        $salesTrend = Transaction::query()
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
            ->limit(60)
            ->get();

        // 2. Top 10 Products (for bar chart)
        $topProducts = Sale::query()
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->where('sales.total', '>', 0)
            ->select(
                'products.name',
                DB::raw('SUM(sales.total) as total'),
                DB::raw('SUM(sales.qty) as qty')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // 3. Sales by Principle (for donut chart)
        $byPrinciple = Transaction::query()
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->where('sales.total', '>', 0)
            ->whereNotNull('transactions.meta')
            ->select(
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name')) as principle"),
                DB::raw('SUM(sales.total) as total')
            )
            ->groupBy('principle')
            ->orderByDesc('total')
            ->get()
            ->filter(fn($r) => $r->principle);

        // 4. Pareto Analysis (80/20)
        $allProducts = Sale::query()
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->where('sales.total', '>', 0)
            ->select(
                'products.name',
                DB::raw('SUM(sales.total) as total')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total')
            ->get();

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
        $grossNetTrend = Transaction::query()
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->where('sales.total', '>', 0)
            ->select(
                DB::raw('transactions.transaction_date as date'),
                DB::raw('SUM(sales.gross_price) as gross'),
                DB::raw('SUM(sales.total) as net')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->limit(30)
            ->get();

        // 6. Stock summary per branch
        $latestUploadId = Stock::max('upload_history_id');
        $stockByBranch = Stock::where('upload_history_id', $latestUploadId)
            ->select(
                'branch',
                DB::raw('SUM(stock_value_on_hand) as value'),
                DB::raw('COUNT(*) as items')
            )
            ->groupBy('branch')
            ->get();

        // 7. Summary cards
        $summary = [
            'total_sales' => Sale::where('total', '>', 0)->sum('total'),
            'total_returns' => abs(Sale::where('total', '<', 0)->sum('total')),
            'total_transactions' => Transaction::count(),
            'avg_daily_sales' => $salesTrend->count() > 0 ? $salesTrend->avg('total') : 0,
        ];

        return view('analytics.index', compact(
            'salesTrend', 'topProducts', 'byPrinciple',
            'paretoData', 'paretoCount80', 'totalProducts', 'paretoChartData',
            'grossNetTrend', 'stockByBranch', 'summary'
        ));
    }
}
