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
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ReturnSheetImport implements ToCollection, WithHeadingRow, SkipsEmptyRows, WithChunkReading, WithEvents
{
    protected int $uploadHistoryId;
    protected int $rowNumber = 1;
    protected int $successRows = 0;
    protected int $failedRows = 0;
    protected int $skippedRows = 0;
    protected array $outletIdByCode = [];
    protected array $productIdBySku = [];
    protected array $transactionIdByInvoice = [];
    protected array $existingSaleKeyCache = [];

    public function __construct(int $uploadHistoryId)
    {
        $this->uploadHistoryId = $uploadHistoryId;
    }

    public function chunkSize(): int
    {
        return 1000;
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

        // Help garbage collector between chunks
        gc_collect_cycles();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                gc_collect_cycles();
            },
        ];
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

        $invoiceNumber = $this->value($data, 'sicn_no')
            ?? $this->value($data, 'sosn_no')
            ?? $this->value($data, 'pficn_no');

        if (!$invoiceNumber) {
            throw new \RuntimeException('Missing return invoice number (SI/CN No / SO/SN No / PFI/CN No).');
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

        $qty = $this->toDecimal($this->value($data, 'qty_base'))
            ?: $this->toDecimal($this->value($data, 'qty1'));

        $price = $this->toDecimal($this->value($data, 'price_base'))
            ?: $this->toDecimal($this->value($data, 'price'));

        $taxedAmt = $this->toDecimal($this->value($data, 'taxed_amt'));
        $arAmt = $this->toDecimal($this->value($data, 'ar_amt'));
        $gross = $this->toDecimal($this->value($data, 'gross'));
        $lineTotal = $taxedAmt != 0 ? $taxedAmt : ($arAmt != 0 ? $arAmt : ($gross != 0 ? $gross : ($qty * $price)));

        $vatValue = $this->toDecimal($this->value($data, 'vat'));
        $discTotal = $this->toDecimal($this->value($data, 'disc_total'));

        // Returns should have negative values
        if ($qty > 0) {
            $qty *= -1;
        }
        if ($lineTotal > 0) {
            $lineTotal *= -1;
        }

        $soldAt = $this->parseDateTime($this->value($data, 'sicn_date'))
            ?? $this->parseDateTime($this->value($data, 'gigr_date'))
            ?? $this->parseDateTime($this->value($data, 'sosn_date'))
            ?? $this->parseDateTime($this->value($data, 'pficn_date'));

        $transactionDate = $this->parseDate($this->value($data, 'sicn_date'))
            ?? $this->parseDate($this->value($data, 'gigr_date'))
            ?? $this->parseDate($this->value($data, 'sosn_date'))
            ?? $this->parseDate($this->value($data, 'pficn_date'))
            ?? now()->toDateString();

        $outletId = $this->resolveOutletId(
            $outletCode,
            $outletName,
            $this->value($data, 'outlet_address'),
            $this->value($data, 'outlet_city'),
            $this->value($data, 'outlet_phone')
        );

        $productId = $this->resolveProductId(
            $productSku,
            $productName,
            $this->value($data, 'item_group'),
            $this->value($data, 'uom_sku')
        );

        $transactionId = $this->resolveTransactionId(
            $invoiceNumber,
            $outletId,
            $transactionDate,
            $this->buildTransactionMeta($data)
        );

        $saleKey = $transactionId . ':' . $productId;
        if (isset($this->existingSaleKeyCache[$saleKey])) {
            return 'skipped';
        }

        $existsInDb = Sale::where('transaction_id', $transactionId)
            ->where('product_id', $productId)
            ->exists();
        if ($existsInDb) {
            $this->existingSaleKeyCache[$saleKey] = true;
            return 'skipped';
        }

        DB::transaction(function () use (
            $transactionId,
            $productId,
            $qty,
            $price,
            $lineTotal,
            $vatValue,
            $discTotal,
            $soldAt,
            $data
        ) {
            Sale::create([
                'transaction_id' => $transactionId,
                'product_id' => $productId,
                'qty' => $qty,
                'price' => $price,
                'gross_price' => abs($this->toDecimal($this->value($data, 'gross'))),
                'disc_item' => $discTotal,
                'disc_internal' => 0,
                'disc_external' => 0,
                'disc_invoice' => 0,
                'total' => $lineTotal,
                'vat' => $vatValue,
                'sold_at' => $soldAt,
                'raw_data' => $data,
            ]);

            DB::table('transactions')
                ->where('id', $transactionId)
                ->increment('total', $lineTotal);
        });

        $this->existingSaleKeyCache[$saleKey] = true;
        return 'inserted';
    }

    protected function resolveOutletId(
        string $outletCode,
        string $outletName,
        ?string $address,
        ?string $city,
        ?string $phone
    ): int {
        if (isset($this->outletIdByCode[$outletCode])) {
            return $this->outletIdByCode[$outletCode];
        }

        $id = Outlet::where('code', $outletCode)->value('id');
        if (!$id) {
            try {
                $id = Outlet::create([
                    'code' => $outletCode,
                    'name' => $outletName,
                    'address' => $address,
                    'city' => $city,
                    'phone' => $phone,
                ])->id;
            } catch (QueryException $e) {
                $id = Outlet::where('code', $outletCode)->value('id');
            }
        }

        $this->outletIdByCode[$outletCode] = (int) $id;
        return (int) $id;
    }

    protected function resolveProductId(string $productSku, string $productName, ?string $category, ?string $unit): int
    {
        if (isset($this->productIdBySku[$productSku])) {
            return $this->productIdBySku[$productSku];
        }

        $id = Product::where('sku', $productSku)->value('id');
        if (!$id) {
            try {
                $id = Product::create([
                    'sku' => $productSku,
                    'name' => $productName,
                    'category' => $category,
                    'unit' => $unit,
                ])->id;
            } catch (QueryException $e) {
                $id = Product::where('sku', $productSku)->value('id');
            }
        }

        $this->productIdBySku[$productSku] = (int) $id;
        return (int) $id;
    }

    protected function resolveTransactionId(string $invoiceNumber, int $outletId, string $transactionDate, array $meta): int
    {
        if (isset($this->transactionIdByInvoice[$invoiceNumber])) {
            return $this->transactionIdByInvoice[$invoiceNumber];
        }

        $id = Transaction::where('invoice_number', $invoiceNumber)->value('id');
        if (!$id) {
            try {
                $id = Transaction::create([
                    'invoice_number' => $invoiceNumber,
                    'outlet_id' => $outletId,
                    'upload_history_id' => $this->uploadHistoryId,
                    'transaction_date' => $transactionDate,
                    'total' => 0,
                    'meta' => $meta,
                ])->id;
            } catch (QueryException $e) {
                $id = Transaction::where('invoice_number', $invoiceNumber)->value('id');
            }
        }

        $this->transactionIdByInvoice[$invoiceNumber] = (int) $id;
        return (int) $id;
    }

    protected function logError(string $message, array $row): void
    {
        ImportLog::create([
            'upload_history_id' => $this->uploadHistoryId,
            'row_number' => $this->rowNumber,
            'level' => 'error',
            'message' => '[return] ' . $message,
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

    protected function buildTransactionMeta(array $data): array
    {
        return [
            'dist_id' => $this->value($data, 'branch'),
            'branch' => $this->value($data, 'branch'),
            'branch_name' => $this->value($data, 'branch_name'),
            'supervisor' => $this->value($data, 'supervisor'),
            'sales_id' => $this->value($data, 'sales_id'),
            'sales_name' => $this->value($data, 'sales_name'),
            'type' => $this->value($data, 'type'),
            'so_sn_date_created' => $this->value($data, 'sosn_date_created'),
            'so_sn_no' => $this->value($data, 'sosn_no'),
            'so_sn_date' => $this->value($data, 'sosn_date'),
            'ref_no' => $this->value($data, 'ref_no'),
            'pfi_cn_no' => $this->value($data, 'pficn_no'),
            'pfi_cn_date' => $this->value($data, 'pficn_date'),
            'gi_gr_no' => $this->value($data, 'gigr_no'),
            'gi_gr_date' => $this->value($data, 'gigr_date'),
            'si_cn_no' => $this->value($data, 'sicn_no'),
            'month' => $this->value($data, 'month'),
            'week' => $this->value($data, 'week'),
            'warehouse' => $this->value($data, 'warehouse'),
            'outlet_class' => $this->value($data, 'outlet_class'),
            'outlet_level' => $this->value($data, 'outlet_level'),
            'outlet_group' => $this->value($data, 'outlet_group'),
            'route' => $this->value($data, 'route'),
            'notice' => $this->value($data, 'notice'),
            'principle_id' => $this->value($data, 'principle_id'),
            'principle_name' => $this->value($data, 'principle_name'),
        ];
    }
}
