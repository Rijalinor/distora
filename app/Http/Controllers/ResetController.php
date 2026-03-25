<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Models\Outlet;
use App\Models\Period;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\UploadHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ResetController extends Controller
{
    /**
     * Show the closing confirmation page.
     */
    public function index()
    {
        $activePeriod = Period::getActive();

        // Stats for the active period
        $uploadIds = UploadHistory::where('period_id', $activePeriod->id)->pluck('id');
        $transactionIds = Transaction::whereIn('upload_history_id', $uploadIds)->pluck('id');

        $summary = [
            'period' => $activePeriod,
            'upload_histories' => $uploadIds->count(),
            'transactions' => $transactionIds->count(),
            'sales' => Sale::whereIn('transaction_id', $transactionIds)->where('total', '>', 0)->count(),
            'returns' => Sale::whereIn('transaction_id', $transactionIds)->where('total', '<', 0)->count(),
            'stocks' => Stock::whereIn('upload_history_id', $uploadIds)->count(),
            'outlets' => Outlet::count(),
            'products' => Product::count(),
            'total_sales_value' => Sale::whereIn('transaction_id', $transactionIds)->where('total', '>', 0)->sum('total'),
            'total_return_value' => Sale::whereIn('transaction_id', $transactionIds)->where('total', '<', 0)->sum('total'),
        ];

        // History of closed periods
        $closedPeriods = Period::where('status', 'closed')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return view('reset.index', compact('summary', 'closedPeriods'));
    }

    /**
     * Execute period closing (Tutup Buku).
     */
    public function execute(Request $request)
    {
        $request->validate([
            'confirmation' => 'required|in:TUTUP',
        ]);

        $activePeriod = Period::getActive();
        $uploadIds = UploadHistory::where('period_id', $activePeriod->id)->pluck('id');
        $transactionIds = Transaction::whereIn('upload_history_id', $uploadIds)->pluck('id');

        // Save summary snapshot
        $summary = [
            'uploads' => $uploadIds->count(),
            'transactions' => $transactionIds->count(),
            'sales_count' => Sale::whereIn('transaction_id', $transactionIds)->where('total', '>', 0)->count(),
            'returns_count' => Sale::whereIn('transaction_id', $transactionIds)->where('total', '<', 0)->count(),
            'stocks_count' => Stock::whereIn('upload_history_id', $uploadIds)->count(),
            'outlets_count' => Outlet::count(),
            'products_count' => Product::count(),
            'total_sales' => Sale::whereIn('transaction_id', $transactionIds)->where('total', '>', 0)->sum('total'),
            'total_returns' => Sale::whereIn('transaction_id', $transactionIds)->where('total', '<', 0)->sum('total'),
            'total_gross' => Sale::whereIn('transaction_id', $transactionIds)->where('total', '>', 0)->sum('gross_price'),
            'total_vat' => Sale::whereIn('transaction_id', $transactionIds)->sum('vat'),
            'total_discount' => Sale::whereIn('transaction_id', $transactionIds)->where('total', '>', 0)
                ->selectRaw('SUM(disc_item + disc_internal + disc_external + disc_invoice) as total')->value('total') ?? 0,
        ];

        DB::beginTransaction();
        try {
            // 1. Close the current period
            $activePeriod->update([
                'status' => 'closed',
                'closed_at' => now(),
                'summary' => $summary,
            ]);

            // 2. Delete all transactional data for this period
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            ImportLog::whereIn('upload_history_id', $uploadIds)->delete();
            Sale::whereIn('transaction_id', $transactionIds)->delete();
            Stock::whereIn('upload_history_id', $uploadIds)->delete();
            Transaction::whereIn('upload_history_id', $uploadIds)->delete();

            // Delete uploaded files
            $storedPaths = UploadHistory::whereIn('id', $uploadIds)->pluck('stored_path')->filter();
            foreach ($storedPaths as $path) {
                Storage::delete($path);
            }
            UploadHistory::whereIn('id', $uploadIds)->delete();

            // 3. Clear outlets & products (will be recreated on next import)
            Outlet::truncate();
            Product::truncate();

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // 4. Auto-create next period
            $nextMonth = now()->addMonth();
            Period::create([
                'name' => $nextMonth->translatedFormat('F Y'),
                'year' => $nextMonth->year,
                'month' => $nextMonth->month,
                'status' => 'active',
            ]);

            DB::commit();

            return redirect()->route('dashboard')
                ->with('success', "Periode {$activePeriod->name} berhasil ditutup. Data bulan baru siap digunakan.");
        } catch (\Throwable $e) {
            DB::rollBack();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            return back()->with('error', 'Tutup buku gagal: ' . $e->getMessage());
        }
    }

    /**
     * Show a closed period's summary.
     */
    public function show(Period $period)
    {
        return view('reset.show', compact('period'));
    }
}
