<?php

namespace App\Http\Controllers;

use App\Exports\GenericExport;
use App\Models\Period;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function export(Request $request, $type)
    {
        $period = Period::getActive();
        $uploadIds = $period->uploadHistories()->pluck('id');

        $data = [];
        $headings = [];
        $fileName = "Export_{$type}_{$period->name}.xlsx";

        switch ($type) {
            case 'sales-summary':
                $headings = ['Tanggal', 'Jumlah Transaksi', 'Qty Terjual', 'Gross Price', 'Diskon', 'Net Sales', 'PPN'];
                $query = Transaction::query()
                    ->whereIn('upload_history_id', $uploadIds)
                    ->join('sales', 'transactions.id', '=', 'sales.transaction_id')
                    ->where('sales.total', '>', 0)
                    ->select(
                        DB::raw('transactions.transaction_date as date'),
                        DB::raw('COUNT(DISTINCT transactions.id) as trans_count'),
                        DB::raw('SUM(sales.qty) as qty'),
                        DB::raw('SUM(sales.gross_price) as gross'),
                        DB::raw('SUM(sales.disc_item + sales.disc_internal + sales.disc_external + sales.disc_invoice) as discount'),
                        DB::raw('SUM(sales.total) as net'),
                        DB::raw('SUM(sales.vat) as vat')
                    )
                    ->groupBy('date')
                    ->orderByDesc('date')
                    ->get();
                
                foreach ($query as $row) {
                    $data[] = [
                        $row->date,
                        $row->trans_count,
                        $row->qty,
                        $row->gross,
                        $row->discount,
                        $row->net,
                        $row->vat,
                    ];
                }
                break;

            case 'top-products':
                $headings = ['Nama Produk', 'Terjual (Qty)', 'Nilai Sales (Rp)'];
                $transIds = Transaction::whereIn('upload_history_id', $uploadIds)->pluck('id');
                $query = Sale::query()
                    ->whereIn('transaction_id', $transIds)
                    ->join('products', 'sales.product_id', '=', 'products.id')
                    ->where('sales.total', '>', 0)
                    ->select(
                        'products.name',
                        DB::raw('SUM(sales.qty) as qty'),
                        DB::raw('SUM(sales.total) as total')
                    )
                    ->groupBy('products.id', 'products.name')
                    ->orderByDesc('total')
                    ->get();
                    
                foreach ($query as $row) {
                    $data[] = [
                        $row->name,
                        $row->qty,
                        $row->total,
                    ];
                }
                break;

            case 'stock-by-warehouse':
                $headings = ['Cabang', 'Lokasi Gudang', 'Total Item', 'Total Qty', 'Nilai Stok (Rp)'];
                $latestUploadId = Stock::whereIn('upload_history_id', $uploadIds)->max('upload_history_id');
                $query = Stock::query()
                    ->where('upload_history_id', $latestUploadId)
                    ->select(
                        'branch',
                        'warehouse_name',
                        DB::raw('COUNT(*) as total_items'),
                        DB::raw('SUM(on_hand) as total_qty'),
                        DB::raw('SUM(stock_value_on_hand) as total_value')
                    )
                    ->groupBy('branch', 'warehouse_name')
                    ->orderBy('branch')
                    ->get();
                    
                foreach ($query as $row) {
                    $data[] = [
                        $row->branch,
                        $row->warehouse_name,
                        $row->total_items,
                        $row->total_qty,
                        $row->total_value,
                    ];
                }
                break;

            default:
                return back()->with('error', 'Tipe export tidak ditemukan.');
        }

        return Excel::download(new GenericExport($data, $headings), $fileName);
    }
}
