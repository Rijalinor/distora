<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;

$sample = Sale::where('total', '>', 0)->first();
echo "Qty: {$sample->qty}\n";
echo "Price: {$sample->price}\n";
echo "Gross: {$sample->gross_price}\n";
echo "Total: {$sample->total}\n";

echo "Raw data array keys:\n";
print_r(array_keys($sample->raw_data));
