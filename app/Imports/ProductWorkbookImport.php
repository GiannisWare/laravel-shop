<?php

declare(strict_types=1);

namespace App\Imports;

use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class ProductWorkbookImport implements Import, WithMultipleSheets
{
    use Importable;

    public function __construct(
        public readonly ProductImport $products,
    ) {}

    /** @return array<int, ProductImport> */
    public function sheets(): array
    {
        return [0 => $this->products];
    }
}
