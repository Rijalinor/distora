<?php

namespace App\Imports;

use App\Models\ImportLog;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StockSheetImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected int $uploadHistoryId;
    protected string $branch;
    protected int $rowNumber = 1;
    protected int $successRows = 0;
    protected int $failedRows = 0;

    public function __construct(int $uploadHistoryId, string $branch)
    {
        $this->uploadHistoryId = $uploadHistoryId;
        $this->branch = $branch;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $this->rowNumber++;

            try {
                $this->processRow($row);
                $this->successRows++;
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

    protected function processRow(Collection $row): void
    {
        $data = $row->toArray();

        $itemNo = $this->value($data, 'item');
        if (!$itemNo) {
            throw new \RuntimeException('Missing Item#.');
        }

        $product = Product::firstOrCreate(
            ['sku' => $itemNo],
            [
                'name' => $this->value($data, 'item_description') ?? 'Unknown Product',
                'category' => null,
            ]
        );

        Stock::create([
            'upload_history_id' => $this->uploadHistoryId,
            'product_id' => $product->id,
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
