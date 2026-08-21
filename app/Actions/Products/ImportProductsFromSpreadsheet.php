<?php

namespace App\Actions\Products;

use App\Data\ProductImportResult;
use App\Imports\ProductImport;
use Maatwebsite\Excel\Validators\Failure;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ImportProductsFromSpreadsheet
{
    public function handle(string|UploadedFile $file): ProductImportResult
    {
        $import = new ProductImport;
        $import->import($file);

        $failures = array_values($import->failures()
            ->map(fn (Failure $failure): array => [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'messages' => $failure->errors(),
            ])
            ->all());

        return new ProductImportResult(
            created: $import->createdCount(),
            updated: $import->updatedCount(),
            failures: $failures,
        );
    }
}
