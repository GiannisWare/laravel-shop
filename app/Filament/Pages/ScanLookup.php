<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Actions\Products\ScanProductBarcode;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Rules\Ean13;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use LogicException;
use UnitEnum;

final class ScanLookup extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'phosphor-barcode';

    protected static string|BackedEnum|null $activeNavigationIcon = 'phosphor-barcode-fill';

    protected static string|UnitEnum|null $navigationGroup = 'Scanning';

    protected static ?string $navigationLabel = 'Scan products';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Scan products';

    protected string $view = 'filament.pages.scan-lookup';

    /** @var array{barcode?: string}|null */
    public ?array $data = [];

    public ?int $scannedProductId = null;

    public bool $createdDraft = false;

    public function mount(): void
    {
        $this->scanForm()->fill();
    }

    public function getSubheading(): string
    {
        return 'Scan an EAN-13 barcode to find its product or create a safe unpublished draft.';
    }

    public function getMaxContentWidth(): Width
    {
        return Width::SevenExtraLarge;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('barcode')
                    ->label('Product barcode')
                    ->placeholder('Scan or type 13 digits, then press Enter')
                    ->helperText('Most USB and Bluetooth scanners work as keyboards and submit with Enter.')
                    ->prefixIcon('phosphor-barcode')
                    ->required()
                    ->length(13)
                    ->rule(new Ean13)
                    ->autofocus()
                    ->extraInputAttributes([
                        'x-ref' => 'barcode',
                        'autocomplete' => 'off',
                        'inputmode' => 'numeric',
                    ]),
            ])
            ->statePath('data');
    }

    public function scan(ScanProductBarcode $scanProductBarcode): void
    {
        $state = $this->scanForm()->getState();
        $product = $scanProductBarcode->handle((string) $state['barcode']);

        $this->scannedProductId = (int) $product->getKey();
        $this->createdDraft = $product->wasRecentlyCreated;

        $this->sendResultNotification($product);
        $this->scanForm()->fill();
        $this->dispatch('scan-finished');
    }

    public function getScannedProduct(): ?Product
    {
        if ($this->scannedProductId === null) {
            return null;
        }

        return Product::query()
            ->withTrashed()
            ->with(['brand', 'category'])
            ->find($this->scannedProductId);
    }

    public function getScannedProductEditUrl(): ?string
    {
        $product = $this->getScannedProduct();

        return $product === null
            ? null
            : ProductResource::getUrl('edit', ['record' => $product]);
    }

    private function sendResultNotification(Product $product): void
    {
        if ($product->trashed()) {
            Notification::make()
                ->title('Archived product found')
                ->body('The barcode already belongs to an archived product. No duplicate was created.')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title($this->createdDraft ? 'Draft product created' : 'Product found')
            ->body($this->createdDraft
                ? 'The barcode is saved as an unpublished draft, ready to complete.'
                : 'The existing product is ready to review.')
            ->success()
            ->send();
    }

    private function scanForm(): Schema
    {
        return $this->getSchema('form')
            ?? throw new LogicException('The scan form schema is not registered.');
    }
}
