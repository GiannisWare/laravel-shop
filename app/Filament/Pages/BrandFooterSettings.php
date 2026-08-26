<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use LogicException;
use UnitEnum;

final class BrandFooterSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'phosphor-paint-brush';

    protected static string|BackedEnum|null $activeNavigationIcon = 'phosphor-paint-brush-fill';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Brand & footer';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Brand & footer';

    protected string $view = 'filament.pages.brand-footer-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    private ?SiteSetting $settings = null;

    public function mount(): void
    {
        $this->settings = SiteSetting::query()->firstOrCreate(['id' => 1], ['site_name' => 'Kokkonios']);
        $this->settingsForm()->fill($this->settings->attributesToArray());
    }

    public function getSubheading(): string
    {
        return 'Manage the public logo, utility strip and footer copy from one place.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->model($this->getSettings())
            ->statePath('data')
            ->components([
                Section::make('Brand')
                    ->description('The name remains as a text fallback whenever no logo is uploaded.')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('brand_logo')
                            ->collection('brand_logo')
                            ->conversion('logo')
                            ->image()
                            ->openable()
                            ->maxSize(5 * 1024)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/svg+xml'])
                            ->helperText('Use a wide transparent PNG, WebP or SVG. Recommended width: 500–900 px.')
                            ->columnSpanFull(),
                        TextInput::make('site_name')->required()->maxLength(100),
                    ]),
                Section::make('Utility strip')
                    ->description('Short messages shown above the main menu. Leave any field empty to hide it.')
                    ->schema([
                        TextInput::make('utility_left_text')->label('Left message')->maxLength(120),
                        TextInput::make('utility_center_text')->label('Centre message')->maxLength(120),
                        TextInput::make('utility_right_text')->label('Right message')->maxLength(120),
                    ])
                    ->columns(3),
                Section::make('Footer')
                    ->schema([
                        TextInput::make('footer_heading')->maxLength(120),
                        Textarea::make('footer_description')->rows(4)->maxLength(500)->columnSpanFull(),
                        TextInput::make('copyright_text')->maxLength(180)->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function save(): void
    {
        $settings = $this->getSettings();
        $settings->fill($this->settingsForm()->getState())->save();
        $this->settingsForm()->model($settings)->saveRelationships();

        Notification::make()->title('Brand settings saved')->success()->send();
    }

    private function getSettings(): SiteSetting
    {
        return $this->settings ??= SiteSetting::query()->firstOrCreate(['id' => 1], ['site_name' => 'Kokkonios']);
    }

    private function settingsForm(): Schema
    {
        return $this->getSchema('form')
            ?? throw new LogicException('The brand settings form schema is not registered.');
    }
}
