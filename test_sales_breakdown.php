<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

$totalsByPrinciple = Sale::join('transactions', 'sales.transaction_id', '=', 'transactions.id')
    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.dist_id')) = 'OBM_01'")
    ->where('sales.total', '>', 0) // Gross Sales
    ->select(
        DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name')) as principle"),
        DB::raw('SUM(sales.total) as total_gross'),
        DB::raw('SUM(sales.gross_price) as gross_price'),
        DB::raw('SUM(sales.qty * sales.price) as expected_gross')
    )
    ->groupBy('principle')
    ->orderByDesc('total_gross')
    ->get();

echo "Banjarmasin (OBM_01) Gross Sales by Principle:\n";
$sum = 0;
foreach ($totalsByPrinciple as $t) {
    echo "{$t->principle}: " . number_format($t->total_gross, 0, '.', ',') . "\n";
    $sum += $t->total_gross;
}
echo "--------------------------------------------------\n";
echo "Total Computed: " . number_format($sum, 0, '.', ',') . "\n\n";

$totalsWithReturns = Sale::join('transactions', 'sales.transaction_id', '=', 'transactions.id')
    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.dist_id')) = 'OBM_01'")
    ->select(
        DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name')) as principle"),
        DB::raw('SUM(sales.total) as total_net')
    )
    ->groupBy('principle')
    ->orderByDesc('total_net')
    ->get();

echo "Banjarmasin (OBM_01) Net Sales (including returns) by Principle:\n";
$sumNet = 0;
foreach ($totalsWithReturns as $t) {
    echo "{$t->principle}: " . number_format($t->total_net, 0, '.', ',') . "\n";
    $sumNet += $t->total_net;
}
echo "--------------------------------------------------\n";
echo "Total Net Computed: " . number_format($sumNet, 0, '.', ',') . "\n";
