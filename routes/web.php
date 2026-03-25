<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResetController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/authenticate', [AuthController::class, 'authenticate'])->name('authenticate')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/upload/{uploadHistory}', [DashboardController::class, 'show'])->name('dashboard.show');

    // Tutup Buku (Period Closing)
    Route::get('/reset', [ResetController::class, 'index'])->name('reset.index');
    Route::post('/reset', [ResetController::class, 'execute'])->name('reset.execute');
    Route::get('/periode/{period}', [ResetController::class, 'show'])->name('reset.show');

    // Analytics & Export
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/export/{type}', [ExportController::class, 'export'])->name('export.download');

    // Target (KPI)
    Route::get('/targets', [TargetController::class, 'index'])->name('targets.index');
    Route::post('/targets', [TargetController::class, 'store'])->name('targets.store');
    Route::delete('/targets/{target}', [TargetController::class, 'destroy'])->name('targets.destroy');

    // User Management (Admin only)
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Reports
    Route::prefix('reports')->group(function () {
        // Sales
        Route::get('/sales-summary', [ReportController::class, 'salesSummary'])->name('reports.sales-summary');
        Route::get('/top-products', [ReportController::class, 'topProducts'])->name('reports.top-products');
        Route::get('/top-outlets', [ReportController::class, 'topOutlets'])->name('reports.top-outlets');
        Route::get('/sales-by-salesman', [ReportController::class, 'salesBySalesman'])->name('reports.sales-by-salesman');
        Route::get('/sales-by-principle', [ReportController::class, 'salesByPrinciple'])->name('reports.sales-by-principle');

        // Stock
        Route::get('/stock-by-warehouse', [ReportController::class, 'stockByWarehouse'])->name('reports.stock-by-warehouse');
        Route::get('/slow-moving', [ReportController::class, 'slowMoving'])->name('reports.slow-moving');
        Route::get('/stock-coverage', [ReportController::class, 'stockCoverage'])->name('reports.stock-coverage');

        // Return
        Route::get('/return-rate', [ReportController::class, 'returnRate'])->name('reports.return-rate');
        Route::get('/top-returns', [ReportController::class, 'topReturns'])->name('reports.top-returns');

        // Financial
        Route::get('/discount-summary', [ReportController::class, 'discountSummary'])->name('reports.discount-summary');
        Route::get('/gross-vs-net', [ReportController::class, 'grossVsNet'])->name('reports.gross-vs-net');
    });
});
