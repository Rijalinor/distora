<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Fill dist_id from branch
    $affectedId = DB::update("
        UPDATE transactions 
        SET meta = JSON_SET(meta, '$.dist_id', JSON_UNQUOTE(JSON_EXTRACT(meta, '$.branch'))) 
        WHERE JSON_EXTRACT(meta, '$.dist_id') IS NULL 
        AND JSON_EXTRACT(meta, '$.branch') IS NOT NULL
    ");
    echo "Updated dist_id: $affectedId rows\n";

    // Fill dist_name from branch_name
    $affectedName = DB::update("
        UPDATE transactions 
        SET meta = JSON_SET(meta, '$.dist_name', JSON_UNQUOTE(JSON_EXTRACT(meta, '$.branch_name'))) 
        WHERE JSON_EXTRACT(meta, '$.dist_name') IS NULL 
        AND JSON_EXTRACT(meta, '$.branch_name') IS NOT NULL
    ");
    echo "Updated dist_name: $affectedName rows\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
