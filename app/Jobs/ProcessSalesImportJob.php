<?php

namespace App\Jobs;

use App\Imports\SalesWorkbookImport;
use App\Models\UploadHistory;
use App\Services\MonthlySalesAggregationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessSalesImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct(
        protected int $uploadHistoryId
    ) {}

    public function handle(): void
    {
        ini_set('memory_limit', '2048M');
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $uploadHistory = UploadHistory::findOrFail($this->uploadHistoryId);

        $uploadHistory->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $import = new SalesWorkbookImport($uploadHistory->id);
            $absolutePath = Storage::path($uploadHistory->stored_path);

            Excel::import($import, $absolutePath);

            $successRows = $import->getSuccessRows();
            $failedRows = $import->getFailedRows();
            $skippedRows = $import->getSkippedRows();
            $totalRows = $successRows + $failedRows + $skippedRows;

            $uploadHistory->update([
                'status' => $failedRows > 0 ? 'partial' : 'success',
                'total_rows' => $totalRows,
                'success_rows' => $successRows,
                'failed_rows' => $failedRows,
                'finished_at' => now(),
            ]);

            Log::info('Sales import completed', [
                'upload_history_id' => $uploadHistory->id,
                'total' => $totalRows,
                'success' => $successRows,
                'failed' => $failedRows,
                'skipped' => $skippedRows,
            ]);

            if ($uploadHistory->period_id) {
                try {
                    app(MonthlySalesAggregationService::class)->rebuildForPeriod((int) $uploadHistory->period_id);
                } catch (\Throwable $e) {
                    Log::warning('Failed to rebuild monthly aggregates after import', [
                        'upload_history_id' => $uploadHistory->id,
                        'period_id' => $uploadHistory->period_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Sales import failed', [
                'upload_history_id' => $uploadHistory->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $uploadHistory->update([
                'status' => 'failed',
                'finished_at' => now(),
                'errors_summary' => $e->getMessage(),
            ]);
        }
    }
}
