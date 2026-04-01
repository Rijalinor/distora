<?php

namespace App\Services;

use App\Models\Period;
use Illuminate\Support\Facades\Cache;

class InsightIndexSummaryService
{
    /**
     * Cache one metric independently so expensive metrics don't block each other.
     */
    public function metric(string $metric, Period $period, string $branch, callable $callback): mixed
    {
        $key = "insight_metric_v1_{$metric}_{$period->id}_{$branch}";

        if ($period->status === 'closed') {
            return Cache::driver('file')->rememberForever($key, $callback);
        }

        return Cache::driver('file')->remember($key, 600, $callback);
    }

    /**
     * Build index summary with per-metric cache keys.
     */
    public function build(Period $period, string $branch, array $resolvers): array
    {
        return [
            'outlets' => (int) $this->metric('index_outlets', $period, $branch, $resolvers['outlets']),
            'bundles' => $this->metric('index_bundles', $period, $branch, $resolvers['bundles']),
            'advisor' => (int) $this->metric('index_advisor', $period, $branch, $resolvers['advisor']),
            'stock_alerts' => (int) $this->metric('index_stock_alerts', $period, $branch, $resolvers['stock_alerts']),
            'redistribution' => (int) $this->metric('index_redistribution', $period, $branch, $resolvers['redistribution']),
            'dead_stock' => (int) $this->metric('index_dead_stock', $period, $branch, $resolvers['dead_stock']),
        ];
    }
}
