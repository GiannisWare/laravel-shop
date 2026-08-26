<?php

namespace App\Filament\Resources\HeroSlides\Schemas;

use App\Models\HeroSlide;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class HeroSlideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Campaign image')
                    ->description('Use a wide landscape image with clear space behind the text.')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('image')
                            ->collection('image')
                            ->conversion('hero')
                            ->image()
                            ->imageEditor()
                            ->openable()
                            ->maxSize(10 * 1024)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
                            ->helperText('Recommended: 1920 × 1080 px, under 5 MB. The original is preserved.')
                            ->columnSpanFull(),
                        TextInput::make('image_alt')
                            ->label('Image description')
                            ->helperText('Describe the image for screen-reader users.')
                            ->required()
                            ->maxLength(255),
                        Select::make('focal_position')
                            ->options([
                                'left' => 'Left',
                                'center' => 'Centre',
                                'right' => 'Right',
                            ])
                            ->default('center')
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Slide content')
                    ->schema([
                        TextInput::make('key')
                            ->helperText('Stable internal key, for example: summer-paints.')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->alphaDash()
                            ->maxLength(80),
                        TextInput::make('eyebrow')->maxLength(80),
                        TextInput::make('title')->required()->maxLength(120)->columnSpanFull(),
                        Textarea::make('description')->rows(3)->maxLength(300)->columnSpanFull(),
                        TextInput::make('cta_label')->label('Button label')->maxLength(80),
                        TextInput::make('cta_url')
                            ->label('Button path')
                            ->placeholder('/products')
                            ->rules([
                                fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                    if ($value !== null && (! is_string($value) || str_contains($value, '\\') || ! str_starts_with($value, '/') || str_starts_with($value, '//'))) {
                                        $fail('The button path must start with one forward slash.');
                                    }
                                },
                            ])
                            ->maxLength(2048),
                    ])
                    ->columns(2),
                Section::make('Publishing')
                    ->schema([
                        Toggle::make('is_active')
                            ->default(true)
                            ->rules([
                                fn (?HeroSlide $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                    if (! $value) {
                                        return;
                                    }

                                    $activeSlides = HeroSlide::query()
                                        ->where('is_active', true)
                                        ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                        ->count();

                                    if ($activeSlides >= 6) {
                                        $fail('Only six carousel slides may be active at once.');
                                    }
                                },
                            ]),
                    ])
                    ->columns(2),
            ]);
    }
}
