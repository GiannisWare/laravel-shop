<?php

namespace App\Filament\Resources\HeroSlides;

use App\Filament\Resources\HeroSlides\Pages\CreateHeroSlide;
use App\Filament\Resources\HeroSlides\Pages\EditHeroSlide;
use App\Filament\Resources\HeroSlides\Pages\ListHeroSlides;
use App\Filament\Resources\HeroSlides\Schemas\HeroSlideForm;
use App\Filament\Resources\HeroSlides\Tables\HeroSlidesTable;
use App\Models\HeroSlide;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class HeroSlideResource extends Resource
{
    protected static ?string $model = HeroSlide::class;

    protected static string|BackedEnum|null $navigationIcon = 'phosphor-images';

    protected static string|BackedEnum|null $activeNavigationIcon = 'phosphor-images-fill';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Home carousel';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return HeroSlideForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HeroSlidesTable::configure($table);
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
            'index' => ListHeroSlides::route('/'),
            'create' => CreateHeroSlide::route('/create'),
            'edit' => EditHeroSlide::route('/{record}/edit'),
        ];
    }
}
