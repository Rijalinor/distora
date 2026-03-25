<?php

namespace App\Jobs;

use App\Imports\SalesWorkbookImport;
use App\Models\UploadHistory;
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
