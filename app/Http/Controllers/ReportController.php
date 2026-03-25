<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Stock;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // ===== SALES REPORTS =====

    public function salesSummary(Request $request)
    {
        $period = $request->get('period', 'daily'); // daily, monthly
        $dateFrom = $request->get('from');
        $dateTo = $request->get('to');

        $query = Transaction::query()
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->where('sales.total', '>', 0); // Only sales, not returns

        if ($dateFrom) $query->where('transactions.transaction_date', '>=', $dateFrom);
        if ($dateTo) $query->where('transactions.transaction_date', '<=', $dateTo);

        if ($period === 'monthly') {
            $data = $query->select(
                DB::raw("DATE_FORMAT(transactions.transaction_date, '%Y-%m') as period"),
                DB::raw('COUNT(DISTINCT transactions.id) as total_transactions'),
                DB::raw('SUM(sales.qty) as total_qty'),
                DB::raw('SUM(sales.total) as total_sales'),
                DB::raw('SUM(sales.vat) as total_vat'),
                DB::raw('SUM(sales.gross_price) as total_gross')
            )->groupBy('period')->orderBy('period', 'desc')->get();
        } else {
            $data = $query->select(
                DB::raw('transactions.transaction_date as period'),
                DB::raw('COUNT(DISTINCT transactions.id) as total_transactions'),
                DB::raw('SUM(sales.qty) as total_qty'),
                DB::raw('SUM(sales.total) as total_sales'),
                DB::raw('SUM(sales.vat) as total_vat'),
                DB::raw('SUM(sales.gross_price) as total_gross')
            )->groupBy('period')->orderBy('period', 'desc')->limit(60)->get();
        }

        return view('reports.sales-summary', compact('data', 'period', 'dateFrom', 'dateTo'));
    }

    public function topProducts(Request $request)
    {
        $limit = $request->get('limit', 20);
        $sortBy = $request->get('sort', 'value'); // value or qty

        $data = Sale::query()
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->where('sales.total', '>', 0)
            ->select(
                'products.sku',
                'products.name',
                'products.category',
                DB::raw('SUM(sales.qty) as total_qty'),
                DB::raw('SUM(sales.total) as total_value'),
                DB::raw('COUNT(DISTINCT sales.transaction_id) as total_transactions')
            )
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.category')
            ->orderByDesc($sortBy === 'qty' ? 'total_qty' : 'total_value')
            ->limit($limit)
            ->get();

        return view('reports.top-products', compact('data', 'limit', 'sortBy'));
    }

    public function topOutlets(Request $request)
    {
        $limit = $request->get('limit', 20);

        $data = Transaction::query()
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->where('sales.total', '>', 0)
            ->select(
                'outlets.code',
                'outlets.name',
                'outlets.city',
                DB::raw('SUM(sales.total) as total_sales'),
                DB::raw('SUM(sales.qty) as total_qty'),
                DB::raw('COUNT(DISTINCT transactions.id) as total_transactions')
            )
            ->groupBy('outlets.id', 'outlets.code', 'outlets.name', 'outlets.city')
            ->orderByDesc('total_sales')
            ->limit($limit)
            ->get();

        return view('reports.top-outlets', compact('data', 'limit'));
    }

    public function salesBySalesman(Request $request)
    {
        $data = Transaction::query()
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->where('sales.total', '>', 0)
            ->whereNotNull('transactions.meta')
            ->select(
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.sales_id')) as sales_id"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.sales_name')) as sales_name"),
                DB::raw('SUM(sales.total) as total_sales'),
                DB::raw('SUM(sales.qty) as total_qty'),
                DB::raw('COUNT(DISTINCT transactions.id) as total_transactions'),
                DB::raw('COUNT(DISTINCT transactions.outlet_id) as total_outlets')
            )
            ->groupBy('sales_id', 'sales_name')
            ->orderByDesc('total_sales')
            ->get()
            ->filter(fn($r) => $r->sales_id);

        return view('reports.sales-by-salesman', compact('data'));
    }

    public function salesByPrinciple(Request $request)
    {
        $data = Transaction::query()
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->where('sales.total', '>', 0)
            ->whereNotNull('transactions.meta')
            ->select(
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_id')) as principle_id"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name')) as principle_name"),
                DB::raw('SUM(sales.total) as total_sales'),
                DB::raw('SUM(sales.qty) as total_qty'),
                DB::raw('COUNT(DISTINCT transactions.id) as total_transactions')
            )
            ->groupBy('principle_id', 'principle_name')
            ->orderByDesc('total_sales')
            ->get()
            ->filter(fn($r) => $r->principle_id);

        return view('reports.sales-by-principle', compact('data'));
    }

    // ===== STOCK REPORTS =====

    public function stockByWarehouse(Request $request)
    {
        // Get latest upload with stock data
        $latestUploadId = Stock::max('upload_history_id');

        $data = Stock::query()
            ->where('upload_history_id', $latestUploadId)
            ->select(
                'branch',
                'warehouse_code',
                'warehouse_name',
                DB::raw('COUNT(*) as total_items'),
                DB::raw('SUM(stock_value_on_hand) as total_value_on_hand'),
                DB::raw('SUM(stock_value_on_sales) as total_value_on_sales'),
                DB::raw('SUM(on_hand_base) as total_on_hand'),
                DB::raw('SUM(on_sales_base) as total_on_sales')
            )
            ->groupBy('branch', 'warehouse_code', 'warehouse_name')
            ->orderBy('branch')
            ->get();

        return view('reports.stock-by-warehouse', compact('data', 'latestUploadId'));
    }

    public function slowMoving(Request $request)
    {
        $limit = $request->get('limit', 30);
        $minAge = $request->get('min_age', 60);
        $latestUploadId = Stock::max('upload_history_id');

        $data = Stock::query()
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->where('stocks.upload_history_id', $latestUploadId)
            ->where('stocks.age_of_goods', '>=', $minAge)
            ->select(
                'products.sku',
                'products.name',
                'stocks.branch',
                'stocks.warehouse_name',
                'stocks.on_hand_base',
                'stocks.stock_value_on_hand',
                'stocks.was',
                'stocks.swc',
                'stocks.age_of_goods'
            )
            ->orderByDesc('stocks.age_of_goods')
            ->limit($limit)
            ->get();

        return view('reports.slow-moving', compact('data', 'limit', 'minAge'));
    }

    public function stockCoverage(Request $request)
    {
        $maxSwc = $request->get('max_swc', 4);
        $latestUploadId = Stock::max('upload_history_id');

        $data = Stock::query()
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->where('stocks.upload_history_id', $latestUploadId)
            ->where('stocks.swc', '<=', $maxSwc)
            ->where('stocks.was', '>', 0)
            ->select(
                'products.sku',
                'products.name',
                'stocks.branch',
                'stocks.warehouse_name',
                'stocks.on_hand_base',
                'stocks.was',
                'stocks.swc',
                'stocks.stock_value_on_hand'
            )
            ->orderBy('stocks.swc')
            ->orderByDesc('stocks.was')
            ->limit(50)
            ->get();

        return view('reports.stock-coverage', compact('data', 'maxSwc'));
    }

    // ===== RETURN REPORTS =====

    public function returnRate(Request $request)
    {
        // Sales totals per product
        $salesByProduct = Sale::query()
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->select(
                'products.id as product_id',
                'products.sku',
                'products.name',
                DB::raw('SUM(CASE WHEN sales.total > 0 THEN sales.qty ELSE 0 END) as sales_qty'),
                DB::raw('SUM(CASE WHEN sales.total > 0 THEN sales.total ELSE 0 END) as sales_value'),
                DB::raw('SUM(CASE WHEN sales.total < 0 THEN ABS(sales.qty) ELSE 0 END) as return_qty'),
                DB::raw('SUM(CASE WHEN sales.total < 0 THEN ABS(sales.total) ELSE 0 END) as return_value')
            )
            ->groupBy('products.id', 'products.sku', 'products.name')
            ->havingRaw('SUM(CASE WHEN sales.total < 0 THEN 1 ELSE 0 END) > 0')
            ->orderByDesc('return_value')
            ->limit(30)
            ->get()
            ->map(function ($item) {
                $item->return_rate_qty = $item->sales_qty > 0 ? round(($item->return_qty / $item->sales_qty) * 100, 1) : 0;
                $item->return_rate_value = $item->sales_value > 0 ? round(($item->return_value / $item->sales_value) * 100, 1) : 0;
                return $item;
            });

        return view('reports.return-rate', compact('salesByProduct'));
    }

    public function topReturns(Request $request)
    {
        $data = Sale::query()
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->join('transactions', 'sales.transaction_id', '=', 'transactions.id')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->where('sales.total', '<', 0)
            ->select(
                'products.sku',
                'products.name as product_name',
                'outlets.name as outlet_name',
                'outlets.code as outlet_code',
                DB::raw('ABS(SUM(sales.qty)) as return_qty'),
                DB::raw('ABS(SUM(sales.total)) as return_value'),
                'transactions.transaction_date'
            )
            ->groupBy('products.id', 'products.sku', 'products.name', 'outlets.id', 'outlets.name', 'outlets.code', 'transactions.transaction_date')
            ->orderByDesc('return_value')
            ->limit(30)
            ->get();

        return view('reports.top-returns', compact('data'));
    }

    // ===== FINANCIAL REPORTS =====

    public function discountSummary(Request $request)
    {
        $data = Sale::query()
            ->where('total', '>', 0)
            ->select(
                DB::raw('SUM(disc_item) as total_disc_item'),
                DB::raw('SUM(disc_internal) as total_disc_internal'),
                DB::raw('SUM(disc_external) as total_disc_external'),
                DB::raw('SUM(disc_invoice) as total_disc_invoice'),
                DB::raw('SUM(disc_item + disc_internal + disc_external + disc_invoice) as total_discount'),
                DB::raw('SUM(total) as total_sales'),
                DB::raw('SUM(gross_price) as total_gross'),
                DB::raw('SUM(vat) as total_vat'),
                DB::raw('COUNT(*) as total_rows')
            )
            ->first();

        // Per principle breakdown
        $perPrinciple = Transaction::query()
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->where('sales.total', '>', 0)
            ->select(
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.principle_name')) as principle_name"),
                DB::raw('SUM(sales.gross_price) as gross'),
                DB::raw('SUM(sales.total) as net'),
                DB::raw('SUM(sales.disc_item + sales.disc_internal + sales.disc_external + sales.disc_invoice) as discount'),
                DB::raw('SUM(sales.vat) as vat')
            )
            ->groupBy('principle_name')
            ->orderByDesc('gross')
            ->get()
            ->filter(fn($r) => $r->principle_name);

        return view('reports.discount-summary', compact('data', 'perPrinciple'));
    }

    public function grossVsNet(Request $request)
    {
        $data = Transaction::query()
            ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
            ->where('sales.total', '>', 0)
            ->select(
                DB::raw("DATE_FORMAT(transactions.transaction_date, '%Y-%m-%d') as date"),
                DB::raw('SUM(sales.gross_price) as gross'),
                DB::raw('SUM(sales.total) as net'),
                DB::raw('SUM(sales.gross_price - sales.total) as discount_amount'),
                DB::raw('SUM(sales.vat) as vat')
            )
            ->groupBy('date')
            ->orderByDesc('date')
            ->limit(30)
            ->get()
            ->map(function ($item) {
                $item->discount_pct = $item->gross > 0 ? round((($item->gross - $item->net) / $item->gross) * 100, 2) : 0;
                return $item;
            });

        return view('reports.gross-vs-net', compact('data'));
    }
}
