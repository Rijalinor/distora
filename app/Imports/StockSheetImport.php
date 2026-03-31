<?php

namespace App\Imports;

use App\Models\ImportLog;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Illuminate\Database\QueryException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\AfterSheet;

class StockSheetImport implements ToModel, WithHeadingRow, SkipsEmptyRows, WithChunkReading, WithBatchInserts, WithEvents
{
    protected int $uploadHistoryId;
    protected string $branch;
    protected int $rowNumber = 1;
    protected int $successRows = 0;
    protected int $failedRows = 0;
    protected array $productIdBySku = [];

    public function __construct(int $uploadHistoryId, string $branch)
    {
        $this->uploadHistoryId = $uploadHistoryId;
        $this->branch = $branch;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                gc_collect_cycles();
            },
        ];
    }

    public function model(array $row): ?Stock
    {
        $this->rowNumber++;

        try {
            return $this->processRow($row);
        } catch (\Throwable $e) {
            $this->failedRows++;
            $this->logError($e->getMessage(), $row);
            return null;
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

    protected function processRow(array $data): ?Stock
    {
        $itemNo = $this->value($data, 'item');
        if (!$itemNo) {
            throw new \RuntimeException('Missing Item#.');
        }

        $productId = $this->resolveProductId(
            $itemNo,
            $this->value($data, 'item_description') ?? 'Unknown Product'
        );

        $this->successRows++;

        return new Stock([
            'upload_history_id' => $this->uploadHistoryId,
            'product_id' => $productId,
            'branch' => $this->branch,
            'principle_code' => $this->value($data, 'principle'),
            'principle_name' => $this->value($data, 'principle_description'),
            'warehouse_code' => $this->value($data, 'warehouse'),
            'warehouse_name' => $this->value($data, 'warehouse_description'),
            'location_code' => $this->value($data, 'location'),
            'location_name' => $this->value($data, 'location_description'),
            'on_hand' => $this->toDecimal($this->value($data, 'onhand_base'))
                ?: $this->toDecimal($this->value($data, 'onhand')),
            'on_sales' => $this->toDecimal($this->value($data, 'onsales_base'))
                ?: $this->toDecimal($this->value($data, 'onsales')),
            'on_hand_base' => $this->toDecimal($this->value($data, 'onhand_base')),
            'on_sales_base' => $this->toDecimal($this->value($data, 'onsales_base')),
            'stock_value_on_hand' => $this->toDecimal($this->value($data, 'stock_value_onhand')),
            'stock_value_on_sales' => $this->toDecimal($this->value($data, 'stock_value_onsales')),
            'tonnage' => $this->toDecimal($this->value($data, 'tonnage')),
            'was' => $this->toDecimal(
                $this->value($data, 'was') ?? $this->value($data, 'week_average_sales')
            ),
            'swc' => (int) $this->toDecimal(
                $this->value($data, 'swc') ?? $this->value($data, 'sales_week_coverage')
            ),
            'age_of_goods' => (int) $this->toDecimal($this->value($data, 'age_of_goods')),
            'raw_data' => $data,
        ]);
    }

    protected function resolveProductId(string $sku, string $name): int
    {
        if (isset($this->productIdBySku[$sku])) {
            return $this->productIdBySku[$sku];
        }

        $id = Product::where('sku', $sku)->value('id');
        if (!$id) {
            try {
                $id = Product::create([
                    'sku' => $sku,
                    'name' => $name,
                    'category' => null,
                ])->id;
            } catch (QueryException $e) {
                $id = Product::where('sku', $sku)->value('id');
            }
        }

        $this->productIdBySku[$sku] = (int) $id;
        return (int) $id;
    }

    protected function logError(string $message, array $row): void
    {
        ImportLog::create([
            'upload_history_id' => $this->uploadHistoryId,
            'row_number' => $this->rowNumber,
            'level' => 'error',
            'message' => "[stock {$this->branch}] {$message}",
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
}
