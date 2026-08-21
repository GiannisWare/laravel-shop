<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductAvailability;
use App\Rules\Ean13;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('source_id')
                    ->label('Source ID')
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('barcode')
                    ->nullable()
                    ->rule(new Ean13)
                    ->unique(ignoreRecord: true)
                    ->length(13),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('brand_id')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Textarea::make('short_description')
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->rows(8)
                    ->columnSpanFull(),
                Select::make('availability')
                    ->options(collect(ProductAvailability::cases())
                        ->mapWithKeys(fn (ProductAvailability $availability): array => [
                            $availability->value => $availability->label(),
                        ]))
                    ->required()
                    ->default(ProductAvailability::InStock->value),
                Toggle::make('is_published')
                    ->required()
                    ->default(true),
                DateTimePicker::make('discontinued_at'),
            ]);
    }
}
