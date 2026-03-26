<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Imports\SalesSheetImport;

class TestImport extends SalesSheetImport {
    public function __construct() { parent::__construct(1); }
    public function testMeta($data) { return $this->buildTransactionMeta($data); }
}

$data = [
    'dist_id' => '', // This is what Excel might return for an empty cell
    'branch' => 'OBM_03',
    'branch_name' => 'PT. OBOR'
];

$import = new TestImport();
print_r($import->testMeta($data));
