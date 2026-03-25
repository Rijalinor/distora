<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SalesWorkbookImport implements WithMultipleSheets
{
    protected int $uploadHistoryId;
    protected ?SalesSheetImport $salesSheetImport = null;
    protected ?ReturnSheetImport $returnSheetImport = null;

    /** @var StockSheetImport[] */
    protected array $stockSheetImports = [];

    public function __construct(int $uploadHistoryId)
    {
        $this->uploadHistoryId = $uploadHistoryId;
    }

    public function sheets(): array
    {
        $this->salesSheetImport = new SalesSheetImport($this->uploadHistoryId);
        $this->returnSheetImport = new ReturnSheetImport($this->uploadHistoryId);

        $stockBranches = ['bjm', 'brb', 'btl', 'ampah'];
        foreach ($stockBranches as $branch) {
            $this->stockSheetImports[$branch] = new StockSheetImport($this->uploadHistoryId, $branch);
        }

        return [
            'penjualan'   => $this->salesSheetImport,
            'return'      => $this->returnSheetImport,
            'stock bjm'   => $this->stockSheetImports['bjm'],
            'stock brb'   => $this->stockSheetImports['brb'],
            'stock btl'   => $this->stockSheetImports['btl'],
            'stock ampah' => $this->stockSheetImports['ampah'],
        ];
    }

    public function getSuccessRows(): int
    {
        $total = ($this->salesSheetImport?->getSuccessRows() ?? 0)
            + ($this->returnSheetImport?->getSuccessRows() ?? 0);

        foreach ($this->stockSheetImports as $import) {
            $total += $import->getSuccessRows();
        }

        return $total;
    }

    public function getFailedRows(): int
    {
        $total = ($this->salesSheetImport?->getFailedRows() ?? 0)
            + ($this->returnSheetImport?->getFailedRows() ?? 0);

        foreach ($this->stockSheetImports as $import) {
            $total += $import->getFailedRows();
        }

        return $total;
    }

    public function getSkippedRows(): int
    {
        return ($this->salesSheetImport?->getSkippedRows() ?? 0)
            + ($this->returnSheetImport?->getSkippedRows() ?? 0);
    }
}
