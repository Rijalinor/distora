<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;

$sample = Sale::join('transactions', 'sales.transaction_id', '=', 'transactions.id')
    ->where('sales.total', '<', 0)
    ->first();

echo "Qty: " . $sample->qty . "\n";
echo "Price: " . $sample->price . "\n";
echo "Gross: " . $sample->gross_price . "\n";
echo "Total: " . $sample->total . "\n";
