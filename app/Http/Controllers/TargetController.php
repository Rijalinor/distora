<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\Period;
use App\Models\Target;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TargetController extends Controller
{
    private function getBranchFilter(Request $request): string
    {
        return (string) ($request->query('branch', $request->input('branch', 'all')));
    }

    private function getSelectedPeriod(Request $request): Period
    {
        $periodId = $request->query('period_id', $request->input('period_id'));
        if ($periodId) {
            $p = Period::find($periodId);
            if ($p) {
                return $p;
            }
        }
        return Period::resolveFromRequest($request);
    }

    private function applyBranchFilter($query, string $branch, string $transactionAlias = 'transactions'): void
    {
        if ($branch !== 'all') {
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT({$transactionAlias}.meta, '$.dist_id')) = ?", [$branch]);
        }
    }

    private function buildSalesTeamAllocation(Period $period, string $principalName, float $teamTarget, string $branch = 'all'): array
    {
        $precedingIds = $period->getPrecedingIds(3);
        if (count($precedingIds) === 0) {
            return [
                'rows' => [],
                'summary' => [
                    'principal_name' => strtoupper($principalName),
                    'branch' => $branch,
                    'team_target' => $teamTarget,
                    'total_historical' => 0,
                    'sales_count' => 0,
                ],
            ];
        }

        $normalizedPrincipal = strtoupper($principalName);
        $contributorsQuery = Transaction::query()
            ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->whereIn('upload_histories.period_id', $precedingIds)
            ->where('sales.total', '>', 0)
            ->whereNotNull('transactions.meta')
            ->whereRaw("UPPER(JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name'))) = ?", [$normalizedPrincipal])
            ->select(
                DB::raw("UPPER(JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.sales_name'))) as salesman_name"),
                DB::raw('SUM(sales.total) as historical_amount')
            );

        $this->applyBranchFilter($contributorsQuery, $branch);

        $contributors = $contributorsQuery
            ->groupBy('salesman_name')
            ->orderByDesc('historical_amount')
            ->get()
            ->filter(fn($row) => filled($row->salesman_name))
            ->values();

        if ($contributors->isEmpty()) {
            return [
                'rows' => [],
                'summary' => [
                    'principal_name' => $normalizedPrincipal,
                    'branch' => $branch,
                    'team_target' => $teamTarget,
                    'total_historical' => 0,
                    'sales_count' => 0,
                ],
            ];
        }

        $totalHistorical = (float) $contributors->sum('historical_amount');
        $salesCount = $contributors->count();

        $rows = [];
        $allocatedTotal = 0.0;
        foreach ($contributors as $idx => $row) {
            $historical = (float) $row->historical_amount;
            $weight = $totalHistorical > 0 ? ($historical / $totalHistorical) : (1 / $salesCount);

            $allocated = $idx === ($salesCount - 1)
                ? round(max(0, $teamTarget - $allocatedTotal), 2)
                : round($teamTarget * $weight, 2);
            $allocatedTotal += $allocated;

            $rows[] = [
                'salesman_name' => (string) $row->salesman_name,
                'historical_amount' => $historical,
                'contribution_pct' => round($weight * 100, 2),
                'allocated_target' => $allocated,
            ];
        }

        return [
            'rows' => $rows,
            'summary' => [
                'principal_name' => $normalizedPrincipal,
                'branch' => $branch,
                'team_target' => round($teamTarget, 2),
                'total_historical' => round($totalHistorical, 2),
                'sales_count' => $salesCount,
            ],
        ];
    }

    private function getAvailableNames(string $type, ?int $periodId = null, string $branch = 'all')
    {
        if ($type === 'outlet') {
            $outletNames = Outlet::query()
                ->select(DB::raw('UPPER(name) as name'))
                ->distinct()
                ->pluck('name')
                ->filter(fn($name) => filled($name))
                ->values();

            $targetNames = Target::query()
                ->where('type', 'outlet')
                ->when($periodId, fn($q) => $q->where('period_id', $periodId))
                ->pluck('name')
                ->map(fn($name) => strtoupper((string) $name))
                ->filter(fn($name) => filled($name))
                ->values();

            return $outletNames
                ->merge($targetNames)
                ->unique()
                ->sort()
                ->values();
        }

        $metaPath = $type === 'salesman' ? '$.sales_name' : '$.principle_name';

        $historyNames = Transaction::query()
            ->whereNotNull('transactions.meta')
            ->when($branch !== 'all', function ($q) use ($branch) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.dist_id')) = ?", [$branch]);
            })
            ->select(DB::raw("UPPER(JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '{$metaPath}'))) as name"))
            ->distinct()
            ->pluck('name')
            ->filter(fn($name) => filled($name) && strtoupper($name) !== 'NULL')
            ->values();

        $targetNames = Target::query()
            ->where('type', $type)
            ->when($periodId, fn($q) => $q->where('period_id', $periodId))
            ->pluck('name')
            ->map(fn($name) => strtoupper((string) $name))
            ->filter(fn($name) => filled($name))
            ->values();

        return $historyNames
            ->merge($targetNames)
            ->unique()
            ->sort()
            ->values();
    }

    private function historicalAverageForTarget(Period $period, string $type, string $name, string $principalName = '', string $branch = 'all'): array
    {
        $precedingIds = $period->getPrecedingIds(3);
        if (count($precedingIds) === 0) {
            return [
                'average' => 0.0,
                'total' => 0.0,
                'months_used' => 0,
                'months_with_sales' => 0,
            ];
        }

        $normalizedName = strtoupper($name);

        if ($type === 'outlet') {
            $normalizedPrincipal = strtoupper($principalName);
            $query = Transaction::query()
                ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
                ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
                ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
                ->whereIn('upload_histories.period_id', $precedingIds)
                ->where('sales.total', '>', 0)
                ->whereRaw('UPPER(outlets.name) = ?', [$normalizedName])
                ->whereNotNull('transactions.meta')
                ->whereRaw("UPPER(JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name'))) = ?", [$normalizedPrincipal])
                ->select(
                    'upload_histories.period_id',
                    DB::raw('SUM(sales.total) as total_amount')
                );
            $this->applyBranchFilter($query, $branch);
            $monthlyTotals = $query->groupBy('upload_histories.period_id')->pluck('total_amount', 'upload_histories.period_id');
        } else {
            $metaPath = $type === 'salesman' ? '$.sales_name' : '$.principle_name';
            $query = Transaction::query()
                ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
                ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
                ->whereIn('upload_histories.period_id', $precedingIds)
                ->where('sales.total', '>', 0)
                ->whereNotNull('transactions.meta')
                ->whereRaw("UPPER(JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, ?))) = ?", [$metaPath, $normalizedName])
                ->when($type === 'salesman' && filled($principalName), function ($q) use ($principalName) {
                    $q->whereRaw("UPPER(JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name'))) = ?", [strtoupper($principalName)]);
                })
                ->select(
                    'upload_histories.period_id',
                    DB::raw('SUM(sales.total) as total_amount')
                );
            $this->applyBranchFilter($query, $branch);
            $monthlyTotals = $query->groupBy('upload_histories.period_id')->pluck('total_amount', 'upload_histories.period_id');
        }

        $total = (float) $monthlyTotals->sum();
        $monthsUsed = count($precedingIds);
        $average = $monthsUsed > 0 ? ($total / $monthsUsed) : 0.0;

        return [
            'average' => $average,
            'total' => $total,
            'months_used' => $monthsUsed,
            'months_with_sales' => $monthlyTotals->count(),
        ];
    }

    public function index(Request $request)
    {
        $period = $this->getSelectedPeriod($request);
        $selectedBranch = $this->getBranchFilter($request);
        $uploadIds = $period->uploadHistories()->pluck('id');

        $actualSalesmanQuery = Transaction::query()
            ->whereIn('upload_history_id', $uploadIds)
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->where('sales.total', '>', 0)
            ->whereNotNull('transactions.meta')
            ->select(
                DB::raw("UPPER(JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.sales_name'))) as name"),
                DB::raw('SUM(sales.total) as actual_amount')
            );
        $this->applyBranchFilter($actualSalesmanQuery, $selectedBranch);
        $actualSalesman = $actualSalesmanQuery->groupBy('name')->pluck('actual_amount', 'name');

        $actualSalesmanPrincipalRowsQuery = Transaction::query()
            ->whereIn('upload_history_id', $uploadIds)
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->where('sales.total', '>', 0)
            ->whereNotNull('transactions.meta')
            ->select(
                DB::raw("UPPER(JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.sales_name'))) as sales_name"),
                DB::raw("UPPER(JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name'))) as principle_name"),
                DB::raw('SUM(sales.total) as actual_amount')
            );
        $this->applyBranchFilter($actualSalesmanPrincipalRowsQuery, $selectedBranch);
        $actualSalesmanPrincipalRows = $actualSalesmanPrincipalRowsQuery->groupBy('sales_name', 'principle_name')->get();

        $actualSalesmanPrincipal = $actualSalesmanPrincipalRows->mapWithKeys(function ($row) {
            $key = strtoupper((string) $row->sales_name) . '||' . strtoupper((string) $row->principle_name);
            return [$key => (float) $row->actual_amount];
        });

        $actualPrincipleQuery = Transaction::query()
            ->whereIn('upload_history_id', $uploadIds)
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->where('sales.total', '>', 0)
            ->whereNotNull('transactions.meta')
            ->select(
                DB::raw("UPPER(JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name'))) as name"),
                DB::raw('SUM(sales.total) as actual_amount')
            );
        $this->applyBranchFilter($actualPrincipleQuery, $selectedBranch);
        $actualPrinciple = $actualPrincipleQuery->groupBy('name')->pluck('actual_amount', 'name');

        $actualOutletQuery = Transaction::query()
            ->whereIn('upload_history_id', $uploadIds)
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->where('sales.total', '>', 0)
            ->select(
                DB::raw('UPPER(outlets.name) as name'),
                DB::raw('SUM(sales.total) as actual_amount')
            );
        $this->applyBranchFilter($actualOutletQuery, $selectedBranch);
        $actualOutlet = $actualOutletQuery->groupBy('name')->pluck('actual_amount', 'name');

        $actualOutletPrincipalRowsQuery = Transaction::query()
            ->whereIn('upload_history_id', $uploadIds)
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->where('sales.total', '>', 0)
            ->whereNotNull('transactions.meta')
            ->select(
                DB::raw('UPPER(outlets.name) as outlet_name'),
                DB::raw("UPPER(JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name'))) as principle_name"),
                DB::raw('SUM(sales.total) as actual_amount')
            );
        $this->applyBranchFilter($actualOutletPrincipalRowsQuery, $selectedBranch);
        $actualOutletPrincipalRows = $actualOutletPrincipalRowsQuery->groupBy('outlet_name', 'principle_name')->get();

        $actualOutletPrincipal = $actualOutletPrincipalRows->mapWithKeys(function ($row) {
            $key = strtoupper((string) $row->outlet_name) . '||' . strtoupper((string) $row->principle_name);
            return [$key => (float) $row->actual_amount];
        });

        $user = auth()->user();
        $targetQuery = Target::where('period_id', $period->id);

        if ($user && $user->role === 'salesman') {
            $targetQuery->where('type', 'salesman')
                ->where('name', $user->linked_salesman_name);
        }

        $targets = $targetQuery->get();

        $targetData = $targets->map(function ($target) use ($actualSalesman, $actualSalesmanPrincipal, $actualPrinciple, $actualOutlet, $actualOutletPrincipal) {
            $normalizedName = strtoupper((string) $target->name);
            $normalizedPrincipal = strtoupper((string) ($target->principal_name ?? ''));

            $actual = $target->type === 'salesman'
                ? (
                    $normalizedPrincipal !== ''
                        ? ($actualSalesmanPrincipal[$normalizedName . '||' . $normalizedPrincipal] ?? 0)
                        : ($actualSalesman[$normalizedName] ?? 0)
                )
                : ($target->type === 'principle'
                    ? ($actualPrinciple[$normalizedName] ?? 0)
                    : (
                        $normalizedPrincipal !== ''
                            ? ($actualOutletPrincipal[$normalizedName . '||' . $normalizedPrincipal] ?? 0)
                            : ($actualOutlet[$normalizedName] ?? 0)
                    ));

            $progress = $target->target_amount > 0
                ? round(($actual / $target->target_amount) * 100, 1)
                : 0;

            return (object) [
                'id' => $target->id,
                'type' => $target->type,
                'name' => $target->name,
                'principal_name' => $target->principal_name,
                'target' => $target->target_amount,
                'actual' => $actual,
                'progress' => min($progress, 150),
                'raw_progress' => $progress,
            ];
        });

        $salesmanTargets = $targetData->where('type', 'salesman')->sortByDesc('raw_progress');
        $principleTargets = $targetData->where('type', 'principle')->sortByDesc('raw_progress');
        $outletTargets = $targetData->where('type', 'outlet')->sortByDesc('raw_progress');

        $salesmanNames = $this->getAvailableNames('salesman', $period->id, $selectedBranch);
        $principleNames = $this->getAvailableNames('principle', $period->id, $selectedBranch);
        $outletNames = $this->getAvailableNames('outlet', $period->id, $selectedBranch);
        $allPeriods = Period::ordered()->get();

        return view('targets.index', compact(
            'period',
            'allPeriods',
            'selectedBranch',
            'salesmanTargets',
            'principleTargets',
            'outletTargets',
            'salesmanNames',
            'principleNames',
            'outletNames'
        ));
    }

    public function suggest(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Hanya Admin yang dapat menghitung target.');
        }

        $validated = $request->validate([
            'type' => 'required|in:salesman,principle,outlet',
            'name' => 'required|string|max:255',
            'principal_name' => 'nullable|string|max:255',
            'growth_pct' => 'required|numeric|min:-100|max:500',
        ]);

        $period = $this->getSelectedPeriod($request);
        $branch = $this->getBranchFilter($request);

        $principalName = in_array($validated['type'], ['outlet', 'salesman'], true)
            ? strtoupper(trim((string) ($validated['principal_name'] ?? '')))
            : '';

        if ($validated['type'] === 'outlet' && $principalName === '') {
            return response()->json(['message' => 'Principal wajib dipilih untuk target toko.'], 422);
        }

        $base = $this->historicalAverageForTarget($period, $validated['type'], $validated['name'], $principalName, $branch);
        $growthPct = (float) $validated['growth_pct'];
        $multiplier = 1 + ($growthPct / 100);
        $suggestedTarget = max(0, $base['average'] * $multiplier);

        return response()->json([
            'period' => $period->name,
            'type' => $validated['type'],
            'name' => strtoupper($validated['name']),
            'principal_name' => $principalName,
            'branch' => $branch,
            'growth_pct' => $growthPct,
            'average_last_3_months' => round($base['average'], 2),
            'suggested_target' => round($suggestedTarget, 2),
            'months_used' => $base['months_used'],
            'months_with_sales' => $base['months_with_sales'],
        ]);
    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Hanya Admin yang dapat mengubah target.');
        }

        $request->validate([
            'type' => 'required|in:salesman,principle,outlet',
            'name' => 'required|string|max:255',
            'principal_name' => 'nullable|string|max:255',
            'target_amount' => 'required|numeric|min:1',
        ]);

        $period = $this->getSelectedPeriod($request);
        $principalName = in_array($request->type, ['outlet', 'salesman'], true)
            ? strtoupper(trim((string) $request->principal_name))
            : '';

        if ($request->type === 'outlet' && $principalName === '') {
            return back()->withErrors(['principal_name' => 'Principal wajib dipilih untuk target toko.'])->withInput();
        }

        Target::updateOrCreate(
            [
                'period_id' => $period->id,
                'type' => $request->type,
                'name' => strtoupper($request->name),
                'principal_name' => $principalName,
            ],
            [
                'target_amount' => $request->target_amount,
            ]
        );

        return back()->with('success', 'Target berhasil disimpan.');
    }

    public function teamAllocationPreview(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Hanya Admin yang dapat menghitung alokasi target tim.');
        }

        $validated = $request->validate([
            'principal_name' => 'required|string|max:255',
            'team_target' => 'required|numeric|min:1',
        ]);

        $period = $this->getSelectedPeriod($request);
        $branch = $this->getBranchFilter($request);
        $allocation = $this->buildSalesTeamAllocation($period, $validated['principal_name'], (float) $validated['team_target'], $branch);

        return response()->json($allocation);
    }

    public function teamAllocationApply(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Hanya Admin yang dapat menerapkan alokasi target tim.');
        }

        $validated = $request->validate([
            'principal_name' => 'required|string|max:255',
            'team_target' => 'required|numeric|min:1',
        ]);

        $period = $this->getSelectedPeriod($request);
        $branch = $this->getBranchFilter($request);
        $allocation = $this->buildSalesTeamAllocation($period, $validated['principal_name'], (float) $validated['team_target'], $branch);

        if (empty($allocation['rows'])) {
            return back()->withErrors(['team_allocation' => 'Tidak ada data sales historis untuk principal tersebut pada 3 bulan terakhir.']);
        }

        $principal = strtoupper(trim((string) $validated['principal_name']));
        foreach ($allocation['rows'] as $row) {
            Target::updateOrCreate(
                [
                    'period_id' => $period->id,
                    'type' => 'salesman',
                    'name' => strtoupper((string) $row['salesman_name']),
                    'principal_name' => $principal,
                ],
                [
                    'target_amount' => $row['allocated_target'],
                ]
            );
        }

        return back()->with('success', 'Alokasi target tim berhasil diterapkan ke KPI salesman.');
    }

    public function destroy(Target $target)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Hanya Admin yang dapat menghapus target.');
        }

        $target->delete();
        return back()->with('success', 'Target berhasil dihapus.');
    }
}
