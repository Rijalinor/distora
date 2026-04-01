<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResetController;
use App\Http\Controllers\SalesImportController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InsightController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/authenticate', [AuthController::class, 'authenticate'])->name('authenticate')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Target (KPI)
    Route::get('/targets', [TargetController::class, 'index'])->name('targets.index');

    Route::middleware('admin')->group(function () {
        Route::get('/upload/{uploadHistory}', [DashboardController::class, 'show'])->name('dashboard.show');
        Route::get('/targets/suggest', [TargetController::class, 'suggest'])->name('targets.suggest');
        Route::get('/targets/team-allocation-preview', [TargetController::class, 'teamAllocationPreview'])->name('targets.team-allocation-preview');
        Route::post('/targets/team-allocation-apply', [TargetController::class, 'teamAllocationApply'])->name('targets.team-allocation-apply');
        Route::post('/targets', [TargetController::class, 'store'])->name('targets.store');
        Route::delete('/targets/{target}', [TargetController::class, 'destroy'])->name('targets.destroy');

        // Imports
        Route::prefix('imports/sales')->group(function () {
            Route::get('/', [SalesImportController::class, 'index']);
            Route::post('/', [SalesImportController::class, 'store']);
            Route::get('/{uploadHistory}', [SalesImportController::class, 'show']);
            Route::get('/{uploadHistory}/logs', [SalesImportController::class, 'logs']);
            Route::post('/{uploadHistory}/retry', [SalesImportController::class, 'retry']);
            Route::delete('/{uploadHistory}', [SalesImportController::class, 'destroy']);
        });

        // Tutup Buku (Period Closing)
        Route::get('/reset', [ResetController::class, 'index'])->name('reset.index');
        Route::post('/reset', [ResetController::class, 'execute'])->name('reset.execute');
        Route::post('/periods', [ResetController::class, 'store'])->name('periods.store');
        Route::get('/periode/{period}', [ResetController::class, 'show'])->name('reset.show');

        // Analytics & Export
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::prefix('insights')->group(function () {
            Route::get('/', [InsightController::class, 'index'])->name('insights.index');
            Route::get('/ai-dashboard', [InsightController::class, 'aiDashboard'])->name('insights.ai-dashboard');
            Route::get('/rfm', [InsightController::class, 'rfm'])->name('insights.rfm');
            Route::get('/bundling', [InsightController::class, 'bundling'])->name('insights.bundling');
            Route::get('/discounts', [InsightController::class, 'discounts'])->name('insights.discounts');
            Route::get('/anomalies', [InsightController::class, 'anomalies'])->name('insights.anomalies');
            Route::get('/ai-advisor', [InsightController::class, 'aiAdvisor'])->name('insights.ai-advisor');
            Route::get('/salesman-audit', [InsightController::class, 'salesmanAudit'])->name('insights.salesman-audit');
            Route::get('/stock-forecast', [InsightController::class, 'stockForecast'])->name('insights.stock-forecast');
            Route::get('/stock-redistribution', [InsightController::class, 'stockRedistribution'])->name('insights.stock-redistribution');
            Route::get('/purchase-order', [InsightController::class, 'purchaseOrder'])->name('insights.purchase-order');
            Route::get('/principal-report', [InsightController::class, 'principalReport'])->name('insights.principal-report');
            Route::get('/dead-stock', [InsightController::class, 'deadStock'])->name('insights.dead-stock');
            Route::get('/salesman-intelligence', [InsightController::class, 'salesmanIntelligence'])->name('insights.salesman-intelligence');
            Route::get('/growth', [InsightController::class, 'growth'])->name('insights.growth');
            Route::get('/guide', [InsightController::class, 'guide'])->name('insights.guide');
            Route::get('/ml-monitor', [InsightController::class, 'mlMonitor'])->name('insights.ml-monitor');
        });
        Route::get('/export/{type}', [ExportController::class, 'export'])->name('export.download');

        // User Management
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
            Route::get('/tax-vat-compliance', [ReportController::class, 'taxVatCompliance'])->name('reports.tax-vat-compliance');
        });
    });
});
