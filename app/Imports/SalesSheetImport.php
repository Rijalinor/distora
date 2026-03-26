<?php

namespace App\Imports;

use App\Models\ImportLog;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class SalesSheetImport implements ToCollection, WithHeadingRow, SkipsEmptyRows, WithChunkReading
{
    protected int $uploadHistoryId;
    protected int $rowNumber = 1;
    protected int $successRows = 0;
    protected int $failedRows = 0;
    protected int $skippedRows = 0;

    public function __construct(int $uploadHistoryId)
    {
        $this->uploadHistoryId = $uploadHistoryId;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $this->rowNumber++;

            try {
                $result = $this->processRow($row);
                if ($result === 'skipped') {
                    $this->skippedRows++;
                } else {
                    $this->successRows++;
                }
            } catch (\Throwable $e) {
                $this->failedRows++;
                $this->logError($e->getMessage(), $row->toArray());
            }
        }
    }

    public function getSuccessRows(): int
    {
        return $this->successRows;
    }

    public function getFailedRows(): int
    {
        return $this->failedRows;
    }

    public function getSkippedRows(): int
    {
        return $this->skippedRows;
    }

    protected function processRow(Collection $row): string
    {
        $data = $row->toArray();

        $invoiceNumber = $this->value($data, 'si_no')
            ?? $this->value($data, 'so_no')
            ?? $this->value($data, 'pfi_no');

        if (!$invoiceNumber) {
            throw new \RuntimeException('Missing invoice number (SI_NO / SO_NO / PFI_NO).');
        }

        $outletCode = $this->value($data, 'outlet_id');
        $outletName = $this->value($data, 'outlet_name') ?? 'Unknown Outlet';

        if (!$outletCode) {
            throw new \RuntimeException('Missing outlet_id.');
        }

        $productSku = $this->value($data, 'item_no');
        $productName = $this->value($data, 'item_name') ?? 'Unknown Product';

        if (!$productSku) {
            throw new \RuntimeException('Missing item_no.');
        }

        $seqNo = (int) $this->toDecimal($this->value($data, 'seq_no'));
        $qty = $this->toDecimal($this->value($data, 'qty'));
        $price = $this->toDecimal($this->value($data, 'selling_price'));
        $grossPrice = $this->toDecimal($this->value($data, 'gross_price'));
        $subtotal = $this->toDecimal($this->value($data, 'subtotal'));
        $lineTotal = $subtotal > 0 ? $subtotal : ($qty * $price);

        $discItem = $this->toDecimal($this->value($data, 'disc_item'));
        $discInt = $this->toDecimal($this->value($data, 'disc_int'));
        $discExt = $this->toDecimal($this->value($data, 'disc_ext'));
        $discInvoice = $this->toDecimal($this->value($data, 'disc_invoice'));
        $vat = $this->toDecimal($this->value($data, 'vat'));

        $soldAt = $this->parseDateTime($this->value($data, 'si_created_date'))
            ?? $this->parseDateTime($this->value($data, 'so_date'))
            ?? $this->parseDateTime($this->value($data, 'pfi_date'));

        $transactionDate = $this->parseDate($this->value($data, 'si_created_date'))
            ?? $this->parseDate($this->value($data, 'so_date'))
            ?? $this->parseDate($this->value($data, 'pfi_date'))
            ?? now()->toDateString();

        return DB::transaction(function () use (
            $data, $invoiceNumber, $outletCode, $outletName,
            $productSku, $productName, $seqNo,
            $qty, $price, $grossPrice, $lineTotal,
            $discItem, $discInt, $discExt, $discInvoice, $vat,
            $soldAt, $transactionDate
        ) {
            $outlet = Outlet::firstOrCreate(
                ['code' => $outletCode],
                [
                    'name' => $outletName,
                    'address' => $this->mergeAddress(
                        $this->value($data, 'outlet_add1'),
                        $this->value($data, 'outlet_add2')
                    ),
                ]
            );

            $product = Product::firstOrCreate(
                ['sku' => $productSku],
                [
                    'name' => $productName,
                    'category' => $this->value($data, 'item_class_desc'),
                ]
            );

            $transaction = Transaction::firstOrCreate(
                ['invoice_number' => $invoiceNumber],
                [
                    'outlet_id' => $outlet->id,
                    'upload_history_id' => $this->uploadHistoryId,
                    'transaction_date' => $transactionDate,
                    'total' => 0,
                    'meta' => $this->buildTransactionMeta($data),
                ]
            );

            // Dedup: check if this exact line item already exists
            $existingSale = Sale::where('transaction_id', $transaction->id)
                ->where('product_id', $product->id)
                ->where('seq_no', $seqNo)
                ->first();

            if ($existingSale) {
                return 'skipped';
            }

            Sale::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'seq_no' => $seqNo,
                'qty' => $qty,
                'price' => $price,
                'gross_price' => $grossPrice,
                'disc_item' => $discItem,
                'disc_internal' => $discInt,
                'disc_external' => $discExt,
                'disc_invoice' => $discInvoice,
                'total' => $lineTotal,
                'vat' => $vat,
                'sold_at' => $soldAt,
                'raw_data' => $data,
            ]);

            $transaction->total += $lineTotal;
            $transaction->save();

            return 'inserted';
        });
    }

    protected function logError(string $message, array $row): void
    {
        ImportLog::create([
            'upload_history_id' => $this->uploadHistoryId,
            'row_number' => $this->rowNumber,
            'level' => 'error',
            'message' => $message,
            'raw_data' => $row,
        ]);
    }

    protected function value(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;
        if ($value === null) {
            return null;
        }
        $value = is_string($value) ? trim($value) : $value;
        return $value === '' ? null : (string) $value;
    }

    protected function toDecimal(?string $value): float
    {
        if ($value === null) {
            return 0.0;
        }
        $clean = str_replace([',', ' '], ['', ''], $value);
        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    protected function parseDateTime(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }
        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($value));
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function parseDate(?string $value): ?string
    {
        $dateTime = $this->parseDateTime($value);
        return $dateTime ? $dateTime->toDateString() : null;
    }

    protected function mergeAddress(?string $add1, ?string $add2): ?string
    {
        $parts = array_filter([$add1, $add2]);
        return empty($parts) ? null : implode(' ', $parts);
    }

    protected function buildTransactionMeta(array $data): array
    {
        return [
            'dist_id' => $this->value($data, 'dist_id') ?? $this->value($data, 'branch'),
            'dist_name' => $this->value($data, 'dist_name') ?? $this->value($data, 'branch_name'),
            'pfi_created_date' => $this->value($data, 'pfi_created_date'),
            'pfi_no' => $this->value($data, 'pfi_no'),
            'so_created_date' => $this->value($data, 'so_created_date'),
            'so_no' => $this->value($data, 'so_no'),
            'si_created_date' => $this->value($data, 'si_created_date'),
            'si_no' => $this->value($data, 'si_no'),
            'po_no' => $this->value($data, 'po_no'),
            'so_date' => $this->value($data, 'so_date'),
            'pfi_date' => $this->value($data, 'pfi_date'),
            'due_date' => $this->value($data, 'due_date'),
            'supervisor' => $this->value($data, 'supervisor'),
            'sales_id' => $this->value($data, 'sales_id'),
            'sales_name' => $this->value($data, 'sales_name'),
            'outlet_class_id' => $this->value($data, 'outlet_class_id'),
            'outlet_class_desc' => $this->value($data, 'outlet_class_desc'),
            'principle_id' => $this->value($data, 'principle_id'),
            'principle_name' => $this->value($data, 'principle_name'),
        ];
    }
}
