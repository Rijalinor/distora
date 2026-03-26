<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

$dates = Sale::join('transactions', 'sales.transaction_id', '=', 'transactions.id')
    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.dist_id')) = 'OBM_01'")
    ->select(
        DB::raw('DATE(transactions.transaction_date) as date'),
        DB::raw('SUM(sales.total) as total')
    )
    ->groupBy('date')
    ->orderBy('date')
    ->get();

echo "Sales by date for OBM_01:\n";
foreach ($dates as $d) {
    echo "{$d->date}: " . number_format($d->total, 0, '.', ',') . "\n";
}
