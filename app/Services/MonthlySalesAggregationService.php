<?php

namespace App\Services;

use App\Models\MonthlyProductSalesStat;
use App\Models\Period;
use Illuminate\Support\Facades\DB;

class MonthlySalesAggregationService
{
    public function rebuildForPeriod(int $periodId): int
    {
        $base = DB::table('sales')
            ->join('transactions', 'sales.transaction_id', '=', 'transactions.id')
            ->join('upload_histories', 'transactions.upload_history_id', '=', 'upload_histories.id')
            ->where('upload_histories.period_id', $periodId)
            ->where('sales.qty', '>', 0)
            ->selectRaw('
                upload_histories.period_id as period_id,
                COALESCE(JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, "$.dist_id")), "") as branch_dist_id,
                COALESCE(JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, "$.principle_name")), "") as principle_name,
                sales.product_id as product_id,
                sales.qty as qty,
                sales.total as total_net,
                sales.disc_item as total_disc_item
            ');

        $rows = DB::query()
            ->fromSub($base, 'src')
            ->selectRaw('
                src.period_id,
                src.branch_dist_id,
                src.principle_name,
                src.product_id,
                SUM(src.qty) as qty_sold,
                SUM(src.total_net) as total_net,
                SUM(src.total_disc_item) as total_disc_item
            ')
            ->groupBy('src.period_id', 'src.branch_dist_id', 'src.principle_name', 'src.product_id')
            ->get();

        DB::transaction(function () use ($periodId, $rows) {
            MonthlyProductSalesStat::where('period_id', $periodId)->delete();

            if ($rows->isEmpty()) {
                return;
            }

            $timestamp = now();
            $payload = $rows->map(function ($row) use ($timestamp) {
                return [
                    'period_id' => (int) $row->period_id,
                    'branch_dist_id' => (string) ($row->branch_dist_id ?? ''),
                    'principle_name' => (string) ($row->principle_name ?? ''),
                    'product_id' => (int) $row->product_id,
                    'qty_sold' => (float) $row->qty_sold,
                    'total_net' => (float) $row->total_net,
                    'total_disc_item' => (float) $row->total_disc_item,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })->all();

            foreach (array_chunk($payload, 1500) as $chunk) {
                MonthlyProductSalesStat::insert($chunk);
            }
        });

        return $rows->count();
    }

    public function rebuildForPeriods(array $periodIds): int
    {
        $total = 0;
        foreach ($periodIds as $periodId) {
            $total += $this->rebuildForPeriod((int) $periodId);
        }

        return $total;
    }

    public function rebuildAllAvailablePeriods(): int
    {
        $periodIds = Period::query()->orderBy('year')->orderBy('month')->pluck('id')->all();
        return $this->rebuildForPeriods($periodIds);
    }
}
