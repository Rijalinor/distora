<?php

namespace App\Console\Commands;

use App\Models\Period;
use App\Services\MonthlySalesAggregationService;
use Illuminate\Console\Command;

class AggregateMonthlySales extends Command
{
    protected $signature = 'distora:aggregate-monthly-sales
                            {--period_id= : Rebuild hanya untuk period_id tertentu}
                            {--all : Rebuild semua periode yang tersedia}';

    protected $description = 'Build monthly product sales aggregates for faster analytics queries.';

    public function handle(MonthlySalesAggregationService $service): int
    {
        $periodId = $this->option('period_id');
        $all = (bool) $this->option('all');

        if ($all) {
            $count = $service->rebuildAllAvailablePeriods();
            $this->info("Selesai rebuild agregasi semua periode. Total group rows: {$count}");
            return self::SUCCESS;
        }

        if ($periodId) {
            $count = $service->rebuildForPeriod((int) $periodId);
            $this->info("Selesai rebuild agregasi period_id={$periodId}. Total group rows: {$count}");
            return self::SUCCESS;
        }

        $active = Period::where('status', 'active')->first();
        if (!$active) {
            $this->warn('Tidak ada periode aktif.');
            return self::SUCCESS;
        }

        $count = $service->rebuildForPeriod($active->id);
        $this->info("Selesai rebuild agregasi periode aktif ({$active->name}). Total group rows: {$count}");

        return self::SUCCESS;
    }
}

