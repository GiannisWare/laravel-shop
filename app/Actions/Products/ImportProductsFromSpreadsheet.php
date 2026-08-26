<?php

namespace App\Actions\Products;

use App\Data\ProductImportResult;
use App\Imports\ProductImport;
use App\Imports\ProductWorkbookImport;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Validators\Failure;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ImportProductsFromSpreadsheet
{
    public function handle(string|UploadedFile $file): ProductImportResult
    {
        $import = new ProductImport;
        $disk = null;

        if ($file instanceof TemporaryUploadedFile) {
            $disk = FileUploadConfiguration::disk();
            $file = $file->getRealPath();
        }

        if ($this->isCsvOrTextFile($file)) {
            $import->import($file, $disk);
        } else {
            (new ProductWorkbookImport($import))->import($file, $disk);
        }

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

    private function isCsvOrTextFile(string|UploadedFile $file): bool
    {
        $extension = $file instanceof UploadedFile
            ? $file->getClientOriginalExtension()
            : pathinfo($file, PATHINFO_EXTENSION);

        return in_array(strtolower($extension), ['csv', 'txt'], true);
    }
}
