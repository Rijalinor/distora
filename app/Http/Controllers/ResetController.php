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
    public function index(Request $request)
    {
        $selectedPeriodId = $request->query('period_id');
        $activePeriods = Period::active()->get();
        
        $activePeriod = $selectedPeriodId 
            ? Period::findOrFail($selectedPeriodId) 
            : Period::getActive();

        // Stats for the selected active period
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

        return view('reset.index', compact('summary', 'closedPeriods', 'activePeriods', 'activePeriod'));
    }

    /**
     * Execute period closing (Tutup Buku).
     */
    public function execute(Request $request)
    {
        $request->validate([
            'period_id' => 'required|exists:periods,id',
            'confirmation' => 'required|in:TUTUP',
        ]);

        $activePeriod = Period::findOrFail($request->period_id);
        
        if ($activePeriod->status !== 'active') {
            return back()->with('error', 'Hanya periode aktif yang bisa ditutup.');
        }

        $uploadIds = UploadHistory::where('period_id', $activePeriod->id)->pluck('id');
        $transactionIds = Transaction::whereIn('upload_history_id', $uploadIds)->pluck('id');

        // Save summary snapshot
        $summary = [
            'uploads' => $uploadIds->count(),
            'transactions' => $transactionIds->count(),
            'sales_count' => Sale::whereIn('transaction_id', $transactionIds)->where('total', '>', 0)->count(),
            'returns_count' => Sale::whereIn('transaction_id', $transactionIds)->where('total', '<', 0)->count(),
            'stocks_count' => Stock::whereIn('upload_history_id', $uploadIds)->count(),
            'outlets_count' => Outlet::count(), // Note: This counts ALL outlets, might need refinement if outlets are shared
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
            // 1. Close the selected period
            $activePeriod->update([
                'status' => 'closed',
                'closed_at' => now(),
                'summary' => $summary,
            ]);

            // Data is now preserved instead of being deleted.
            // This allows for historical analysis by filtering by period.

            DB::commit();

            return redirect()->route('reset.index')
                ->with('success', "Periode {$activePeriod->name} berhasil ditutup.");
        } catch (\Throwable $e) {
            DB::rollBack();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            return back()->with('error', 'Tutup buku gagal: ' . $e->getMessage());
        }
    }

    /**
     * Create a new period (Past or Future).
     */
    public function store(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        $date = \Carbon\Carbon::create($request->year, $request->month, 1);
        
        $exists = Period::where('year', $request->year)
            ->where('month', $request->month)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Periode untuk bulan tersebut sudah ada.');
        }

        Period::create([
            'name' => $date->translatedFormat('F Y'),
            'year' => $request->year,
            'month' => $request->month,
            'status' => 'active',
        ]);

        return back()->with('success', "Periode {$date->translatedFormat('F Y')} berhasil dibuat.");
    }

    /**
     * Show a closed period's summary.
     */
    public function show(Period $period)
    {
        return view('reset.show', compact('period'));
    }
}
