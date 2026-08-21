<?php

namespace App\Filament\Resources\Attributes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AttributeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                Toggle::make('is_filterable')
                    ->required()
                    ->default(true),
            ]);
    }
}
