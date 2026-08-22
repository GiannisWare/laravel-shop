<?php

namespace App\Filament\Resources\Products\Pages;

use App\Actions\Products\ImportProductsFromSpreadsheet;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importProducts')
                ->label('Import products')
                ->icon('phosphor-upload-simple')
                ->schema([
                    FileUpload::make('file')
                        ->label('Excel file')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'text/plain',
                        ])
                        ->storeFiles(false)
                        ->required(),
                ])
                ->modalDescription('Required headings: source_id or barcode, name, slug. Optional headings: category_slug, brand_slug, short_description, description, availability, is_published, discontinued_at.')
                ->modalSubmitActionLabel('Import')
                ->action(function (array $data, ImportProductsFromSpreadsheet $importer): void {
                    $file = $data['file'];

                    if (! $file instanceof TemporaryUploadedFile) {
                        Notification::make()
                            ->title('The uploaded file could not be read.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $result = $importer->handle($file);
                    $failureCount = count($result->failures);

                    $notification = Notification::make()
                        ->title("Imported {$result->imported()} product(s)")
                        ->body("Created: {$result->created}. Updated: {$result->updated}. Failures: {$failureCount}.\n\n{$result->failureSummary()}");

                    $failureCount > 0
                        ? $notification->warning()
                        : $notification->success();

                    $notification->send();
                }),
            CreateAction::make()
                ->icon('phosphor-plus'),
        ];
    }
}
