<?php

namespace App\Http\Controllers;

use App\Models\Period;
use App\Models\Target;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TargetController extends Controller
{
    public function index()
    {
        $period = Period::getActive();
        $uploadIds = $period->uploadHistories()->pluck('id');

        // Get actual running sales numbers for active period
        // For Salesman
        $actualSalesman = Transaction::query()
            ->whereIn('upload_history_id', $uploadIds)
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->where('sales.total', '>', 0)
            ->whereNotNull('transactions.meta')
            ->select(
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.sales_name')) as name"),
                DB::raw('SUM(sales.total) as actual_amount')
            )
            ->groupBy('name')
            ->pluck('actual_amount', 'name');

        // For Principle
        $actualPrinciple = Transaction::query()
            ->whereIn('upload_history_id', $uploadIds)
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->where('sales.total', '>', 0)
            ->whereNotNull('transactions.meta')
            ->select(
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name')) as name"),
                DB::raw('SUM(sales.total) as actual_amount')
            )
            ->groupBy('name')
            ->pluck('actual_amount', 'name');

        // Fetch targets set for this period
        $user = auth()->user();
        $targetQuery = Target::where('period_id', $period->id);

        if ($user && $user->role === 'salesman') {
            $targetQuery->where('type', 'salesman')
                        ->where('name', $user->linked_salesman_name);
        }

        $targets = $targetQuery->get();

        // Calculate progress for each target
        $targetData = $targets->map(function ($target) use ($actualSalesman, $actualPrinciple) {
            $actual = $target->type === 'salesman' 
                        ? ($actualSalesman[$target->name] ?? 0) 
                        : ($actualPrinciple[$target->name] ?? 0);
            
            $progress = $target->target_amount > 0 
                        ? round(($actual / $target->target_amount) * 100, 1) 
                        : 0;

            return (object) [
                'id' => $target->id,
                'type' => $target->type,
                'name' => $target->name,
                'target' => $target->target_amount,
                'actual' => $actual,
                'progress' => min($progress, 150), // Cap visual at 150%
                'raw_progress' => $progress
            ];
        });

        $salesmanTargets = $targetData->where('type', 'salesman')->sortByDesc('raw_progress');
        $principleTargets = $targetData->where('type', 'principle')->sortByDesc('raw_progress');
        
        // Extract names for the dropdown forms
        $salesmanNames = $actualSalesman->keys()->sort()->values();
        $principleNames = $actualPrinciple->keys()->sort()->values();

        return view('targets.index', compact('period', 'salesmanTargets', 'principleTargets', 'salesmanNames', 'principleNames'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Hanya Admin yang dapat mengubah target.');
        }

        $request->validate([
            'type' => 'required|in:salesman,principle',
            'name' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:1',
        ]);

        $period = Period::getActive();

        // Use updateOrCreate so if user submits same name, it just updates amount
        Target::updateOrCreate(
            [
                'period_id' => $period->id,
                'type' => $request->type,
                'name' => strtoupper($request->name),
            ],
            [
                'target_amount' => $request->target_amount,
            ]
        );

        return back()->with('success', 'Target berhasil disimpan.');
    }

    public function destroy(Target $target)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Hanya Admin yang dapat menghapus target.');
        }

        $target->delete();
        return back()->with('success', 'Target berhasil dihapus.');
    }
}
