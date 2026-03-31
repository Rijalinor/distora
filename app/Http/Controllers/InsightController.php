<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Period;
use App\Models\MonthlyProductSalesStat;
use App\Models\UploadHistory;
use App\Models\MlForecastRun;
use App\Services\MlForecastEvaluator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
        $fullKey = "insight_v2_{$key}_{$period->id}_{$branch}_" . md5(json_encode(request()->all()));

        if ($period->status === 'closed') {
            // Historical data never changes - cache forever in file
            return Cache::driver('file')->rememberForever($fullKey, $callback);
        }

        // Active period data changes - cache for 10 minutes in file
        return Cache::driver('file')->remember($fullKey, 600, $callback);
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
        // 3-month window that includes the selected period.
        $ids = array_merge([$period->id], $period->getPrecedingIds(2));
        return array_values(array_unique($ids));
    }

    /**
     * 3-month historical window before active period (exclude active).
     */
    private function get3MonthsBeforeActive(\App\Models\Period $period): array
    {
        return $period->getPrecedingIds(3);
    }

    /**
     * ML lookback window (up to N historical periods if available).
     */
    private function getMlLookbackRange(\App\Models\Period $period, int $maxMonths = 24): array
    {
        return $period->getPrecedingIds($maxMonths);
    }

    private function getCutoffDate($period = null)
    {
        // No longer needed for date-based window, returning null or dummy
        return null;
    }

    private function recordForecastRun(array $payload): void
    {
        try {
            $key = [
                'context' => $payload['context'] ?? 'unknown',
                'period_id' => $payload['period_id'] ?? null,
                'branch' => $payload['branch'] ?? null,
                'scope_key' => $payload['scope_key'] ?? null,
                'entity_type' => $payload['entity_type'] ?? null,
                'entity_id' => isset($payload['entity_id']) ? (string) $payload['entity_id'] : null,
            ];

            MlForecastRun::updateOrCreate($key, [
                'entity_name' => $payload['entity_name'] ?? null,
                'model' => $payload['model'] ?? null,
                'is_ml' => (bool) ($payload['is_ml'] ?? false),
                'prediction' => $payload['prediction'] ?? null,
                'prediction_low' => $payload['prediction_low'] ?? null,
                'prediction_high' => $payload['prediction_high'] ?? null,
                'confidence' => $payload['confidence'] ?? null,
                'wape' => $payload['wape'] ?? null,
                'mape' => $payload['mape'] ?? null,
                'mae' => $payload['mae'] ?? null,
                'rmse' => $payload['rmse'] ?? null,
                'forecasted_at' => now(),
                'meta' => $payload['meta'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to record ML forecast run', ['error' => $e->getMessage()]);
        }
    }

    public function aiDashboard(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $activePeriod = $this->getSelectedPeriod($request);

        $allPeriods = Period::orderBy('id', 'desc')->get();
        
        $advisorData = $this->getAiAdvisorData($branch, $activePeriod);
        $advisorCount = count($advisorData);

        return view('insights.ai-dashboard', compact('activePeriod', 'allPeriods', 'branch', 'advisorCount'));
    }

    public function mlMonitor(Request $request)
    {
        $activePeriod = $this->getSelectedPeriod($request);
        $branch = $request->query('branch', 'all');
        $context = $request->query('context', 'all');

        app(MlForecastEvaluator::class)->evaluatePending($activePeriod->id ?? null, $context, $branch, 800);

        $query = MlForecastRun::query()
            ->when($activePeriod?->id, fn ($q) => $q->where('period_id', $activePeriod->id))
            ->when($branch !== 'all', fn ($q) => $q->where('branch', $branch))
            ->when($context !== 'all', fn ($q) => $q->where('context', $context));

        $rows = (clone $query)->orderByDesc('forecasted_at')->limit(300)->get();

        $summary = [
            'total_runs' => $rows->count(),
            'ml_runs' => $rows->where('is_ml', true)->count(),
            'avg_confidence' => round((float) ($rows->avg('confidence') ?? 0), 2),
            'avg_wape' => round((float) ($rows->whereNotNull('wape')->avg('wape') ?? 0), 2),
            'evaluated_runs' => $rows->whereNotNull('actual_value')->count(),
            'avg_error_pct' => round((float) ($rows->whereNotNull('error_pct')->avg('error_pct') ?? 0), 2),
        ];

        $byContext = $rows->groupBy('context')->map(function ($items) {
            return [
                'count' => $items->count(),
                'avg_confidence' => round((float) ($items->avg('confidence') ?? 0), 2),
                'avg_wape' => round((float) ($items->whereNotNull('wape')->avg('wape') ?? 0), 2),
            ];
        })->sortByDesc('count');

        return view('insights.ml-monitor', [
            'rows' => $rows,
            'summary' => $summary,
            'byContext' => $byContext,
            'activePeriod' => $activePeriod,
            'allPeriods' => Period::ordered()->get(),
            'selected_branch' => $branch,
            'selected_context' => $context,
            'contexts' => MlForecastRun::query()->distinct()->orderBy('context')->pluck('context'),
        ]);
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

            // 3. AI Advisor (Pillar 4)
            $advisorData = $this->getAiAdvisorData($branch, $activePeriod);
            $advisorCount = count($advisorData);

            // 4. Stock alerts
            $stockAlertsCount = count($this->getStockForecastData($branch, 'all', $activePeriod));

            // 5. Dead Stock
            $deadStockCount = $this->getDeadStockData($branch, $activePeriod)->count();

            return [
                'outlets' => $totalOutlets,
                'bundles' => $bestBundle,
                'advisor' => $advisorCount,
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
                'sultans' => $rfm->where('segment', 'Champion')->count(),
                'sleepers' => $rfm->where('segment', 'Sleeper')->count(),
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
        $range = $request->query('range', '3');
        $periodIds = ($range == '1') ? [$activePeriod->id] : $this->get3MonthRange($activePeriod);
        $selectedPrinciple = $request->query('principle_detail');
        
        $cacheKey = 'discounts_v14_' . $range . '_' . ($selectedPrinciple ? md5($selectedPrinciple) : 'overview');

        $data = $this->cachedResult($cacheKey, $activePeriod, function() use ($branch, $periodIds, $selectedPrinciple, $range) {
            $principles = []; $summary = []; $trend = []; $mode = 'overview';
            
            if ($selectedPrinciple) {
                // PRODUCT LEVEL DETAIL
                $query = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
                    ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
                    ->join('products', 'sales.product_id', '=', 'products.id')
                    ->whereIn('upload_histories.period_id', $periodIds)
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(sales.raw_data, '$.principle_name')) = ?", [$selectedPrinciple])
                    ->where('sales.total', '>', 0)
                    ->select(
                        'products.name as item_name',
                        DB::raw('SUM(sales.gross_price) as gross_sales'),
                        DB::raw('SUM(sales.disc_item) as disc_item'),
                        DB::raw('SUM(sales.disc_internal + sales.disc_external + sales.disc_invoice) as disc_other'),
                        DB::raw('SUM(sales.disc_item) as total_discount'),
                        DB::raw('SUM(sales.total) as net_sales')
                    )
                    ->groupBy('products.id', 'products.name')
                    ->having('gross_sales', '>', 0)
                    ->orderByDesc('net_sales');
                
                $this->applyBranchFilter($query, $branch);
                $items = $query->get()->map(function($item) {
                    return (object) [
                        'name' => $item->item_name,
                        'gross_sales' => (float)$item->gross_sales,
                        'disc_item' => (float)$item->disc_item,
                        'disc_other' => (float)$item->disc_other,
                        'total_discount' => (float)$item->total_discount,
                        'net_sales' => (float)$item->net_sales,
                        'discount_ratio' => ($item->total_discount / ($item->gross_sales ?: 1)) * 100
                    ];
                });

                $totalGross = $items->sum('gross_sales');
                $totalDisc = $items->sum('total_discount');
                $summary = [
                    'total_gross' => $items->sum('gross_sales'),
                    'total_discount' => $items->sum('total_discount'),
                    'total_net' => $items->sum('net_sales'),
                    'avg_ratio' => $items->sum('gross_sales') > 0 ? ($items->sum('total_discount') / $items->sum('gross_sales')) * 100 : 0,
                    'type_breakdown' => [
                        'Item' => (float)$items->sum('disc_item'),
                        'Invoice' => (float)$items->sum('disc_other'),
                    ]
                ];
                // User clarified that `disc_item` is the correct source for promotion effectiveness.

                $trendQuery = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
                    ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
                    ->whereIn('upload_histories.period_id', $periodIds)
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(sales.raw_data, '$.principle_name')) = ?", [$selectedPrinciple])
                    ->select(
                        'upload_histories.period_id',
                        DB::raw('SUM(sales.gross_price) as gross'),
                        DB::raw('SUM(sales.disc_item) as discount')
                    )
                    ->groupBy('upload_histories.period_id');
                $this->applyBranchFilter($trendQuery, $branch);
                $trendRaw = $trendQuery->get()->keyBy('period_id');

                $trend = [];
                foreach (array_reverse($periodIds) as $pId) {
                    $p = \App\Models\Period::find($pId);
                    $row = $trendRaw[$pId] ?? (object)['gross' => 0, 'discount' => 0];
                    $trend[] = [
                        'month' => $p->name,
                        'ratio' => $row->gross > 0 ? ($row->discount / $row->gross) * 100 : 0,
                        'discount' => (float)$row->discount,
                        'revenue' => (float)($row->gross - $row->discount)
                    ];
                }

                $principles = $items->toArray();
                $mode = 'detail';

            } else {
                // OVERVIEW
                $query = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
                    ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
                    ->whereIn('upload_histories.period_id', $periodIds)
                    ->where('sales.total', '>', 0)
                    ->select(
                        DB::raw("JSON_UNQUOTE(JSON_EXTRACT(sales.raw_data, '$.principle_name')) as principle"),
                        DB::raw('SUM(sales.gross_price) as gross_sales'),
                        DB::raw('SUM(sales.disc_item) as disc_item'),
                        DB::raw('SUM(sales.disc_internal) as disc_internal'),
                        DB::raw('SUM(sales.disc_external) as disc_external'),
                        DB::raw('SUM(sales.disc_invoice) as disc_invoice'),
                        DB::raw('SUM(sales.disc_item) as total_discount'),
                        DB::raw('SUM(sales.total) as net_sales')
                    )
                    ->groupBy('principle')
                    ->having('gross_sales', '>', 0)
                    ->orderByDesc('net_sales');
                
                $this->applyBranchFilter($query, $branch);
                $principlesRaw = $query->get()->filter(fn($r) => $r->principle)->map(function($item) {
                    return (object) [
                        'principle' => $item->principle,
                        'gross_sales' => (float)$item->gross_sales,
                        'disc_item' => (float)$item->disc_item,
                        'disc_internal' => (float)$item->disc_internal,
                        'disc_external' => (float)$item->disc_external,
                        'disc_invoice' => (float)$item->disc_invoice,
                        'total_discount' => (float)$item->total_discount,
                        'net_sales' => (float)$item->net_sales,
                        'discount_ratio' => ($item->total_discount / ($item->gross_sales ?: 1)) * 100
                    ];
                });

                $totalGross = $principlesRaw->sum('gross_sales');
                $totalDisc = $principlesRaw->sum('total_discount');
                $summary = [
                    'total_gross' => $totalGross,
                    'total_discount' => $totalDisc,
                    'total_net' => $principlesRaw->sum('net_sales'),
                    'avg_ratio' => $totalGross > 0 ? ($totalDisc / $totalGross) * 100 : 0,
                    'type_breakdown' => [
                        'Item' => (float)$principlesRaw->sum('disc_item'),
                        'Lainnya' => (float)$principlesRaw->sum(fn($p) => $p->disc_internal + $p->disc_external + $p->disc_invoice),
                    ]
                ];

                $trendQuery = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
                    ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
                    ->whereIn('upload_histories.period_id', $periodIds)
                    ->select(
                        'upload_histories.period_id',
                        DB::raw('SUM(sales.gross_price) as gross'),
                        DB::raw('SUM(sales.disc_item) as discount')
                    )
                    ->groupBy('upload_histories.period_id');
                $this->applyBranchFilter($trendQuery, $branch);
                $trendRaw = $trendQuery->get()->keyBy('period_id');

                $trend = [];
                foreach (array_reverse($periodIds) as $pId) {
                    $p = \App\Models\Period::find($pId);
                    $row = $trendRaw[$pId] ?? (object)['gross' => 0, 'discount' => 0];
                    $trend[] = [
                        'month' => $p->name,
                        'ratio' => $row->gross > 0 ? ($row->discount / $row->gross) * 100 : 0,
                        'discount' => (float)$row->discount,
                        'revenue' => (float)($row->gross - $row->discount)
                    ];
                }

                $principles = $principlesRaw->toArray();
                $mode = 'overview';
            }

            // --- SUPERVISOR INSIGHTS ENGINE ---
            $actions = [];
            $principlesColl = collect($principles);
            $avgRatio = $summary['avg_ratio'];

            if ($mode == 'overview') {
                if ($avgRatio > 10) {
                    $actions[] = "⚠️ **Peringatan Rasio Tinggi**: Rasio diskon total mencapai " . number_format($avgRatio, 1) . "%. Supervisor harus meninjau ulang semua promo invoice yang aktif.";
                }
                
                $highestBurner = $principlesColl->sortByDesc('discount_ratio')->first();
                if ($highestBurner && $highestBurner->discount_ratio > 15) {
                    $actions[] = "🔥 **Audit High Burner**: Prinsipel `" . $highestBurner->principle . "` memiliki rasio " . number_format($highestBurner->discount_ratio, 1) . "%. Segera evaluasi apakah pertumbuhan omzet sebanding dengan subsidi promo.";
                }

                $invoiceDisc = $summary['type_breakdown']['Lainnya'] ?? 0;
                $totalDiscValue = $summary['total_discount'];
                if ($invoiceDisc > 0 && $totalDiscValue > 0 && ($invoiceDisc / $totalDiscValue) > 0.4) {
                    $actions[] = "🕵️ **Audit Diskon Non-Item**: Komponen diskon selain item cukup besar dibanding promo item. Supervisor disarankan audit diskon internal/external/invoice.";
                }
            } else {
                $topItem = $principlesColl->sortByDesc('discount_ratio')->first();
                if ($topItem && $topItem->discount_ratio > 15) {
                    $actions[] = "📉 **Item Over-Discount**: Produk `" . $topItem->name . "` dibakar dengan rasio " . number_format($topItem->discount_ratio, 1) . "%. Turunkan intensitas promo jika stok mulai menipis.";
                }
                
                $starItem = $principlesColl->where('discount_ratio', '<', 5)->sortByDesc('net_sales')->first();
                if ($starItem) {
                    $actions[] = "⭐ **Peluang Expansi**: Produk `" . $starItem->name . "` sangat efisien (Rasio " . number_format($starItem->discount_ratio, 1) . "%). Tambah display atau alihkan sedikit budget promo ke sini untuk genjot omzet.";
                }
            }

            if (count($actions) < 2) {
                $actions[] = "✅ **Kesehatan Promo**: Performa diskon saat ini terpantau stabil. Lanjutkan pemantauan harian.";
            }

            return [
                'principles' => $principles,
                'summary' => $summary,
                'trend' => $trend,
                'mode' => $mode,
                'supervisor_actions' => $actions,
                'range' => $range
            ];
        });

        return view('insights.discounts', [
            'data' => collect($data['principles']),
            'summary' => (object)$data['summary'],
            'trend' => $data['trend'],
            'mode' => $data['mode'],
            'supervisor_actions' => $data['supervisor_actions'],
            'selected_principle' => $selectedPrinciple,
            'selected_branch' => $branch,
            'selected_range' => $data['range'],
            'activePeriod' => $activePeriod,
            'allPeriods' => \App\Models\Period::ordered()->get()
        ]);
    }

    // --- PILLAR 4: AI DECISION ADVISOR ---
    public function aiAdvisor(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $activePeriod = $this->getSelectedPeriod($request);
        $advisorCards = $this->cachedResult('ai_advisor_v2', $activePeriod, function() use ($branch, $activePeriod) {
            return $this->getAiAdvisorData($branch, $activePeriod);
        });

        return view('insights.ai-advisor', [
            'cards' => $advisorCards,
            'selected_branch' => $branch,
            'activePeriod' => $activePeriod,
            'allPeriods' => \App\Models\Period::ordered()->get()
        ]);
    }

    public function salesmanAudit(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $activePeriod = $this->getSelectedPeriod($request);
        $anomalies = $this->getAnomaliesData($branch, $activePeriod);

        return view('insights.salesman-audit', [
            'data' => $anomalies,
            'selected_branch' => $branch,
            'activePeriod' => $activePeriod,
            'allPeriods' => \App\Models\Period::ordered()->get()
        ]);
    }

    public function anomalies(Request $request)
    {
        return $this->aiAdvisor($request);
    }

    private function getAiAdvisorData($branch, $period = null)
    {
        $period = $period ?? $this->getSelectedPeriod(request());
        $precedingIds = $this->get3MonthRange($period);
        $cards = [];

        // 1. ANOMALY: High Returns
        $returns = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
            ->whereIn('upload_histories.period_id', $precedingIds)
            ->whereNotNull('transactions.meta')
            ->select(
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.sales_name')) as salesman"),
                DB::raw('SUM(CASE WHEN sales.total > 0 THEN sales.total ELSE 0 END) as gross'),
                DB::raw('ABS(SUM(CASE WHEN sales.total < 0 THEN sales.total ELSE 0 END)) as returns')
            )
            ->groupBy('salesman')
            ->having('gross', '>', 0);
        $this->applyBranchFilter($returns, $branch);
        $returns = $returns->get();

        foreach ($returns as $r) {
            $rate = ($r->returns / $r->gross) * 100;
            if ($rate > 5) {
                $cards[] = [
                    'type' => 'danger',
                    'title' => 'Audit Salesman: Return Tinggi',
                    'desc' => "Salesman <strong>{$r->salesman}</strong> memiliki rasio retur " . round($rate, 1) . "%, jauh di atas batas aman 2%.",
                    'action' => 'Cek Nota Retur',
                    'link' => route('insights.salesman-audit', ['branch' => $branch, 'period_id' => $period->id, 'salesman' => $r->salesman]),
                    'icon' => '🕵️'
                ];
            }
        }

        // 2. RISK: Stockout Opportunity (using Smart AI Prediction)
        $pData = $this->getPurchaseOrderData($branch, 'all', $period);
        $criticalStock = collect($pData)->filter(function($i) {
            $daily = $i->ai_prediction / 30;
            $days = ($daily > 0) ? ($i->current_stock / $daily) : 999;
            $i->days_calculated = $days;
            return $days < 7 && $i->ai_prediction > 0;
        })->sortBy('days_calculated')->take(5);

        foreach ($criticalStock as $s) {
            $volNote = $s->volatility > 0.5 ? " <span style='color:var(--warning)'>[Akurasi Rendah: Jualan Fluktuatif]</span>" : "";
            $cards[] = [
                'type' => 'warning',
                'title' => 'Risiko Stok Habis (AI S1)',
                'desc' => "Produk <strong>{$s->product_name}</strong> ({$s->category}) diprediksi habis dalam <strong>" . round($s->days_calculated, 1) . " hari</strong> menurut tren AI.{$volNote}",
                'action' => 'Buka Order Pabrik',
                'link' => route('insights.purchase-order', ['branch' => $branch, 'period_id' => $period->id]),
                'icon' => '🚨'
            ];
        }

        // 3. OPPORTUNITY: Category Momentum & Growth
        // Find categories that are growing (> 1.2 momentum)
        $growthOpportunity = collect($pData)->filter(fn($i) => $i->ai_trend === 'growing' && $i->ai_confidence > 70)->sortByDesc('ai_prediction')->first();

        if ($growthOpportunity) {
            $cards[] = [
                'type' => 'info',
                'title' => 'Peluang Akselerasi: ' . $growthOpportunity->category,
                'desc' => "Produk <strong>{$growthOpportunity->product_name}</strong> menunjukkan tren pertumbuhan kuat (" . round($growthOpportunity->ai_confidence) . "% confidence). Pertimbangkan tambah stok ekstra.",
                'action' => 'Lihat Tren Brand',
                'link' => route('insights.principal-report', ['branch' => $branch, 'period_id' => $period->id]),
                'icon' => '📈'
            ];
        }

        // 4. AUDIT: Promo Fatigue (Optionally add)
        if (count($cards) < 4) {
            $cards[] = [
                'type' => 'info',
                'title' => 'Optimasi Promo',
                'desc' => "Analisis S1 menunjukkan efektivitas diskon di cabang ini cukup stabil. AI menyarankan evaluasi SKU yang stagnan meskipun ada promo.",
                'action' => 'Evaluasi Diskon',
                'link' => route('insights.discounts', ['branch' => $branch, 'period_id' => $period->id]),
                'icon' => '💸'
            ];
        }

        return $cards;
    }

    // Keep original anomalies check for helper
    private function getAnomaliesData($branch, $period = null)
    {
        $period = $period ?? $this->getSelectedPeriod(request());
        $precedingIds = $this->get3MonthRange($period);

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

        $stockAlerts = $this->cachedResult('stock_forecast_v7', $activePeriod, function() use ($branch, $principle, $activePeriod) {
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
        $precedingIds = $this->get3MonthsBeforeActive($period);
        if (count($precedingIds) < 3) return [];

        $query = MonthlyProductSalesStat::query()
            ->join('products', 'monthly_product_sales_stats.product_id', '=', 'products.id')
            ->whereIn('monthly_product_sales_stats.period_id', $precedingIds)
            ->select(
                'products.id',
                'products.name',
                'monthly_product_sales_stats.principle_name',
                DB::raw('SUM(monthly_product_sales_stats.qty_sold) as total_qty_sold')
            )
            ->groupBy('products.id', 'products.name', 'monthly_product_sales_stats.principle_name')
            ->having('total_qty_sold', '>', 0);

        if ($branch !== 'all') {
            $query->where('monthly_product_sales_stats.branch_dist_id', $branch);
        }

        if ($principle !== 'all') {
            $query->where('monthly_product_sales_stats.principle_name', $principle);
        }

        $salesVelocity = $query->get();

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
        foreach ($salesVelocity as $salesData) {
            $productId = $salesData->id;
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
        $isAiMode = $request->query('mode') === 'ai';

        $principles = Transaction::whereNotNull('meta')
            ->select(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.principle_name')) as name"))
            ->distinct()
            ->pluck('name')->filter()->sort()->values();

        $orderSuggestions = $this->cachedResult('purchase_order_v10', $activePeriod, function() use ($branch, $principle, $activePeriod) {
            return $this->getPurchaseOrderData($branch, $principle, $activePeriod);
        });

        return view('insights.purchase-order', [
            'data' => $orderSuggestions,
            'selected_branch' => $branch,
            'selected_principle' => $principle,
            'principles' => $principles,
            'activePeriod' => $activePeriod,
            'allPeriods' => \App\Models\Period::ordered()->get(),
            'isAiMode' => $isAiMode
        ]);
    }

    private function getPurchaseOrderData($branch, $principle = 'all', $period = null)
    {
        $period = $period ?? $this->getSelectedPeriod(request());
        $precedingIds = $this->getMlLookbackRange($period, 24);
        if (count($precedingIds) < 3) return [];

        // For UI display, we still want the last 3
        $displayIds = $period->getPrecedingIds(3);
        $p1 = isset($displayIds[2]) ? Period::find($displayIds[2]) : null;
        $p2 = isset($displayIds[1]) ? Period::find($displayIds[1]) : null;
        $p3 = isset($displayIds[0]) ? Period::find($displayIds[0]) : null;

        $query = MonthlyProductSalesStat::query()
            ->leftJoin('products', 'monthly_product_sales_stats.product_id', '=', 'products.id')
            ->whereIn('monthly_product_sales_stats.period_id', $precedingIds)
            ->select(
                'products.id',
                'products.name',
                'products.category',
                'monthly_product_sales_stats.period_id',
                'monthly_product_sales_stats.principle_name',
                DB::raw('SUM(monthly_product_sales_stats.qty_sold) as qty'),
                DB::raw('SUM(monthly_product_sales_stats.total_net) as total_net'),
                DB::raw('SUM(monthly_product_sales_stats.total_disc_item) as total_disc')
            )
            ->groupBy(
                'products.id',
                'products.name',
                'products.category',
                'monthly_product_sales_stats.principle_name',
                'monthly_product_sales_stats.period_id'
            );

        if ($branch !== 'all') {
            $query->where('monthly_product_sales_stats.branch_dist_id', $branch);
        }

        if ($principle !== 'all') {
            $query->where('monthly_product_sales_stats.principle_name', $principle);
        }

        $salesRaw = $query->get();
        $salesVelocity = [];
        $mlInput = [];
        
        // Fetch Historical Stocks for Stockout Recovery AI
        $histStockMap = []; 
        $stockMapNames = ['OBM_01' => 'bjm', 'OBM_02' => 'brb', 'OBM_03' => 'btl'];
        $bCode = ($branch !== 'all') ? ($stockMapNames[$branch] ?? $branch) : null;
        
        $histUploadMap = Stock::whereHas('uploadHistory', fn($q) => $q->whereIn('period_id', $precedingIds))
            ->when($bCode, fn($q) => $q->where('branch', $bCode))
            ->join('upload_histories', 'stocks.upload_history_id', '=', 'upload_histories.id')
            ->select('upload_histories.period_id', 'stocks.upload_history_id')
            ->distinct()
            ->pluck('upload_history_id', 'period_id');
        
        if ($histUploadMap->count() > 0) {
            $histStocksRaw = Stock::whereIn('upload_history_id', $histUploadMap->values())
                ->select('product_id', 'upload_history_id', 'on_hand_base')
                ->get();
            
            $uIdToPId = $histUploadMap->flip();
            foreach ($histStocksRaw as $hs) {
                $pId = $uIdToPId[$hs->upload_history_id];
                $histStockMap[$pId][$hs->product_id] = $hs->on_hand_base;
            }
        }
        
        // Reverse precedingIds to get chronological order for ML
        $chronoPeriods = array_reverse($precedingIds);
        $periodMap = array_flip($chronoPeriods); // map period_id -> index 0..5

        // 🎓 S1 UPGRADE: Categorical Momentum Analysis
        $catTrends = []; 
        foreach ($salesRaw as $row) {
            $catTrends[$row->category][$row->period_id] = ($catTrends[$row->category][$row->period_id] ?? 0) + $row->qty;
        }

        foreach ($salesRaw as $row) {
            if (!isset($salesVelocity[$row->id])) {
                $salesVelocity[$row->id] = (object) [
                    'id' => $row->id,
                    'name' => $row->name,
                    'category' => $row->category,
                    'principle_name' => $row->principle_name,
                    'total_qty_sold' => 0,
                    'qty_m1' => 0, 'qty_m2' => 0, 'qty_m3' => 0
                ];
            }
            $v = &$salesVelocity[$row->id];
            $v->total_qty_sold += $row->qty;
            
            if ($p1 && $row->period_id == $p1->id) $v->qty_m1 += $row->qty;
            if ($p2 && $row->period_id == $p2->id) $v->qty_m2 += $row->qty;
            if ($p3 && $row->period_id == $p3->id) $v->qty_m3 += $row->qty;

            // Prepare ML data: chronological index
            $idx = $periodMap[$row->period_id];
            
            // Calculate Category Growth for this period relative to the category's average
            $catAvg = array_sum($catTrends[$row->category]) / count($precedingIds);
            $catMomentum = ($catAvg > 0) ? ($catTrends[$row->category][$row->period_id] / $catAvg) : 1;

            if (!isset($mlInput[$row->id][$idx])) {
                $mlInput[$row->id][$idx] = [
                    'qty' => 0, 
                    'promo' => 0, 
                    'stockout' => 0,
                    'cat_momentum' => $catMomentum
                ];
            }
            $mlInput[$row->id][$idx]['qty'] += (float)$row->qty;
            $gross = (float)$row->total_net + (float)$row->total_disc;
            $mlInput[$row->id][$idx]['promo'] += ($gross > 0) ? ((float)$row->total_disc / $gross) * 100 : 0;
            
            // Check if it was a stockout month
            $hStock = $histStockMap[$row->period_id][$row->id] ?? 1;
            if ($hStock <= 0) $mlInput[$row->id][$idx]['stockout'] = 1;
        }

        $stockMap = ['OBM_01' => 'bjm', 'OBM_02' => 'brb', 'OBM_03' => 'btl'];
        $branchCode = ($branch !== 'all') ? ($stockMap[$branch] ?? $branch) : null;

        $stockQuery = Stock::select('product_id', DB::raw('SUM(on_hand_base) as current_stock'))->groupBy('product_id');
        
        // Find latest stock for the selected period
        $latestUploadId = Stock::whereHas('uploadHistory', fn($q) => $q->where('period_id', $period->id))->max('upload_history_id');
        
        // Fallback: If no stock for selected period, find the absolute latest stock in the system
        if (!$latestUploadId) {
            $latestUploadId = Stock::max('upload_history_id');
        }

        if ($latestUploadId) {
            $stockQuery->where('upload_history_id', $latestUploadId);
            if ($branchCode) $stockQuery->where('branch', $branchCode);
        } else {
            return [];
        }
        $currentStocks = $stockQuery->get()->keyBy('product_id');



        $results = [];
        // Important: Ensure all ML Input items have values for all periods or fill with 0
        foreach ($mlInput as $pId => $data) {
            // 🎓 S1 UPGRADE: Volatility Calculation (Coefficient of Variation)
            $qtys = array_column($data, 'qty');
            $avg = count($qtys) > 0 ? array_sum($qtys) / count($qtys) : 0;
            $variance = 0;
            foreach ($qtys as $q) $variance += pow($q - $avg, 2);
            $stdDev = count($qtys) > 1 ? sqrt($variance / (count($qtys) - 1)) : 0;
            $volatility = ($avg > 0) ? ($stdDev / $avg) : 0;

            for ($i=0; $i < count($chronoPeriods); $i++) {
                if (!isset($mlInput[$pId][$i])) {
                    $periodId = $chronoPeriods[$i];
                    $hStock = $histStockMap[$periodId][$pId] ?? 1;
                    $mlInput[$pId][$i] = [
                        'qty' => 0, 
                        'promo' => 0, 
                        'stockout' => ($hStock <= 0 ? 1 : 0),
                        'cat_momentum' => 1 // Default neutral
                    ];
                }
                // Inject Volatility into every time step to help the model learn consistency
                $mlInput[$pId][$i]['volatility'] = (float)$volatility;
            }
        }

        $periodDates = [];
        foreach ($chronoPeriods as $idx => $pId) {
            $pModel = Period::find($pId);
            $periodDates[$idx] = $pModel ? "{$pModel->year}-" . str_pad($pModel->month, 2, '0', STR_PAD_LEFT) : null;
        }

        $aiPredictions = $this->getBatchMLForecast($mlInput, $periodDates);

        foreach ($salesVelocity as $productId => $salesData) {
            $stock = isset($currentStocks[$productId]) ? $currentStocks[$productId]->current_stock : 0;
            $avgDaily = $salesData->total_qty_sold / (count($precedingIds) * 30);
            $avgMonthly = $salesData->total_qty_sold / count($precedingIds);
            
            $aiData = $aiPredictions[$productId] ?? null;
            $volatility = isset($mlInput[$productId][0]['volatility']) ? $mlInput[$productId][0]['volatility'] : 0;

            $results[] = (object) [
                'id' => $productId,
                'product_name' => $salesData->name,
                'category' => $salesData->category,
                'principle_name' => $salesData->principle_name,
                'current_stock' => $stock,
                'avg_daily' => (float) $avgDaily,
                'avg_monthly' => (float) $avgMonthly,
                'volatility' => (float) $volatility,
                'm1_name' => $p1 ? \Carbon\Carbon::create($p1->year, $p1->month, 1)->translatedFormat('M y') : '-',
                'm2_name' => $p2 ? \Carbon\Carbon::create($p2->year, $p2->month, 1)->translatedFormat('M y') : '-',
                'm3_name' => $p3 ? \Carbon\Carbon::create($p3->year, $p3->month, 1)->translatedFormat('M y') : '-',
                'qty_m1' => (float) ($salesData->qty_m1 ?? 0),
                'qty_m2' => (float) ($salesData->qty_m2 ?? 0),
                'qty_m3' => (float) ($salesData->qty_m3 ?? 0),
                'ai_prediction' => $aiData ? (float) $aiData['prediction'] : $avgMonthly,
                'ai_trend' => $aiData ? $aiData['trend'] : 'stable',
                'ai_confidence' => $aiData ? (float) $aiData['confidence'] : 0,
                'ai_low' => $aiData['prediction_interval']['low'] ?? null,
                'ai_high' => $aiData['prediction_interval']['high'] ?? null,
                'ai_model' => $aiData['model'] ?? 'fallback_avg',
                'ai_wape' => $aiData['validation']['wape'] ?? null,
                'is_ml' => $aiData ? (bool) $aiData['is_ml'] : false
            ];

            if ($aiData) {
                $this->recordForecastRun([
                    'context' => 'purchase_order_product',
                    'period_id' => $period->id ?? null,
                    'branch' => $branch,
                    'scope_key' => $principle,
                    'entity_type' => 'product',
                    'entity_id' => $productId,
                    'entity_name' => $salesData->name,
                    'model' => $aiData['model'] ?? null,
                    'is_ml' => $aiData['is_ml'] ?? false,
                    'prediction' => $aiData['prediction'] ?? null,
                    'prediction_low' => $aiData['prediction_interval']['low'] ?? null,
                    'prediction_high' => $aiData['prediction_interval']['high'] ?? null,
                    'confidence' => $aiData['confidence'] ?? null,
                    'wape' => $aiData['validation']['wape'] ?? null,
                    'mape' => $aiData['validation']['mape'] ?? null,
                    'mae' => $aiData['validation']['mae'] ?? null,
                    'rmse' => $aiData['validation']['rmse'] ?? null,
                ]);
            }
        }
        return $results;
    }

    // --- PILLAR 7: DEAD STOCK ---

    public function deadStock(Request $request)
    {
        $branch = $this->getBranchFilter($request);
        $activePeriod = $this->getSelectedPeriod($request);
        $principle = $request->query('principle', 'all');

        $deadStock = $this->cachedResult('dead_stock_v2', $activePeriod, function() use ($branch, $activePeriod, $principle) {
            return $this->getDeadStockData($branch, $activePeriod, $principle);
        });

        $allPrinciples = Stock::whereHas('uploadHistory', fn($q) => $q->where('period_id', $activePeriod->id))
            ->distinct()->pluck('principle_name')->filter()->sort()->values();

        return view('insights.dead-stock', [
            'data' => $deadStock,
            'selected_branch' => $branch,
            'selected_principle' => $principle,
            'activePeriod' => $activePeriod,
            'allPeriods' => \App\Models\Period::ordered()->get(),
            'allPrinciples' => $allPrinciples
        ]);
    }

    private function getDeadStockData($branch, $period = null, $principle = 'all')
    {
        $period = $period ?? $this->getSelectedPeriod(request());
        $precedingIds = $this->get3MonthRange($period);

        $soldProductIds = Sale::join('transactions', 'sales.transaction_id', '=', 'transactions.id')
            ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
            ->whereIn('upload_histories.period_id', $precedingIds)
            ->distinct()->pluck('sales.product_id')->toArray();

        $query = Stock::join('products', 'stocks.product_id', '=', 'products.id')

            ->whereNotIn('stocks.product_id', $soldProductIds)
            ->where('stocks.on_hand_base', '>', 0)
            ->select(
                'products.name',
                'stocks.principle_name',
                DB::raw('SUM(stocks.on_hand_base) as stock'),
                DB::raw('SUM(stocks.stock_value_on_hand) as value')
            )
            ->when($principle !== 'all', fn($q) => $q->where('stocks.principle_name', $principle))
            ->groupBy('products.id', 'products.name', 'stocks.principle_name')
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
        $periodIds = $this->get3MonthRange($activePeriod); 
        $isAiMode = $request->query('mode') === 'ai';
        
        // Fetch all possible principles for the dropdown within the 3-month window
        $dropdownQuery = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
            ->whereIn('upload_histories.period_id', $periodIds);
        
        $this->applyBranchFilter($dropdownQuery, $branch);
        
        $principles = $dropdownQuery->select(DB::raw('JSON_UNQUOTE(JSON_EXTRACT(sales.raw_data, "$.principle_name")) as name'))
            ->distinct()->pluck('name')->filter(fn($n) => !empty($n) && $n !== 'Principle Name')->sort()->values();

        $selectedPrinciple = $request->query('principle', $principles[0] ?? null);

        if (!$selectedPrinciple) {
            return view('insights.principal-report', [
                'data' => null,
                'summary' => null,
                'principles' => $principles,
                'selected_branch' => $branch,
                'selected_principle' => null,
                'activePeriod' => $activePeriod,
                'allPeriods' => \App\Models\Period::ordered()->get(),
                'isAiMode' => $isAiMode
            ]);
        }

        $reportData = $this->cachedResult('principal_report_v9', $activePeriod, function() use ($selectedPrinciple, $branch, $periodIds, $activePeriod, $principles) {
            // Find the Principle ID for the selected name
            $principleId = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
                ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
                ->whereIn('upload_histories.period_id', $periodIds)
                ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(sales.raw_data, "$.principle_name")) = ?', [$selectedPrinciple])
                ->select(DB::raw('JSON_UNQUOTE(JSON_EXTRACT(sales.raw_data, "$.principle_id")) as pid'))
                ->value('pid');

            // 1. Summary Metrics
            $queryBase = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
                ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
                ->whereIn('upload_histories.period_id', $periodIds);
            
            if ($principleId) {
                $queryBase->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(sales.raw_data, "$.principle_id")) = ?', [$principleId]);
            } else {
                $queryBase->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(sales.raw_data, "$.principle_name")) = ?', [$selectedPrinciple]);
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
                $monthlyData->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(sales.raw_data, "$.principle_id")) = ?', [$principleId]);
            } else {
                $monthlyData->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(sales.raw_data, "$.principle_name")) = ?', [$selectedPrinciple]);
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
                        'month' => "{$pModel->year}-" . str_pad($pModel->month, 2, '0', STR_PAD_LEFT),
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
                    $prevValQuery->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(sales.raw_data, "$.principle_id")) = ?', [$principleId]);
                } else {
                    $prevValQuery->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(sales.raw_data, "$.principle_name")) = ?', [$selectedPrinciple]);
                }

                $this->applyBranchFilter($prevValQuery, $branch);
                $prevVal = $prevValQuery->sum('sales.total');
            }

                        $growthVal = ($prevVal > 0) ? (($currVal - $prevVal) / $prevVal) * 100 : (($currVal > 0) ? 100 : 0);

            // 8. Sleeper Outlets
            $rfmNow = \Carbon\Carbon::now(); // Use current date for recency calculation
            $lastOrdersQuery = Transaction::join('sales', 'transactions.id', '=', 'sales.transaction_id')
                ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
                ->whereIn('upload_histories.period_id', $periodIds) // Filter by period IDs
                ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(sales.raw_data, "$.principle_name")) = ?', [$selectedPrinciple]);
            
            $this->applyBranchFilter($lastOrdersQuery, $branch);
            
            $lastOrders = $lastOrdersQuery->select('transactions.outlet_id', DB::raw('MAX(transactions.transaction_date) as last_date'), DB::raw('SUM(sales.total) as total_contribution'))
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

            // 10. ML Forecast
            $forecast = $this->getMLForecast($growthSeries);
            if ($forecast) {
                $this->recordForecastRun([
                    'context' => 'principal_report',
                    'period_id' => $activePeriod->id ?? null,
                    'branch' => $branch,
                    'scope_key' => $selectedPrinciple,
                    'entity_type' => 'principle',
                    'entity_id' => $principleId ?: $selectedPrinciple,
                    'entity_name' => $selectedPrinciple,
                    'model' => $forecast['model'] ?? null,
                    'is_ml' => $forecast['is_ml'] ?? false,
                    'prediction' => $forecast['prediction'] ?? null,
                    'prediction_low' => $forecast['prediction_interval']['low'] ?? null,
                    'prediction_high' => $forecast['prediction_interval']['high'] ?? null,
                    'confidence' => $forecast['confidence'] ?? null,
                    'wape' => $forecast['validation']['wape'] ?? null,
                    'mape' => $forecast['validation']['mape'] ?? null,
                    'mae' => $forecast['validation']['mae'] ?? null,
                    'rmse' => $forecast['validation']['rmse'] ?? null,
                ]);
            }

            return compact('summary', 'trend', 'growthSeries', 'topProducts', 'topOutlets', 'topSalesmen', 'cityAnalysis', 'growthVal', 'currVal', 'sleepers', 'returns', 'forecast');
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
            'forecast' => $reportData['forecast'],
            'activePeriod' => $activePeriod,
            'allPeriods' => \App\Models\Period::ordered()->get(),
            'isAiMode' => $isAiMode
        ]);
    }

    // --- PILLAR 9: SALESMAN INTELLIGENCE ---

    public function salesmanIntelligence(Request $request)
    {
        $branch = $request->get('branch', 'all');
        $activePeriod = $this->getSelectedPeriod($request);

        $allPeriods = Period::orderBy('id', 'desc')->get();

        $data = $this->cachedResult('salesman_intel_v1', $activePeriod, function() use ($branch, $activePeriod) {
            return $this->getSalesmanIntelligenceData($branch, $activePeriod);
        });

        return view('insights.salesman-intelligence', compact('data', 'activePeriod', 'branch', 'allPeriods'));
    }

    private function getSalesmanIntelligenceData($branch, $period)
    {
        $precedingIds = $this->getMlLookbackRange($period, 24);
        if (count($precedingIds) < 3) return [];

        $chronoPeriods = array_reverse($precedingIds);
        $periodMap = array_flip($chronoPeriods);

        $query = Sale::join('transactions', 'sales.transaction_id', '=', 'transactions.id')
            ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
            ->whereIn('upload_histories.period_id', $precedingIds)
            ->where('sales.total', '>', 0) // Sales only
            ->select(
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.sales_id')) as sales_id"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.sales_name')) as sales_name"),
                'upload_histories.period_id',
                DB::raw('SUM(sales.total) as total_revenue'),
                DB::raw('COUNT(DISTINCT transactions.id) as total_tx'),
                DB::raw('COUNT(DISTINCT transactions.outlet_id) as total_outlets')
            )
            ->groupBy('sales_id', 'sales_name', 'upload_histories.period_id');

        $this->applyBranchFilter($query, $branch);
        $raw = $query->get();

        // 🎓 S1 UPGRADE: Area Momentum Analysis (Branch-wide daily trend)
        $branchTrends = [];
        foreach ($raw as $row) {
            $branchTrends[$row->period_id] = ($branchTrends[$row->period_id] ?? 0) + $row->total_revenue;
        }

        $salesmen = [];
        $mlInput = [];
        
        foreach ($raw as $row) {
            if (!$row->sales_id) continue;
            
            if (!isset($salesmen[$row->sales_id])) {
                $salesmen[$row->sales_id] = (object) [
                    'id' => $row->sales_id,
                    'name' => $row->sales_name,
                    'total_revenue' => 0,
                    'total_tx' => 0,
                    'total_outlets' => 0,
                    'history' => array_fill(0, count($chronoPeriods), 0)
                ];
            }
            
            $s = &$salesmen[$row->sales_id];
            $s->total_revenue += $row->total_revenue;
            $s->total_tx += $row->total_tx;
            $s->total_outlets = max($s->total_outlets, $row->total_outlets);

            $idx = $periodMap[$row->period_id];
            $s->history[$idx] = (float) $row->total_revenue;

            // Activity Factor (Transaction intensity)
            $activity = (float) $row->total_tx;

            // Area Momentum: How this branch performed this month vs it's average
            $branchAvg = array_sum($branchTrends) / count($precedingIds);
            $areaMomentum = ($branchAvg > 0) ? ($branchTrends[$row->period_id] / $branchAvg) : 1;

            if (!isset($mlInput[$row->sales_id][$idx])) {
                $mlInput[$row->sales_id][$idx] = [
                    'qty' => (float) $row->total_revenue,
                    'promo' => (float) $row->total_tx, // Use transaction count as effort/activity variable
                    'stockout' => 0,
                    'cat_momentum' => $areaMomentum
                ];
            }
        }

        // Fill missing ML indices and calculate Volatility
        foreach ($mlInput as $sId => $data) {
            $revs = array_column($data, 'qty');
            $avg = count($revs) > 0 ? array_sum($revs) / count($revs) : 0;
            $variance = 0;
            foreach ($revs as $r) $variance += pow($r - $avg, 2);
            $stdDev = count($revs) > 1 ? sqrt($variance / (count($revs) - 1)) : 0;
            $volatility = ($avg > 0) ? ($stdDev / $avg) : 0;

            for ($i=0; $i < count($chronoPeriods); $i++) {
                if (!isset($mlInput[$sId][$i])) {
                    $mlInput[$sId][$i] = [
                        'qty' => 0, 
                        'promo' => 0, 
                        'stockout' => 0, 
                        'cat_momentum' => 1
                    ];
                }
                $mlInput[$sId][$i]['volatility'] = (float)$volatility;
            }
        }

        $predictions = $this->getBatchMLForecast($mlInput);
        
        $results = [];
        foreach ($salesmen as $id => $s) {
            $pred = $predictions[$id] ?? null;
            $avgRevenue = $s->total_revenue / count($precedingIds);
            
            $results[] = (object) [
                'sales_id' => $s->id,
                'sales_name' => $s->name,
                'avg_revenue' => $avgRevenue,
                'total_outlets' => $s->total_outlets,
                'total_tx' => $s->total_tx,
                'ai_prediction' => $pred ? (float) $pred['prediction'] : $avgRevenue,
                'ai_trend' => $pred ? $pred['trend'] : 'stable',
                'ai_confidence' => $pred ? (float) $pred['confidence'] : 0,
                'ai_low' => $pred['prediction_interval']['low'] ?? null,
                'ai_high' => $pred['prediction_interval']['high'] ?? null,
                'ai_model' => $pred['model'] ?? 'fallback_avg',
                'ai_wape' => $pred['validation']['wape'] ?? null,
                'recent_history' => array_slice($s->history, -3) // Last 3 months for UI
            ];

            if ($pred) {
                $this->recordForecastRun([
                    'context' => 'salesman_intelligence',
                    'period_id' => $period->id ?? null,
                    'branch' => $branch,
                    'entity_type' => 'salesman',
                    'entity_id' => $s->id,
                    'entity_name' => $s->name,
                    'model' => $pred['model'] ?? null,
                    'is_ml' => $pred['is_ml'] ?? false,
                    'prediction' => $pred['prediction'] ?? null,
                    'prediction_low' => $pred['prediction_interval']['low'] ?? null,
                    'prediction_high' => $pred['prediction_interval']['high'] ?? null,
                    'confidence' => $pred['confidence'] ?? null,
                    'wape' => $pred['validation']['wape'] ?? null,
                    'mape' => $pred['validation']['mape'] ?? null,
                    'mae' => $pred['validation']['mae'] ?? null,
                    'rmse' => $pred['validation']['rmse'] ?? null,
                ]);
            }
        }

        return collect($results)->sortByDesc('avg_revenue')->values();
    }

    // --- PILLAR 8: GUIDE ---

    public function guide()
    {
        return view('insights.guide');
    }

    protected function getMLForecast($historicalData)
    {
        if (!$historicalData || count($historicalData) < 2) return null;

        $tempPath = storage_path('app/temp_forecast_' . uniqid() . '.csv');
        $file = fopen($tempPath, 'w');
        fputcsv($file, ['month', 'total', 'promo']);
        foreach ($historicalData as $row) {
            fputcsv($file, [$row['month'], $row['value'], $row['promo'] ?? 0]);
        }
        fclose($file);

        $pythonPath = base_path('python_ml/python.exe');
        $scriptPath = base_path('forecast_sales.py');
        
        // Use escapeshellarg for security and to handle spaces in paths
        $cmd = "\"$pythonPath\" \"$scriptPath\" \"$tempPath\"";
        $output = shell_exec($cmd);
        
        if (file_exists($tempPath)) @unlink($tempPath);

        if (!$output) return null;

        $result = json_decode($output, true);
        return ($result && ($result['status'] ?? '') === 'success') ? $result : null;
    }

    protected function getBatchMLForecast($historicalData, $periodDates = [])
    {
        if (!$historicalData || count($historicalData) === 0) return null;

        $tempPath = storage_path('app/temp_batch_forecast_' . uniqid() . '.csv');
        $file = fopen($tempPath, 'w');
        fputcsv($file, ['product_id', 'month_index', 'qty', 'date', 'promo', 'stockout', 'cat_momentum', 'volatility']);
        
        foreach ($historicalData as $pId => $months) {
            foreach ($months as $idx => $data) {
                $qty = is_array($data) ? ($data['qty'] ?? 0) : $data;
                $promo = is_array($data) ? ($data['promo'] ?? 0) : 0;
                $stockout = is_array($data) ? ($data['stockout'] ?? 0) : 0;
                $cat_mo = is_array($data) ? ($data['cat_momentum'] ?? 1) : 1;
                $vol = is_array($data) ? ($data['volatility'] ?? 0) : 0;
                $dateStr = $periodDates[$idx] ?? '';
                fputcsv($file, [$pId, $idx, $qty, $dateStr, $promo, $stockout, $cat_mo, $vol]);
            }
        }
        fclose($file);

        $pythonPath = base_path('python_ml/python.exe');
        $scriptPath = base_path('forecast_inventory_batch.py');
        $cmd = "\"$pythonPath\" \"$scriptPath\" \"$tempPath\"";
        $output = shell_exec($cmd);
        
        if (file_exists($tempPath)) @unlink($tempPath);

        if (!$output) return null;

        $result = json_decode($output, true);
        return ($result && ($result['status'] ?? '') === 'success') ? $result['data'] : null;
    }
}
