<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;

$sample = Sale::join('transactions', 'sales.transaction_id', '=', 'transactions.id')
    ->where('sales.total', '<', 0)
    // Find one that was recently added or unpatched
    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.dist_id')) IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.dist_id')) = ''")
    ->first();

if ($sample) {
    echo "Raw Transaction meta from database:\n";
    echo $sample->transaction->getAttributes()['meta'] . "\n";
} else {
    echo "All returns have valid dist_id now.\n";
}
