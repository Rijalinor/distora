<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesImportRequest;
use App\Jobs\ProcessSalesImportJob;
use App\Models\ImportLog;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\UploadHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SalesImportController extends Controller
{
    /**
     * Upload file and dispatch import job.
     */
    public function store(StoreSalesImportRequest $request): JsonResponse
    {
        if (!auth('web')->check() || auth('web')->user()->role !== 'admin') {
            abort(403, 'Unauthorized');
        }

        $file = $request->file('file');

        $uploadHistory = UploadHistory::create([
            'period_id' => $request->period_id ?? \App\Models\Period::getActive()->id,
            'file_name' => $file->getClientOriginalName(),
            'stored_path' => $file->store('imports/sales'),
            'uploaded_by' => optional($request->user())->id,
            'status' => 'pending',
        ]);

        ProcessSalesImportJob::dispatch($uploadHistory->id);

        return response()->json([
            'message' => 'File berhasil diupload. Import sedang diproses.',
            'upload_history_id' => $uploadHistory->id,
            'status' => 'pending',
        ], 202);
    }

    /**
     * List all upload histories.
     */
    public function index(): JsonResponse
    {
        $histories = UploadHistory::orderByDesc('created_at')
            ->paginate(20);

        return response()->json($histories);
    }

    /**
     * Show detail of a specific upload.
     */
    public function show(UploadHistory $uploadHistory): JsonResponse
    {
        return response()->json([
            'data' => $uploadHistory,
            'summary' => [
                'transactions_count' => Transaction::where('upload_history_id', $uploadHistory->id)->count(),
                'sales_count' => Sale::whereHas('transaction', fn ($q) => $q->where('upload_history_id', $uploadHistory->id))->count(),
                'stocks_count' => Stock::where('upload_history_id', $uploadHistory->id)->count(),
                'error_count' => ImportLog::where('upload_history_id', $uploadHistory->id)->count(),
            ],
        ]);
    }

    /**
     * Show error logs for a specific upload.
     */
    public function logs(UploadHistory $uploadHistory): JsonResponse
    {
        $logs = ImportLog::where('upload_history_id', $uploadHistory->id)
            ->orderBy('row_number')
            ->paginate(50);

        return response()->json($logs);
    }

    /**
     * Retry a failed import.
     */
    public function retry(UploadHistory $uploadHistory): JsonResponse
    {
        if (!auth('web')->check() || auth('web')->user()->role !== 'admin') {
            abort(403, 'Unauthorized');
        }

        if (!in_array($uploadHistory->status, ['failed', 'partial'])) {
            return response()->json([
                'message' => 'Hanya upload dengan status failed atau partial yang bisa di-retry.',
            ], 422);
        }

        // Delete all data from this upload
        $this->deleteUploadData($uploadHistory);

        // Reset status
        $uploadHistory->update([
            'status' => 'pending',
            'total_rows' => 0,
            'success_rows' => 0,
            'failed_rows' => 0,
            'started_at' => null,
            'finished_at' => null,
            'errors_summary' => null,
        ]);

        ProcessSalesImportJob::dispatch($uploadHistory->id);

        return response()->json([
            'message' => 'Import di-retry. Sedang diproses ulang.',
            'upload_history_id' => $uploadHistory->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Delete an upload and all its related data.
     */
    public function destroy(UploadHistory $uploadHistory): JsonResponse
    {
        if (!auth('web')->check() || auth('web')->user()->role !== 'admin') {
            abort(403, 'Unauthorized');
        }

        $this->deleteUploadData($uploadHistory);

        // Delete the stored file
        if ($uploadHistory->stored_path) {
            Storage::delete($uploadHistory->stored_path);
        }

        $uploadHistory->delete();

        return response()->json([
            'message' => 'Upload dan semua data terkait berhasil dihapus.',
        ]);
    }

    /**
     * Delete all data associated with an upload.
     */
    protected function deleteUploadData(UploadHistory $uploadHistory): void
    {
        // Delete sales via transactions
        $transactionIds = Transaction::where('upload_history_id', $uploadHistory->id)
            ->pluck('id');
        Sale::whereIn('transaction_id', $transactionIds)->delete();

        // Delete transactions
        Transaction::where('upload_history_id', $uploadHistory->id)->delete();

        // Delete stocks
        Stock::where('upload_history_id', $uploadHistory->id)->delete();

        // Delete import logs
        ImportLog::where('upload_history_id', $uploadHistory->id)->delete();
    }
}
