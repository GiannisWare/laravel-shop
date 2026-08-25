<?php

namespace App\Filament\Resources\NavigationItems;

use App\Filament\Resources\NavigationItems\Pages\CreateNavigationItem;
use App\Filament\Resources\NavigationItems\Pages\EditNavigationItem;
use App\Filament\Resources\NavigationItems\Pages\ListNavigationItems;
use App\Filament\Resources\NavigationItems\Schemas\NavigationItemForm;
use App\Filament\Resources\NavigationItems\Tables\NavigationItemsTable;
use App\Models\NavigationItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class NavigationItemResource extends Resource
{
    protected static ?string $model = NavigationItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'phosphor-list-dashes';

    protected static string|BackedEnum|null $activeNavigationIcon = 'phosphor-list-dashes-fill';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Menu';

    protected static ?string $modelLabel = 'menu item';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Schema $schema): Schema
    {
        return NavigationItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NavigationItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNavigationItems::route('/'),
            'create' => CreateNavigationItem::route('/create'),
            'edit' => EditNavigationItem::route('/{record}/edit'),
        ];
    }
}
