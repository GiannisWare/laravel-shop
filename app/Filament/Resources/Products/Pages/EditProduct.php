<?php

namespace App\Filament\Resources\Products\Pages;

use App\Actions\Products\UpdateProductImageAltText;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editImageAltText')
                ->label('Image alt text')
                ->icon('phosphor-pencil-simple-line')
                ->visible(fn (): bool => $this->product()->getMedia('images')->isNotEmpty())
                ->fillForm(fn (): array => [
                    'images' => $this->product()->getMedia('images')
                        ->map(fn ($media): array => [
                            'id' => $media->getKey(),
                            'file_name' => $media->file_name,
                            'alt' => $media->getCustomProperty('alt', $this->product()->name),
                        ])
                        ->values()
                        ->all(),
                ])
                ->schema([
                    Repeater::make('images')
                        ->label('Image descriptions')
                        ->schema([
                            Hidden::make('id'),
                            TextInput::make('file_name')
                                ->disabled()
                                ->dehydrated(false),
                            TextInput::make('alt')
                                ->label('Alternative text')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false),
                ])
                ->action(function (array $data, UpdateProductImageAltText $updateAltText): void {
                    $updateAltText->handle($this->product(), $data['images'] ?? []);
                })
                ->successNotificationTitle('Image alt text updated'),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    private function product(): Product
    {
        /** @var Product $record */
        $record = $this->getRecord();

        return $record;
    }
}
