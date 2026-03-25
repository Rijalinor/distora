<?php

use App\Http\Controllers\SalesImportController;
use Illuminate\Support\Facades\Route;

Route::prefix('imports/sales')->group(function () {
    Route::get('/', [SalesImportController::class, 'index']);
    Route::post('/', [SalesImportController::class, 'store']);
    Route::get('/{uploadHistory}', [SalesImportController::class, 'show']);
    Route::get('/{uploadHistory}/logs', [SalesImportController::class, 'logs']);
    Route::post('/{uploadHistory}/retry', [SalesImportController::class, 'retry']);
    Route::delete('/{uploadHistory}', [SalesImportController::class, 'destroy']);
});
