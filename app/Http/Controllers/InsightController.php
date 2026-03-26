<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InsightController extends Controller
{
    private function getBranchFilter(Request $request)
    {
        return $request->query('branch', 'all');
    }

    private function applyBranchFilter($query, $branch, $transactionAlias = 'transactions')
    {
        if ($branch !== 'all') {
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT({$transactionAlias}.meta, '$.dist_id')) = ?", [$branch]);
        }
    }

    private function getCutoffDate()
    {
        $latestDate = Transaction::max('transaction_date');
        return $latestDate ? Carbon::parse($latestDate)->subDays(90)->toDateString() : now()->subDays(90)->toDateString();
    }

    public function index(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $cutoff = $this->getCutoffDate();
        
        // Quick Summary Counts
        $latestDate = Transaction::max('transaction_date') ?? now()->toDateString();
        $d0 = Carbon::parse($latestDate);
        
        // 1. RFM Summary
        $rfmCount = Transaction::where('total', '>', 0)->where('transaction_date', '>=', $cutoff);
        $this->applyBranchFilter($rfmCount, $branch);
        $totalOutlets = $rfmCount->distinct('outlet_id')->count();

        // 2. Bundling Sample
        $bestBundle = DB::table('sales as s1')
            ->join('sales as s2', 's1.transaction_id', '=', 's2.transaction_id')
            ->join('transactions', 's1.transaction_id', '=', 'transactions.id')
            ->where('s1.product_id', '<', 's2.product_id')
            ->where('s1.total', '>', 0)
            ->where('s2.total', '>', 0)
            ->where('transactions.transaction_date', '>=', $cutoff);
        $this->applyBranchFilter($bestBundle, $branch);
        $bestBundle = $bestBundle->select(DB::raw('COUNT(*) as count'))->first()->count > 0 ? 'Tersedia' : 'N/A';


        // 3. Anomalies
        $anomaliesCount = $this->getAnomaliesData($branch)->count();

        // 4. Stock alerts
        $stockAlertsCount = count($this->getStockForecastData($branch));

        // 5. Dead Stock
        $deadStockCount = $this->getDeadStockData($branch)->count();

        $data = [
            'selected_branch' => $branch,
            'summary' => [
                'outlets' => $totalOutlets,
                'bundles' => $bestBundle,
                'anomalies' => $anomaliesCount,
                'stock_alerts' => $stockAlertsCount,
                'dead_stock' => $deadStockCount,
            ]
        ];

        return view('insights.index', compact('data'));
    }

    // --- PILLAR 1: RFM ---
    public function rfm(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $globalLastDate = Transaction::max('transaction_date');
        $rfmNow = $globalLastDate ? Carbon::parse($globalLastDate) : now();

        $rfmQuery = Transaction::join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->where('transactions.transaction_date', '>=', $this->getCutoffDate())
            ->select(
                'outlets.name',
                DB::raw('MAX(transactions.transaction_date) as last_order'),
                DB::raw('COUNT(transactions.id) as frequency'),
                DB::raw('SUM(transactions.total) as monetary')
            )

            ->groupBy('outlets.id', 'outlets.name')
            ->having('monetary', '>', 0)
            ->orderByDesc('monetary');
        
        $this->applyBranchFilter($rfmQuery, $branch);
        $rfm = $rfmQuery->get()->map(function($item) use ($rfmNow) {
            $daysSinceOrder = Carbon::parse($item->last_order)->diffInDays($rfmNow);
            if ($item->monetary > 10000000 && $daysSinceOrder <= 7) {
                $segment = 'Sultan (High Priority)'; $color = 'success';
            } elseif ($item->monetary > 5000000 && $daysSinceOrder <= 14) {
                $segment = 'Gold (Growth)'; $color = 'info';
            } elseif ($daysSinceOrder > 14) {
                $segment = 'Sleeper (Risk)'; $color = 'danger';
            } else {
                $segment = 'Regular'; $color = 'secondary';
            }
            $item->days_since_order = $daysSinceOrder;
            $item->segment = $segment;
            $item->color = $color;
            return $item;
        });

        return view('insights.rfm', [
            'data' => $rfm,
            'selected_branch' => $branch,
            'summary' => [
                'sultans' => $rfm->where('segment', 'Sultan (High Priority)')->count(),
                'sleepers' => $rfm->where('segment', 'Sleeper (Risk)')->count(),
            ]
        ]);
    }

    // --- PILLAR 2: BUNDLING ---
    public function bundling(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $query = DB::table('sales as s1')
            ->join('sales as s2', 's1.transaction_id', '=', 's2.transaction_id')
            ->join('transactions', 's1.transaction_id', '=', 'transactions.id')
            ->join('products as p1', 's1.product_id', '=', 'p1.id')
            ->join('products as p2', 's2.product_id', '=', 'p2.id')
            ->where('s1.product_id', '<', 's2.product_id')
            ->where('s1.total', '>', 0)
            ->where('s2.total', '>', 0)
            ->where('transactions.transaction_date', '>=', $this->getCutoffDate());

        
        $this->applyBranchFilter($query, $branch);

        $bundling = $query->select(
                'p1.name as product_a',
                'p2.name as product_b',
                DB::raw('COUNT(DISTINCT s1.transaction_id) as times_bought_together')
            )
            ->groupBy('s1.product_id', 's2.product_id', 'p1.name', 'p2.name')
            ->orderByDesc('times_bought_together')
            ->limit(30)
            ->get();

        return view('insights.bundling', [
            'data' => $bundling,
            'selected_branch' => $branch
        ]);
    }

    // --- PILLAR 3: DISCOUNTS ---
    public function discounts(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $cutoff = $this->getCutoffDate();
        $query = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->whereNotNull('transactions.meta')
            ->where('transactions.transaction_date', '>=', $cutoff)
            ->where('sales.total', '>', 0)
            ->select(

                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name')) as principle"),
                DB::raw('SUM(sales.gross_price) as gross_sales'),
                DB::raw('SUM(sales.disc_item + sales.disc_internal + sales.disc_external + sales.disc_invoice) as total_discount'),
                DB::raw('SUM(sales.total) as net_sales')
            )
            ->groupBy('principle')
            ->having('gross_sales', '>', 0)
            ->orderByDesc('net_sales');
        
        $this->applyBranchFilter($query, $branch);
        $discounts = $query->get()->filter(fn($r) => $r->principle)->map(function($item) {
            $item->discount_ratio = ($item->total_discount / $item->gross_sales) * 100;
            return $item;
        });

        return view('insights.discounts', [
            'data' => $discounts,
            'selected_branch' => $branch
        ]);
    }

    // --- PILLAR 4: ANOMALIES ---
    public function anomalies(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $anomalies = $this->getAnomaliesData($branch);

        return view('insights.anomalies', [
            'data' => $anomalies,
            'selected_branch' => $branch
        ]);
    }

    private function getAnomaliesData($branch)
    {
        $query = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->whereNotNull('transactions.meta')
            ->where('transactions.transaction_date', '>=', $this->getCutoffDate())
            ->select(

                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.sales_name')) as salesman"),
                DB::raw('SUM(CASE WHEN sales.total > 0 THEN sales.total ELSE 0 END) as gross_sales'),
                DB::raw('ABS(SUM(CASE WHEN sales.total < 0 THEN sales.total ELSE 0 END)) as return_value')
            )
            ->groupBy('salesman')
            ->having('gross_sales', '>', 0);
        
        $this->applyBranchFilter($query, $branch);
        return $query->get()->filter(fn($r) => $r->salesman)->map(function($item) {
            $item->return_rate = ($item->return_value / $item->gross_sales) * 100;
            return $item;
        })->filter(function($item) {
            return $item->return_rate > 2;
        })->sortByDesc('return_rate')->values();
    }

    // --- PILLAR 5: STOCK FORECAST ---
    public function stockForecast(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $principle = $request->query('principle', 'all');

        // Fetch Principles list for the filter
        $principles = Transaction::whereNotNull('meta')
            ->select(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.principle_name')) as name"))
            ->distinct()
            ->pluck('name')
            ->filter()
            ->sort()
            ->values();

        $stockAlerts = $this->getStockForecastData($branch, $principle);

        return view('insights.stock-forecast', [
            'data' => $stockAlerts,
            'selected_branch' => $branch,
            'selected_principle' => $principle,
            'principles' => $principles
        ]);
    }

    private function getStockForecastData($branch, $principle = 'all')
    {
        $query = Sale::join('products', 'sales.product_id', '=', 'products.id')
            ->join('transactions', 'sales.transaction_id', '=', 'transactions.id')
            ->where('transactions.transaction_date', '>=', $this->getCutoffDate())
            ->where('sales.total', '>', 0)
            ->select(
                'products.id', 'products.name',
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name')) as principle_name"),
                DB::raw('SUM(sales.qty) as total_qty_sold'),
                DB::raw('COUNT(DISTINCT DATE(transactions.transaction_date)) as days_sold')
            )
            ->groupBy('products.id', 'products.name', 'principle_name')
            ->having('total_qty_sold', '>', 0);
        
        $this->applyBranchFilter($query, $branch);

        if ($principle !== 'all') {
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name')) = ?", [$principle]);
        }

        $salesVelocity = $query->get()->keyBy('id');

        $stockMap = ['OBM_01' => 'bjm', 'OBM_02' => 'brb', 'OBM_03' => 'btl'];
        $branchCode = ($branch !== 'all') ? ($stockMap[$branch] ?? $branch) : null;
        
        $stockQuery = Stock::select('product_id', DB::raw('SUM(on_hand_base) as current_stock'))
            ->groupBy('product_id');

        if ($branchCode) {
            $latestUploadId = Stock::where('branch', $branchCode)->max('upload_history_id');
            $stockQuery->where('branch', $branchCode)->where('upload_history_id', $latestUploadId);
        } else {
            // For ALL branches, we take the latest upload globally or per branch?
            // Safer: Latest upload that contains ANY stock.
            $latestUploadId = Stock::max('upload_history_id');
            $stockQuery->where('upload_history_id', $latestUploadId);
        }
        $currentStocks = $stockQuery->get()->keyBy('product_id');



        $alerts = [];
        foreach ($salesVelocity as $productId => $salesData) {
            if (!isset($currentStocks[$productId])) continue;
            $stock = $currentStocks[$productId]->current_stock;
            if ($stock <= 0) continue;
            $avgDailySales = $salesData->total_qty_sold / max($salesData->days_sold, 1);
            $daysToOos = $stock / $avgDailySales;

            if ($daysToOos <= 14) {
                $alerts[] = (object) [
                    'product_name' => $salesData->name,
                    'principle_name' => $salesData->principle_name,
                    'current_stock' => $stock,
                    'avg_daily' => (float) $avgDailySales,
                    'days_to_oos' => round($daysToOos),
                    'urgency' => $daysToOos <= 3 ? 'danger' : 'warning'
                ];
            }
        }
        usort($alerts, fn($a, $b) => $a->days_to_oos <=> $b->days_to_oos);
        return $alerts;
    }

    // --- PILLAR 6: PURCHASE ORDER DECISION (NEW) ---
    public function purchaseOrder(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $principle = $request->query('principle', 'all');

        $principles = Transaction::whereNotNull('meta')
            ->select(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.principle_name')) as name"))
            ->distinct()
            ->pluck('name')->filter()->sort()->values();

        $orderSuggestions = $this->getPurchaseOrderData($branch, $principle);

        return view('insights.purchase-order', [
            'data' => $orderSuggestions,
            'selected_branch' => $branch,
            'selected_principle' => $principle,
            'principles' => $principles
        ]);
    }

    private function getPurchaseOrderData($branch, $principle = 'all')
    {
        $cutoff = $this->getCutoffDate();
        $query = Sale::join('products', 'sales.product_id', '=', 'products.id')
            ->join('transactions', 'sales.transaction_id', '=', 'transactions.id')
            ->where('transactions.transaction_date', '>=', $cutoff)
            ->where('sales.total', '>', 0)
            ->select(
                'products.id', 'products.name',
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name')) as principle_name"),
                DB::raw('SUM(sales.qty) as total_qty_sold'),
                DB::raw('COUNT(DISTINCT DATE(transactions.transaction_date)) as days_sold')
            )
            ->groupBy('products.id', 'products.name', 'principle_name');
        
        $this->applyBranchFilter($query, $branch);
        if ($principle !== 'all') {
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name')) = ?", [$principle]);
        }

        $salesVelocity = $query->get()->keyBy('id');

        $stockMap = ['OBM_01' => 'bjm', 'OBM_02' => 'brb', 'OBM_03' => 'btl'];
        $branchCode = ($branch !== 'all') ? ($stockMap[$branch] ?? $branch) : null;

        $stockQuery = Stock::select('product_id', DB::raw('SUM(on_hand_base) as current_stock'))->groupBy('product_id');
        
        if ($branchCode) {
            $latestUploadId = Stock::where('branch', $branchCode)->max('upload_history_id');
            $stockQuery->where('branch', $branchCode)->where('upload_history_id', $latestUploadId);
        } else {
            $latestUploadId = Stock::max('upload_history_id');
            $stockQuery->where('upload_history_id', $latestUploadId);
        }
        $currentStocks = $stockQuery->get()->keyBy('product_id');



        $results = [];
        foreach ($salesVelocity as $productId => $salesData) {
            $stock = isset($currentStocks[$productId]) ? $currentStocks[$productId]->current_stock : 0;
            $avgDaily = $salesData->total_qty_sold / max($salesData->days_sold, 1);
            
            $results[] = (object) [
                'product_name' => $salesData->name,
                'principle_name' => $salesData->principle_name,
                'current_stock' => $stock,
                'avg_daily' => (float) $avgDaily
            ];
        }
        return $results;
    }

    // --- PILLAR 7: DEAD STOCK ---

    public function deadStock(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $deadStock = $this->getDeadStockData($branch);

        return view('insights.dead-stock', [
            'data' => $deadStock,
            'selected_branch' => $branch
        ]);
    }

    private function getDeadStockData($branch)
    {
        $cutoff = $this->getCutoffDate();
        $soldProductIds = Sale::join('transactions', 'sales.transaction_id', '=', 'transactions.id')
            ->where('transactions.transaction_date', '>=', $cutoff)
            ->distinct()->pluck('sales.product_id')->toArray();

        $query = Stock::join('products', 'stocks.product_id', '=', 'products.id')

            ->whereNotIn('stocks.product_id', $soldProductIds)
            ->where('stocks.on_hand_base', '>', 0)
            ->select(
                'products.name',
                DB::raw('SUM(stocks.on_hand_base) as stock'),
                DB::raw('SUM(stocks.stock_value_on_hand) as value')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('value');
        
        $stockMap = ['OBM_01' => 'bjm', 'OBM_02' => 'brb', 'OBM_03' => 'btl'];
        $branchCode = ($branch !== 'all') ? ($stockMap[$branch] ?? $branch) : null;
        
        if ($branchCode) {
            $latestUploadId = Stock::where('branch', $branchCode)->max('upload_history_id');
            $query->where('stocks.branch', $branchCode)->where('stocks.upload_history_id', $latestUploadId);
        } else {
            $latestUploadId = Stock::max('upload_history_id');
            $query->where('stocks.upload_history_id', $latestUploadId);
        }

        return $query->get();
    }

    // --- PILLAR 7: GROWTH ---
    public function growth(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $latestDate = Transaction::max('transaction_date');
        $d0 = Carbon::parse($latestDate);
        $d7 = $d0->copy()->subDays(7);
        $d14 = $d0->copy()->subDays(14);

        $fetchGrowth = function($start, $end) use ($branch) {
            $q = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
                ->whereBetween('transactions.transaction_date', [$start, $end])
                ->select(
                    DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name')) as principle"),
                    DB::raw('SUM(sales.total) as total')
                )
                ->groupBy('principle');
            $this->applyBranchFilter($q, $branch);
            return $q->get()->keyBy('principle');
        };

        $currentWeek = $fetchGrowth($d7->toDateString(), $d0->toDateString());
        $previousWeek = $fetchGrowth($d14->toDateString(), $d7->copy()->subDay()->toDateString());

        $principleGrowth = [];
        foreach ($currentWeek as $p => $curr) {
            $prevTotal = $previousWeek[$p]->total ?? 0;
            $growth = $prevTotal > 0 ? (($curr->total - $prevTotal) / $prevTotal) * 100 : 100;
            $principleGrowth[] = (object) [
                'principle' => $p,
                'current' => $curr->total,
                'previous' => $prevTotal,
                'growth' => round($growth, 1)
            ];
        }
        usort($principleGrowth, fn($a, $b) => $b->growth <=> $a->growth);

        return view('insights.growth', [
            'data' => $principleGrowth,
            'selected_branch' => $branch
        ]);
    }

    public function principalReport(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $cutoff = $this->getCutoffDate();
        
        // Fetch all possible principles for the dropdown
        $principles = Transaction::where('transaction_date', '>=', $cutoff)
            ->select(DB::raw('JSON_UNQUOTE(JSON_EXTRACT(meta, "$.principle_name")) as name'))
            ->distinct()->pluck('name')->filter(fn($n) => !empty($n) && $n !== 'Principle Name')->sort()->values();

        $selectedPrinciple = $request->query('principle', $principles[0] ?? null);

        if (!$selectedPrinciple) {
            return view('insights.principal-report', [
                'data' => null,
                'principles' => $principles,
                'selected_branch' => $branch,
                'selected_principle' => null
            ]);
        }

        // 1. Summary Metrics
        $queryBase = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->where('transactions.transaction_date', '>=', $cutoff)
            ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, "$.principle_name")) = ?', [$selectedPrinciple]);
        $this->applyBranchFilter($queryBase, $branch);

        $summary = (clone $queryBase)->select(
            DB::raw('SUM(sales.total) as total_value'),
            DB::raw('SUM(sales.qty) as total_qty'),
            DB::raw('COUNT(DISTINCT transactions.outlet_id) as total_outlets')
        )->first();

        // 2. Trend (Weekly)
        $trend = (clone $queryBase)->select(
            DB::raw('DATE_FORMAT(transactions.transaction_date, "%Y-%u") as week'),
            DB::raw('MIN(transactions.transaction_date) as week_start'),
            DB::raw('SUM(sales.total) as total')
        )->groupBy('week')->orderBy('week')->get();

        // 3. Top Products
        $topProducts = (clone $queryBase)->join('products', 'sales.product_id', '=', 'products.id')
            ->select('products.name', DB::raw('SUM(sales.total) as value'), DB::raw('SUM(sales.qty) as qty'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('value')->limit(10)->get();

        // 4. Top Outlets
        $topOutlets = (clone $queryBase)->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->select('outlets.name', DB::raw('SUM(sales.total) as value'))
            ->groupBy('outlets.id', 'outlets.name')
            ->orderByDesc('value')->limit(10)->get();

        // 5. Salesman Performance
        $topSalesmen = (clone $queryBase)
            ->select(DB::raw('JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, "$.sales_name")) as name'), DB::raw('SUM(sales.total) as value'))
            ->groupBy('name')->orderByDesc('value')->get();

        return view('insights.principal-report', [
            'principles' => $principles,
            'selected_principle' => $selectedPrinciple,
            'selected_branch' => $branch,
            'summary' => $summary,
            'trend' => $trend,
            'topProducts' => $topProducts,
            'topOutlets' => $topOutlets,
            'topSalesmen' => $topSalesmen
        ]);
    }

    // --- PILLAR 8: GUIDE ---

    public function guide()
    {
        return view('insights.guide');
    }
}
