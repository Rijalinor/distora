<?php

namespace App\Services;

use App\Models\MlForecastRun;
use App\Models\Sale;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class MlForecastEvaluator
{
    public function evaluatePending(?int $periodId = null, ?string $context = null, ?string $branch = null, int $limit = 500): int
    {
        $query = MlForecastRun::query()
            ->whereNotNull('prediction')
            ->whereNotNull('period_id')
            ->where(function ($q) {
                $q->whereNull('evaluated_at')
                  ->orWhereNull('actual_value');
            })
            ->when($periodId, fn ($q) => $q->where('period_id', $periodId))
            ->when($context && $context !== 'all', fn ($q) => $q->where('context', $context))
            ->when($branch && $branch !== 'all', fn ($q) => $q->where('branch', $branch))
            ->orderByDesc('forecasted_at')
            ->limit($limit);

        $count = 0;
        foreach ($query->get() as $run) {
            if ($this->evaluateRun($run)) {
                $count++;
            }
        }

        return $count;
    }

    public function evaluateRun(MlForecastRun $run): bool
    {
        if (!$run->period_id || $run->prediction === null) {
            return false;
        }

        $actual = $this->resolveActualValue($run);
        if ($actual === null) {
            return false;
        }

        $pred = (float) $run->prediction;
        $absError = abs($actual - $pred);
        $errorPct = abs($actual) > 0 ? ($absError / abs($actual)) * 100 : ($pred == 0.0 ? 0.0 : 100.0);

        $run->update([
            'actual_value' => $actual,
            'error_abs' => $absError,
            'error_pct' => $errorPct,
            'evaluated_at' => now(),
        ]);

        return true;
    }

    private function resolveActualValue(MlForecastRun $run): ?float
    {
        if ($run->context === 'purchase_order_product') {
            return $this->actualProductQty($run);
        }

        if ($run->context === 'salesman_intelligence') {
            return $this->actualSalesmanRevenue($run);
        }

        if ($run->context === 'principal_report') {
            return $this->actualPrincipalNet($run);
        }

        return null;
    }

    private function applyBranchFilter($query, ?string $branch): void
    {
        if ($branch && $branch !== 'all') {
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.dist_id')) = ?", [$branch]);
        }
    }

    private function actualProductQty(MlForecastRun $run): ?float
    {
        if (!$run->entity_id) {
            return null;
        }

        $query = Sale::query()
            ->join('transactions', 'sales.transaction_id', '=', 'transactions.id')
            ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
            ->where('upload_histories.period_id', $run->period_id)
            ->where('sales.product_id', (int) $run->entity_id)
            ->where('sales.qty', '>', 0);

        $this->applyBranchFilter($query, $run->branch);

        if ($run->scope_key && $run->scope_key !== 'all') {
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name')) = ?", [$run->scope_key]);
        }

        return (float) ($query->sum('sales.qty') ?? 0);
    }

    private function actualSalesmanRevenue(MlForecastRun $run): ?float
    {
        if (!$run->entity_id) {
            return null;
        }

        $query = Sale::query()
            ->join('transactions', 'sales.transaction_id', '=', 'transactions.id')
            ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
            ->where('upload_histories.period_id', $run->period_id)
            ->where('sales.total', '>', 0)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.sales_id')) = ?", [(string) $run->entity_id]);

        $this->applyBranchFilter($query, $run->branch);

        return (float) ($query->sum('sales.total') ?? 0);
    }

    private function actualPrincipalNet(MlForecastRun $run): ?float
    {
        $query = Transaction::query()
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
            ->where('upload_histories.period_id', $run->period_id);

        $this->applyBranchFilter($query, $run->branch);

        $key = $run->scope_key ?: $run->entity_name;
        if ($key) {
            if (is_string($run->entity_id) && ctype_digit($run->entity_id)) {
                $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(sales.raw_data, "$.principle_id")) = ?', [$run->entity_id]);
            } else {
                $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(sales.raw_data, "$.principle_name")) = ?', [$key]);
            }
        }

        return (float) ($query->sum('sales.total') ?? 0);
    }
}
