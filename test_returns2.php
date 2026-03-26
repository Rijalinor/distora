<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;

$sample = Sale::join('transactions', 'sales.transaction_id', '=', 'transactions.id')
    ->where('sales.total', '<', 0)
    // Find the newest one
    ->orderBy('sales.id', 'desc')
    ->first();

if ($sample) {
    echo "Sample Transaction Meta:\n";
    print_r($sample->transaction->meta);
    
    echo "Raw Data:\n";
    print_r($sample->raw_data);
}
