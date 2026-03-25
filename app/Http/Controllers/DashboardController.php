<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Models\Outlet;
use App\Models\Period;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Target;
use App\Models\Transaction;
use App\Models\UploadHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $activePeriod = Period::getActive();

        // Salesman gets a personalized dashboard
        if ($user->role === 'salesman') {
            return $this->salesmanDashboard($user, $activePeriod);
        }

        // Admin gets the normal dashboard
        $histories = UploadHistory::where('period_id', $activePeriod->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        $stats = [
            'total_uploads' => UploadHistory::where('period_id', $activePeriod->id)->count(),
            'outlets' => Outlet::count(),
            'products' => Product::count(),
            'transactions' => Transaction::count(),
            'sales' => Sale::count(),
            'stocks' => Stock::count(),
        ];

        return view('dashboard.index', compact('histories', 'stats', 'activePeriod'));
    }

    protected function salesmanDashboard($user, $activePeriod)
    {
        $salesName = $user->linked_salesman_name;
        $uploadIds = $activePeriod->uploadHistories()->pluck('id');

        // 1. Get transaction IDs for this salesman
        $myTransactions = Transaction::query()
            ->whereIn('upload_history_id', $uploadIds)
            ->whereNotNull('meta')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.sales_name')) = ?", [$salesName]);

        $myTransactionIds = (clone $myTransactions)->pluck('id');

        // 2. Summary stats
        $totalOmzet = Sale::whereIn('transaction_id', $myTransactionIds)
            ->where('total', '>', 0)
            ->sum('total');

        $totalTransaksi = $myTransactionIds->count();

        $totalOutlets = (clone $myTransactions)->distinct('outlet_id')->count('outlet_id');

        $totalItems = Sale::whereIn('transaction_id', $myTransactionIds)
            ->where('total', '>', 0)
            ->sum('qty');

        // Return transactions (total < 0 or specific return logic)
        $returnTransactionIds = Transaction::query()
            ->whereIn('upload_history_id', $uploadIds)
            ->whereNotNull('meta')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.sales_name')) = ?", [$salesName])
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.type')) = 'return'")
            ->pluck('id');

        $totalRetur = Sale::whereIn('transaction_id', $returnTransactionIds)->sum('total');
        $totalReturCount = $returnTransactionIds->count();

        // 3. KPI Target
        $target = Target::where('period_id', $activePeriod->id)
            ->where('type', 'salesman')
            ->where('name', $salesName)
            ->first();

        $kpiProgress = 0;
        if ($target && $target->target_amount > 0) {
            $kpiProgress = round(($totalOmzet / $target->target_amount) * 100, 1);
        }

        // 4. List of Outlets served (Paginated, ordered by latest transaction)
        $outletIds = (clone $myTransactions)->distinct()->pluck('outlet_id');
        $outlets = Outlet::whereIn('id', $outletIds)
            ->withCount(['transactions' => function ($q) use ($myTransactionIds) {
                $q->whereIn('id', $myTransactionIds);
            }])
            ->withMax(['transactions' => function ($q) use ($myTransactionIds) {
                $q->whereIn('id', $myTransactionIds);
            }], 'transaction_date')
            ->orderByDesc('transactions_max_transaction_date')
            ->paginate(20, ['*'], 'outlets_page')
            ->withQueryString();

        // 5. Recent Transactions with items (penjualan)
        $recentSales = Transaction::query()
            ->whereIn('upload_history_id', $uploadIds)
            ->whereNotNull('meta')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.sales_name')) = ?", [$salesName])
            ->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.type')), 'sale') != 'return'")
            ->with(['outlet', 'sales.product'])
            ->orderByDesc('transaction_date')
            ->limit(50)
            ->get();

        // 6. Recent Returns
        $recentReturns = Transaction::query()
            ->whereIn('upload_history_id', $uploadIds)
            ->whereNotNull('meta')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.sales_name')) = ?", [$salesName])
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.type')) = 'return'")
            ->with(['outlet', 'sales.product'])
            ->orderByDesc('transaction_date')
            ->limit(30)
            ->get();

        return view('dashboard.salesman', compact(
            'activePeriod', 'salesName',
            'totalOmzet', 'totalTransaksi', 'totalOutlets', 'totalItems',
            'totalRetur', 'totalReturCount',
            'target', 'kpiProgress',
            'outlets', 'recentSales', 'recentReturns'
        ));
    }

    public function show(UploadHistory $uploadHistory)
    {
        $logs = ImportLog::where('upload_history_id', $uploadHistory->id)
            ->orderBy('row_number')
            ->paginate(50);

        $summary = [
            'transactions' => Transaction::where('upload_history_id', $uploadHistory->id)->count(),
            'sales' => Sale::whereHas('transaction', fn ($q) => $q->where('upload_history_id', $uploadHistory->id))->count(),
            'stocks' => Stock::where('upload_history_id', $uploadHistory->id)->count(),
        ];

        return view('dashboard.show', compact('uploadHistory', 'logs', 'summary'));
    }
}
