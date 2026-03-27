<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Period;
use App\Models\UploadHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class InsightController extends Controller
{
    private function getSelectedPeriod(Request $request)
    {
        return \App\Models\Period::resolveFromRequest($request);
    }

    /**
     * Helper to cache expensive analytic results.
     */
    private function cachedResult(string $key, \App\Models\Period $period, callable $callback)
    {
        // Unique cache key based on period and branch
        $branch = request()->query('branch', 'all');
        $fullKey = "insight_{$key}_{$period->id}_{$branch}_" . md5(json_encode(request()->all()));

        if ($period->status === 'closed') {
            // Historical data never changes - cache forever
            return Cache::rememberForever($fullKey, $callback);
        }

        // Active period data changes - cache for 10 minutes
        return Cache::remember($fullKey, 600, $callback);
    }

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

    private function get3MonthRange(\App\Models\Period $period)
    {
        // For March selection, we want IDs of Dec, Jan, Feb periods
        return $period->getPrecedingIds(3);
    }

    private function getCutoffDate($period = null)
    {
        // No longer needed for date-based window, returning null or dummy
        return null;
    }

    public function index(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $activePeriod = $this->getSelectedPeriod($request);
        $periodIds = $this->get3MonthRange($activePeriod);
        [$startDate, $endDate] = $activePeriod->getRange();
        
        $data = $this->cachedResult('index_summary_v5', $activePeriod, function() use ($branch, $activePeriod, $periodIds, $startDate, $endDate) {
            // 1. RFM Summary
            $rfmCount = Transaction::whereIn('upload_history_id', function($q) use ($periodIds) {
                    $q->select('id')->from('upload_histories')->whereIn('period_id', $periodIds);
                })
                ->where('total', '>', 0);
            $this->applyBranchFilter($rfmCount, $branch);
            $totalOutlets = $rfmCount->distinct('outlet_id')->count();

            // 2. Bundling Sample
            $bestBundle = DB::table('sales as s1')
                ->join('sales as s2', 's1.transaction_id', '=', 's2.transaction_id')
                ->join('transactions', 's1.transaction_id', '=', 'transactions.id')
                ->where('s1.product_id', '<', 's2.product_id')
                ->where('s1.total', '>', 0)
                ->where('s2.total', '>', 0)
                ->whereBetween('transactions.transaction_date', [$startDate, $endDate]);
            $this->applyBranchFilter($bestBundle, $branch);
            $bestBundle = $bestBundle->select(DB::raw('COUNT(*) as count'))->first()->count > 0 ? 'Tersedia' : 'N/A';

            // 3. Anomalies
            $anomaliesCount = $this->getAnomaliesData($branch, $activePeriod)->count();

            // 4. Stock alerts
            $stockAlertsCount = count($this->getStockForecastData($branch, 'all', $activePeriod));

            // 5. Dead Stock
            $deadStockCount = $this->getDeadStockData($branch, $activePeriod)->count();

            return [
                'outlets' => $totalOutlets,
                'bundles' => $bestBundle,
                'anomalies' => $anomaliesCount,
                'stock_alerts' => $stockAlertsCount,
                'dead_stock' => $deadStockCount,
            ];
        });

        $allPeriods = \App\Models\Period::ordered()->get();
        $uiData = [
            'selected_branch' => $branch,
            'summary' => $data
        ];

        return view('insights.index', ['data' => $uiData, 'activePeriod' => $activePeriod, 'allPeriods' => $allPeriods]);
    }

    // --- PILLAR 1: RFM ---
    public function rfm(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $activePeriod = $this->getSelectedPeriod($request);
        $periodIds = $this->get3MonthRange($activePeriod);
        [$startDate, $endDate] = $activePeriod->getRange();
        
        $rfmNow = Carbon::parse($endDate);

        $rfm = $this->cachedResult('rfm_v6', $activePeriod, function() use ($branch, $periodIds, $rfmNow) {
            $rfmQuery = Transaction::join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
                ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
                ->whereIn('upload_histories.period_id', $periodIds)
                ->select(
                    'outlets.name',
                    DB::raw('MAX(transactions.transaction_date) as last_order'),
                    DB::raw('COUNT(DISTINCT transactions.id) as frequency'),
                    DB::raw('SUM(transactions.total) as monetary')
                )
                ->groupBy('outlets.id', 'outlets.name');

            $this->applyBranchFilter($rfmQuery, $branch);

            return $rfmQuery->get()->map(function($item) use ($rfmNow) {
                $lastOrder = Carbon::parse($item->last_order);
                $item->days_since_order = $lastOrder->diffInDays($rfmNow);
                
                // Segment logic...
                $segment = 'New';
                $color = 'info';
                
                if ($item->days_since_order > 90) {
                    $segment = 'Lost';
                    $color = 'secondary';
                } elseif ($item->days_since_order > 60) {
                    $segment = 'At Risk';
                    $color = 'warning';
                } elseif ($item->frequency > 5 && $item->monetary > 10000000) {
                    $segment = 'Champion';
                    $color = 'success';
                } elseif ($item->days_since_order > 30) {
                    $segment = 'Sleeper';
                    $color = 'danger';
                }

                return [
                    'name' => $item->name,
                    'last_order' => $item->last_order,
                    'frequency' => (int) $item->frequency,
                    'monetary' => (float) $item->monetary,
                    'days_since_order' => (int) $item->days_since_order,
                    'segment' => $segment,
                    'color' => $color
                ];
            })->toArray();
        });

        $rfm = collect($rfm)->map(fn($i) => (object)$i);

        return view('insights.rfm', [
            'data' => $rfm,
            'selected_branch' => $branch,
            'activePeriod' => $activePeriod,
            'allPeriods' => \App\Models\Period::ordered()->get(),
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
        $activePeriod = $this->getSelectedPeriod($request);
        $periodIds = $this->get3MonthRange($activePeriod);

        $bundling = $this->cachedResult('bundling_v6', $activePeriod, function() use ($branch, $periodIds) {
            $query = DB::table('sales as s1')
                ->join('sales as s2', 's1.transaction_id', '=', 's2.transaction_id')
                ->join('transactions', 's1.transaction_id', '=', 'transactions.id')
                ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
                ->join('products as p1', 's1.product_id', '=', 'p1.id')
                ->join('products as p2', 's2.product_id', '=', 'p2.id')
                ->where('s1.product_id', '<', 's2.product_id')
                ->where('s1.total', '>', 0)
                ->where('s2.total', '>', 0)
                ->whereIn('upload_histories.period_id', $periodIds);

            $this->applyBranchFilter($query, $branch);

            return $query->select(
                    'p1.name as product_a',
                    'p2.name as product_b',
                    DB::raw('COUNT(DISTINCT s1.transaction_id) as times_bought_together')
                )
                ->groupBy('s1.product_id', 's2.product_id', 'p1.name', 'p2.name')
                ->orderByDesc('times_bought_together')
                ->limit(30)
                ->get();
        });

        return view('insights.bundling', [
            'data' => $bundling,
            'selected_branch' => $branch,
            'activePeriod' => $activePeriod,
            'allPeriods' => \App\Models\Period::ordered()->get()
        ]);
    }

    // --- PILLAR 3: DISCOUNTS ---
    public function discounts(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $activePeriod = $this->getSelectedPeriod($request);
        $periodIds = $this->get3MonthRange($activePeriod);
        
        $discounts = $this->cachedResult('discounts_v6', $activePeriod, function() use ($branch, $periodIds) {
            $query = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
                ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
                ->whereIn('upload_histories.period_id', $periodIds)
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
            return $query->get()->filter(fn($r) => $r->principle)->map(function($item) {
                return [
                    'principle' => $item->principle,
                    'gross_sales' => (float)$item->gross_sales,
                    'total_discount' => (float)$item->total_discount,
                    'net_sales' => (float)$item->net_sales,
                    'discount_ratio' => ($item->total_discount / ($item->gross_sales ?: 1)) * 100
                ];
            })->toArray();
        });

        $discounts = collect($discounts)->map(fn($i) => (object)$i);

        return view('insights.discounts', [
            'data' => $discounts,
            'selected_branch' => $branch,
            'activePeriod' => $activePeriod,
            'allPeriods' => \App\Models\Period::ordered()->get()
        ]);
    }

    // --- PILLAR 4: ANOMALIES ---
    public function anomalies(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $activePeriod = $this->getSelectedPeriod($request);
        $anomalies = $this->cachedResult('anomalies_v6', $activePeriod, function() use ($branch, $activePeriod) {
            return $this->getAnomaliesData($branch, $activePeriod);
        });

        return view('insights.anomalies', [
            'data' => $anomalies,
            'selected_branch' => $branch,
            'activePeriod' => $activePeriod,
            'allPeriods' => \App\Models\Period::ordered()->get()
        ]);
    }

    private function getAnomaliesData($branch, $period = null)
    {
        $period = $period ?? $this->getSelectedPeriod(request());
        $precedingIds = $period->getPrecedingIds(3);

        $query = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
            ->whereIn('upload_histories.period_id', $precedingIds)
            ->whereNotNull('transactions.meta')
            ->select(
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.sales_name')) as salesman"),
                DB::raw('SUM(CASE WHEN sales.total > 0 THEN sales.total ELSE 0 END) as gross_value'),
                DB::raw('ABS(SUM(CASE WHEN sales.total < 0 THEN sales.total ELSE 0 END)) as return_value')
            )
            ->groupBy('salesman')
            ->having('gross_value', '>', 0);
        
        $this->applyBranchFilter($query, $branch);
        return $query->get()->filter(fn($r) => $r->salesman)->map(function($item) {
            $item->return_rate = ($item->return_value / ($item->gross_value ?: 1)) * 100;
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
        $activePeriod = $this->getSelectedPeriod($request);

        // Fetch Principles list for the filter
        $principles = Transaction::whereNotNull('meta')
            ->select(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.principle_name')) as name"))
            ->distinct()
            ->pluck('name')
            ->filter()
            ->sort()
            ->values();

        $stockAlerts = $this->cachedResult('stock_forecast_v6', $activePeriod, function() use ($branch, $principle, $activePeriod) {
            return $this->getStockForecastData($branch, $principle, $activePeriod);
        });

        return view('insights.stock-forecast', [
            'data' => $stockAlerts,
            'selected_branch' => $branch,
            'selected_principle' => $principle,
            'principles' => $principles,
            'activePeriod' => $activePeriod,
            'allPeriods' => \App\Models\Period::ordered()->get()
        ]);
    }

    private function getStockForecastData($branch, $principle = 'all', $period = null)
    {
        $period = $period ?? $this->getSelectedPeriod(request());
        $precedingIds = $period->getPrecedingIds(3);
        if (count($precedingIds) < 3) return [];

        $query = Sale::join('products', 'sales.product_id', '=', 'products.id')
            ->join('transactions', 'sales.transaction_id', '=', 'transactions.id')
            ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
            ->whereIn('upload_histories.period_id', $precedingIds)
            ->select(
                'products.id', 'products.name',
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name')) as principle_name"),
                DB::raw('SUM(sales.qty) as total_qty_sold')
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

        // Filter stock by latest upload in the SELECTED period
        $latestUploadId = Stock::whereHas('uploadHistory', fn($q) => $q->where('period_id', $period->id))->max('upload_history_id');

        if ($latestUploadId) {
            $stockQuery->where('upload_history_id', $latestUploadId);
            if ($branchCode) {
                $stockQuery->where('branch', $branchCode);
            }
        } else {
            return []; // No stock data for this period
        }
        $currentStocks = $stockQuery->get()->keyBy('product_id');



        $alerts = [];
        foreach ($salesVelocity as $productId => $salesData) {
            if (!isset($currentStocks[$productId])) continue;
            $stock = $currentStocks[$productId]->current_stock;
            if ($stock <= 0) continue;
            $avgDailySales = $salesData->total_qty_sold / 90;
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
        $activePeriod = $this->getSelectedPeriod($request);

        $principles = Transaction::whereNotNull('meta')
            ->select(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.principle_name')) as name"))
            ->distinct()
            ->pluck('name')->filter()->sort()->values();

        $orderSuggestions = $this->cachedResult('purchase_order_v5', $activePeriod, function() use ($branch, $principle, $activePeriod) {
            return $this->getPurchaseOrderData($branch, $principle, $activePeriod);
        });

        return view('insights.purchase-order', [
            'data' => $orderSuggestions,
            'selected_branch' => $branch,
            'selected_principle' => $principle,
            'principles' => $principles,
            'activePeriod' => $activePeriod,
            'allPeriods' => \App\Models\Period::ordered()->get()
        ]);
    }

    private function getPurchaseOrderData($branch, $principle = 'all', $period = null)
    {
        $period = $period ?? $this->getSelectedPeriod(request());
        $precedingIds = $period->getPrecedingIds(3);
        if (count($precedingIds) < 3) return []; // Need at least 3 months history

        // Correct sequence: Dec(m1), Jan(m2), Feb(m3) for March(period)
        $p1 = Period::find($precedingIds[2]); // Dec
        $p2 = Period::find($precedingIds[1]); // Jan
        $p3 = Period::find($precedingIds[0]); // Feb

        $query = Sale::join('transactions', 'sales.transaction_id', '=', 'transactions.id')
            ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->whereIn('upload_histories.period_id', $precedingIds)
            ->select(
                'products.id', 'products.name',
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name')) as principle_name"),
                DB::raw('SUM(sales.qty) as total_qty_sold'),
                DB::raw("SUM(CASE WHEN upload_histories.period_id = {$p1->id} THEN sales.qty ELSE 0 END) as qty_m1"),
                DB::raw("SUM(CASE WHEN upload_histories.period_id = {$p2->id} THEN sales.qty ELSE 0 END) as qty_m2"),
                DB::raw("SUM(CASE WHEN upload_histories.period_id = {$p3->id} THEN sales.qty ELSE 0 END) as qty_m3")
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
        
        $latestUploadId = Stock::whereHas('uploadHistory', fn($q) => $q->where('period_id', $period->id))->max('upload_history_id');

        if ($latestUploadId) {
            $stockQuery->where('upload_history_id', $latestUploadId);
            if ($branchCode) {
                $stockQuery->where('branch', $branchCode);
            }
        } else {
            return [];
        }
        $currentStocks = $stockQuery->get()->keyBy('product_id');



        $results = [];
        foreach ($salesVelocity as $productId => $salesData) {
            $stock = isset($currentStocks[$productId]) ? $currentStocks[$productId]->current_stock : 0;
            $avgDaily = $salesData->total_qty_sold / 90;
            $avgMonthly = $salesData->total_qty_sold / 3;
            
            $results[] = (object) [
                'product_name' => $salesData->name,
                'principle_name' => $salesData->principle_name,
                'current_stock' => $stock,
                'avg_daily' => (float) $avgDaily,
                'avg_monthly' => (float) $avgMonthly,
                'm1_name' => \Carbon\Carbon::create($p1->year, $p1->month, 1)->translatedFormat('M y'),
                'm2_name' => \Carbon\Carbon::create($p2->year, $p2->month, 1)->translatedFormat('M y'),
                'm3_name' => \Carbon\Carbon::create($p3->year, $p3->month, 1)->translatedFormat('M y'),
                'qty_m1' => (float) ($salesData->qty_m1 ?? 0),
                'qty_m2' => (float) ($salesData->qty_m2 ?? 0),
                'qty_m3' => (float) ($salesData->qty_m3 ?? 0)
            ];
        }
        return $results;
    }

    // --- PILLAR 7: DEAD STOCK ---

    public function deadStock(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $activePeriod = $this->getSelectedPeriod($request);
        [$startDate, $endDate] = $this->get3MonthRange($activePeriod);
        $deadStock = $this->cachedResult('dead_stock_3m', $activePeriod, function() use ($branch, $activePeriod, $startDate, $endDate) {
            return $this->getDeadStockData($branch, $activePeriod);
        });

        return view('insights.dead-stock', [
            'data' => $deadStock,
            'selected_branch' => $branch,
            'activePeriod' => $activePeriod,
            'allPeriods' => \App\Models\Period::ordered()->get()
        ]);
    }

    private function getDeadStockData($branch, $period = null)
    {
        $period = $period ?? $this->getSelectedPeriod(request());
        $precedingIds = $period->getPrecedingIds(3);

        $soldProductIds = Sale::join('transactions', 'sales.transaction_id', '=', 'transactions.id')
            ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
            ->whereIn('upload_histories.period_id', $precedingIds)
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
        
        $latestUploadId = Stock::whereHas('uploadHistory', fn($q) => $q->where('period_id', $period->id))->max('upload_history_id');
        
        if ($latestUploadId) {
            $query->where('stocks.upload_history_id', $latestUploadId);
            if ($branchCode) {
                $query->where('stocks.branch', $branchCode);
            }
        } else {
            return collect();
        }

        return $query->get();
    }

    // --- PILLAR 7: GROWTH ---
    public function growth(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $activePeriod = $this->getSelectedPeriod($request);
        [$startDate, $endDate] = $activePeriod->getRange();

        // Comparison: This Month vs Last Month
        $prevPeriod = \App\Models\Period::where('year', ($activePeriod->month == 1 ? $activePeriod->year - 1 : $activePeriod->year))
            ->where('month', ($activePeriod->month == 1 ? 12 : $activePeriod->month - 1))
            ->first();

        $fetchGrowth = function($pIds) use ($branch) {
            $q = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
                ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
                ->whereIn('upload_histories.period_id', $pIds)
                ->select(
                    DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name')) as principle"),
                    DB::raw('SUM(sales.total) as total')
                )
                ->groupBy('principle');
            $this->applyBranchFilter($q, $branch);
            return $q->get()->keyBy('principle');
        };

        $reportData = $this->cachedResult('growth_v5', $activePeriod, function() use ($branch, $activePeriod, $prevPeriod, $fetchGrowth) {
            $currentMonth = $fetchGrowth([$activePeriod->id]);
            
            $previousMonth = collect();
            if ($prevPeriod) {
                $previousMonth = $fetchGrowth([$prevPeriod->id]);
            }

            $results = [];
            foreach ($currentMonth as $p => $curr) {
                $prevTotal = $previousMonth[$p]->total ?? 0;
                $growth = $prevTotal > 0 ? (($curr->total - $prevTotal) / $prevTotal) * 100 : 100;
                $results[] = (object) [
                    'principle' => $p,
                    'current' => $curr->total,
                    'previous' => $prevTotal,
                    'growth' => round($growth, 1)
                ];
            }
            usort($results, fn($a, $b) => $b->growth <=> $a->growth);
            return $results;
        });

        return view('insights.growth', [
            'data' => $reportData,
            'selected_branch' => $branch,
            'activePeriod' => $activePeriod,
            'allPeriods' => \App\Models\Period::ordered()->get()
        ]);
    }

    public function principalReport(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $activePeriod = $this->getSelectedPeriod($request);
        $periodIds = $this->get3MonthRange($activePeriod); // Assuming this method returns an array of period IDs
        
        // Fetch all possible principles for the dropdown within the 3-month window
        $principles = Transaction::join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
            ->whereIn('upload_histories.period_id', $periodIds)
            ->select(DB::raw('JSON_UNQUOTE(JSON_EXTRACT(meta, "$.principle_name")) as name'))
            ->distinct()->pluck('name')->filter(fn($n) => !empty($n) && $n !== 'Principle Name')->sort()->values();

        $selectedPrinciple = $request->query('principle', $principles[0] ?? null);

        if (!$selectedPrinciple) {
            return view('insights.principal-report', [
                'data' => null,
                'summary' => null, // Added for consistency
                'principles' => $principles,
                'selected_branch' => $branch,
                'selected_principle' => null,
                'activePeriod' => $activePeriod,
                'allPeriods' => \App\Models\Period::ordered()->get()
            ]);
        }

        $reportData = $this->cachedResult('principal_report_v8', $activePeriod, function() use ($selectedPrinciple, $branch, $periodIds, $activePeriod, $principles) {
            // Find the Principle ID for the selected name
            $principleId = Transaction::join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
                ->whereIn('upload_histories.period_id', $periodIds)
                ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(meta, "$.principle_name")) = ?', [$selectedPrinciple])
                ->select(DB::raw('JSON_UNQUOTE(JSON_EXTRACT(meta, "$.principle_id")) as pid'))
                ->value('pid');

            // 1. Summary Metrics
            $queryBase = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
                ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
                ->whereIn('upload_histories.period_id', $periodIds);
            
            if ($principleId) {
                $queryBase->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, "$.principle_id")) = ?', [$principleId]);
            } else {
                $queryBase->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, "$.principle_name")) = ?', [$selectedPrinciple]);
            }
            
            $this->applyBranchFilter($queryBase, $branch);

            $summary = (clone $queryBase)->select(
                DB::raw('SUM(CASE WHEN sales.total > 0 THEN sales.total ELSE 0 END) as gross_value'),
                DB::raw('ABS(SUM(CASE WHEN sales.total < 0 THEN sales.total ELSE 0 END)) as return_value'),
                DB::raw('SUM(sales.total) as net_value'),
                DB::raw('SUM(sales.qty) as total_qty'),
                DB::raw('COUNT(DISTINCT transactions.outlet_id) as total_outlets')
            )->first();

            // 2. Trend (Weekly)
            $trend = (clone $queryBase)->select(
                DB::raw('DATE_FORMAT(transactions.transaction_date, "%Y-%u") as week'),
                DB::raw('MIN(transactions.transaction_date) as week_start'),
                DB::raw('SUM(sales.total) as total')
            )->groupBy('week')->orderBy('week')->get();

            // 2b. Monthly Growth Series (for the chart)
            // We want the 3 active months + 1 baseline month
            $activePeriods = \App\Models\Period::whereIn('id', $periodIds)->orderBy('year')->orderBy('month')->get();
            $baselinePeriod = $activePeriods->first()->getPrecedingIds(1);
            $allGrowthPeriodIds = array_merge($baselinePeriod, $activePeriods->pluck('id')->toArray());
            
            $monthlyData = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
                ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
                ->whereIn('upload_histories.period_id', $allGrowthPeriodIds);
            
            if ($principleId) {
                $monthlyData->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, "$.principle_id")) = ?', [$principleId]);
            } else {
                $monthlyData->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, "$.principle_name")) = ?', [$selectedPrinciple]);
            }
            $this->applyBranchFilter($monthlyData, $branch);
            
            $monthlySales = $monthlyData->select(
                'upload_histories.period_id',
                DB::raw('SUM(sales.total) as total')
            )->groupBy('upload_histories.period_id')->get()->keyBy('period_id');

            $growthSeries = [];
            $prevVal = null;
            foreach ($allGrowthPeriodIds as $pId) {
                $currVal = $monthlySales[$pId]->total ?? 0;
                $pModel = \App\Models\Period::find($pId);
                if ($prevVal !== null) {
                    $growthSeries[] = [
                        'month' => $pModel->name,
                        'value' => $currVal,
                        'growth' => $prevVal > 0 ? (($currVal - $prevVal) / $prevVal) * 100 : ($currVal > 0 ? 100 : 0)
                    ];
                }
                $prevVal = $currVal;
            }

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

            // 6. City Analysis
            $cityAnalysis = (clone $queryBase)->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
                ->select('outlets.city', DB::raw('SUM(sales.total) as value'))
                ->whereNotNull('outlets.city')
                ->groupBy('outlets.city')->orderByDesc('value')->get();

            // 7. Growth (Current 3 Months vs Previous 3 Months)
            $earliestPeriod = \App\Models\Period::whereIn('id', $periodIds)->orderBy('year')->orderBy('month')->first();
            $prev3PeriodIds = $earliestPeriod ? $earliestPeriod->getPrecedingIds(3) : [];
            
            $currVal = $summary->net_value ?? 0;
            $prevVal = 0;
            if (count($prev3PeriodIds) > 0) {
                $prevValQuery = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
                    ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
                    ->whereIn('upload_histories.period_id', $prev3PeriodIds);

                if ($principleId) {
                    $prevValQuery->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, "$.principle_id")) = ?', [$principleId]);
                } else {
                    $prevValQuery->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, "$.principle_name")) = ?', [$selectedPrinciple]);
                }

                $this->applyBranchFilter($prevValQuery, $branch);
                $prevVal = $prevValQuery->sum('sales.total');
            }

                        $growthVal = ($prevVal > 0) ? (($currVal - $prevVal) / $prevVal) * 100 : (($currVal > 0) ? 100 : 0);

            // 8. Sleeper Outlets
            $rfmNow = \Carbon\Carbon::now(); // Use current date for recency calculation
            $lastOrders = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
                ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
                ->whereIn('upload_histories.period_id', $periodIds) // Filter by period IDs
                ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, "$.principle_name")) = ?', [$selectedPrinciple])
                ->select('transactions.outlet_id', DB::raw('MAX(transactions.transaction_date) as last_date'), DB::raw('SUM(sales.total) as total_contribution'))
                ->groupBy('transactions.outlet_id')
                ->orderByDesc('total_contribution')->limit(50)->get();
            
            $sleepers = $lastOrders->filter(function($o) use ($rfmNow) {
                return \Carbon\Carbon::parse($o->last_date)->diffInDays($rfmNow) > 14;
            })->take(5);
            if ($sleepers->count() > 0) {
                $sleeperIds = $sleepers->pluck('outlet_id')->toArray();
                $sleeperDetails = \App\Models\Outlet::whereIn('id', $sleeperIds)->get()->keyBy('id');
                foreach($sleepers as $s) {
                    $s->name = $sleeperDetails[$s->outlet_id]->name ?? 'Unknown';
                    $s->days = \Carbon\Carbon::parse($s->last_date)->diffInDays($rfmNow);
                }
            }

            // 9. Product Return Audit
            $returns = (clone $queryBase)->join('products', 'sales.product_id', '=', 'products.id')
                ->where('sales.total', '<', 0)
                ->select('products.name', DB::raw('ABS(SUM(sales.total)) as value'), DB::raw('ABS(SUM(sales.qty)) as qty'))
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('value')->limit(5)->get();

            return compact('summary', 'trend', 'growthSeries', 'topProducts', 'topOutlets', 'topSalesmen', 'cityAnalysis', 'growthVal', 'currVal', 'sleepers', 'returns');
        });

        return view('insights.principal-report', [
            'principles' => $principles,
            'selected_principle' => $selectedPrinciple,
            'selected_branch' => $branch,
            'summary' => $reportData['summary'],
            'trend' => $reportData['trend'],
            'growthSeries' => $reportData['growthSeries'],
            'topProducts' => $reportData['topProducts'],
            'topOutlets' => $reportData['topOutlets'],
            'topSalesmen' => $reportData['topSalesmen'],
            'cityAnalysis' => $reportData['cityAnalysis'],
            'growth3m' => round($reportData['growthVal'], 1),
            'sleepers' => $reportData['sleepers'],
            'returns' => $reportData['returns'],
            'activePeriod' => $activePeriod,
            'allPeriods' => \App\Models\Period::ordered()->get()
        ]);
    }

    // --- PILLAR 8: GUIDE ---

    public function guide()
    {
        return view('insights.guide');
    }
}
