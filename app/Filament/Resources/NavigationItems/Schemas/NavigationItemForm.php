<?php

namespace App\Filament\Resources\NavigationItems\Schemas;

use App\Enums\NavigationDestination;
use App\Enums\NavigationLocation;
use App\Models\NavigationItem;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

final class NavigationItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Menu item')
                    ->description('Choose where the item appears and where it sends visitors.')
                    ->schema([
                        Select::make('location')
                            ->options(collect(NavigationLocation::cases())->mapWithKeys(
                                fn (NavigationLocation $location): array => [$location->value => $location->label()],
                            ))
                            ->default(NavigationLocation::Header->value)
                            ->live()
                            ->required(),
                        TextInput::make('label')
                            ->required()
                            ->maxLength(80),
                        Select::make('parent_id')
                            ->label('Parent item')
                            ->helperText('Optional. Select a top-level item to create one submenu level.')
                            ->options(fn (Get $get, ?NavigationItem $record): array => NavigationItem::query()
                                ->whereNull('parent_id')
                                ->where('location', $get('location') ?: NavigationLocation::Header->value)
                                ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                ->orderBy('sort_order')
                                ->pluck('label', 'id')
                                ->all())
                            ->searchable()
                            ->preload(),
                        Select::make('destination')
                            ->options(collect(NavigationDestination::cases())->mapWithKeys(
                                fn (NavigationDestination $destination): array => [$destination->value => $destination->label()],
                            ))
                            ->default(NavigationDestination::Catalogue->value)
                            ->live()
                            ->afterStateUpdated(function (mixed $state, Set $set): void {
                                if ($state !== NavigationDestination::ExternalUrl->value) {
                                    $set('open_in_new_tab', false);
                                }
                            })
                            ->required(),
                        Select::make('category_id')
                            ->relationship('category', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get): bool => $get('destination') === NavigationDestination::Category->value)
                            ->visible(fn (Get $get): bool => $get('destination') === NavigationDestination::Category->value),
                        TextInput::make('url')
                            ->label(fn (Get $get): string => $get('destination') === NavigationDestination::ExternalUrl->value ? 'External URL' : 'Internal path')
                            ->placeholder(fn (Get $get): string => $get('destination') === NavigationDestination::ExternalUrl->value ? 'https://example.com' : '/contact')
                            ->required(fn (Get $get): bool => in_array($get('destination'), [NavigationDestination::InternalPath->value, NavigationDestination::ExternalUrl->value], true))
                            ->visible(fn (Get $get): bool => in_array($get('destination'), [NavigationDestination::InternalPath->value, NavigationDestination::ExternalUrl->value], true))
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    if ($get('destination') === NavigationDestination::InternalPath->value
                                        && (! is_string($value) || ! str_starts_with($value, '/') || str_starts_with($value, '//'))) {
                                        $fail('The internal path must start with one forward slash.');
                                    }

                                    if ($get('destination') === NavigationDestination::ExternalUrl->value
                                        && (! is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false || ! preg_match('/^https?:\/\//i', $value))) {
                                        $fail('The external URL must be a valid HTTP or HTTPS address.');
                                    }
                                },
                            ])
                            ->maxLength(2048),
                    ])
                    ->columns(2),
                Section::make('Display')
                    ->schema([
                        Toggle::make('is_active')
                            ->default(true)
                            ->required(),
                        Toggle::make('open_in_new_tab')
                            ->label('Open in a new tab')
                            ->visible(fn (Get $get): bool => $get('destination') === NavigationDestination::ExternalUrl->value)
                            ->default(false),
                    ])
                    ->columns(3),
                Section::make('Submenu preview')
                    ->description('Shown beside this submenu item in the desktop mega menu.')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('menu_preview')
                            ->label('Reference image')
                            ->collection('menu_preview')
                            ->conversion('menu-preview')
                            ->image()
                            ->openable()
                            ->maxSize(10 * 1024)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
                            ->customHeaders([
                                'CacheControl' => 'public, max-age=31536000, immutable',
                            ])
                            ->helperText('Recommended: landscape 1600–2400 px wide, approximately 8:5, under 5 MB. The original is preserved.')
                            ->columnSpanFull(),
                        TextInput::make('preview_image_alt')
                            ->label('Image description')
                            ->helperText('Briefly describe the image for screen-reader users.')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Get $get): bool => $get('location') === NavigationLocation::Header->value && filled($get('parent_id'))),
            ]);
    }
}
