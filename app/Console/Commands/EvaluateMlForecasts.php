<?php

namespace App\Console\Commands;

use App\Services\MlForecastEvaluator;
use Illuminate\Console\Command;

class EvaluateMlForecasts extends Command
{
    protected $signature = 'distora:ml-evaluate {--period_id=} {--context=} {--branch=} {--limit=500}';

    protected $description = 'Evaluate ML forecast runs against actual realized values';

    public function handle(MlForecastEvaluator $evaluator): int
    {
        $periodId = $this->option('period_id') ? (int) $this->option('period_id') : null;
        $context = $this->option('context') ?: null;
        $branch = $this->option('branch') ?: null;
        $limit = max(1, (int) $this->option('limit'));

        $count = $evaluator->evaluatePending($periodId, $context, $branch, $limit);

        $this->info("Evaluasi selesai. {$count} run diperbarui.");
        return self::SUCCESS;
    }
}
