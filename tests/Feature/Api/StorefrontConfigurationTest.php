<?php

use App\Enums\NavigationDestination;
use App\Enums\NavigationLocation;
use App\Models\HeroSlide;
use App\Models\NavigationItem;
use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Conversions\ConversionCollection;

beforeEach(function () {
    Cache::flush();
    Storage::fake('public');
    Queue::fake();
    config()->set('media-library.disk_name', 'public');
});

it('returns the configurable public shell without leaking inactive content', function () {
    SiteSetting::factory()->create([
        'site_name' => 'Kokkonios Studio',
        'utility_center_text' => 'Fine paints and tools',
        'footer_heading' => 'Built for careful work',
    ]);

    $parent = NavigationItem::factory()->create([
        'location' => NavigationLocation::Header,
        'label' => 'Products',
        'destination' => NavigationDestination::Catalogue,
        'sort_order' => 10,
    ]);
    NavigationItem::factory()->for($parent, 'parent')->create([
        'location' => NavigationLocation::Header,
        'label' => 'All products',
        'destination' => NavigationDestination::Catalogue,
    ]);
    NavigationItem::factory()->create([
        'location' => NavigationLocation::Header,
        'label' => 'Hidden',
        'is_active' => false,
    ]);
    NavigationItem::factory()->create([
        'location' => NavigationLocation::Footer,
        'label' => 'Home',
        'destination' => NavigationDestination::Home,
    ]);

    HeroSlide::factory()->create([
        'key' => 'painting',
        'title' => 'Colour, built to last',
        'is_active' => true,
    ]);
    HeroSlide::factory()->create(['key' => 'hidden', 'is_active' => false]);

    $this->getJson('/api/v1/storefront')
        ->assertOk()
        ->assertJsonPath('data.site.name', 'Kokkonios Studio')
        ->assertJsonPath('data.site.logo', null)
        ->assertJsonPath('data.navigation.header.0.label', 'Products')
        ->assertJsonPath('data.navigation.header.0.url', '/products')
        ->assertJsonPath('data.navigation.header.0.preview_image', null)
        ->assertJsonPath('data.navigation.header.0.children.0.label', 'All products')
        ->assertJsonCount(1, 'data.navigation.header')
        ->assertJsonCount(1, 'data.navigation.footer')
        ->assertJsonCount(1, 'data.hero_slides')
        ->assertJsonPath('data.hero_slides.0.title', 'Colour, built to last')
        ->assertJsonPath('data.footer.heading', 'Built for careful work');
});

it('exposes an optimized submenu preview image through the storefront resource', function () {
    $parent = NavigationItem::factory()->create([
        'location' => NavigationLocation::Header,
        'label' => 'Paints',
    ]);
    $child = NavigationItem::factory()->for($parent, 'parent')->create([
        'location' => NavigationLocation::Header,
        'label' => 'Interior paints',
        'preview_image_alt' => 'Paint tins and colour cards in a workshop',
    ]);

    $media = $child->addMedia(UploadedFile::fake()->image('interior-paints.jpg', 1600, 1000))
        ->toMediaCollection('menu_preview', 'public');

    $conversions = ConversionCollection::createForMedia($media)->getConversions('menu_preview');

    expect($conversions->map->getName()->all())
        ->toBe(['menu-preview'])
        ->and($conversions->first()->shouldBeQueued())
        ->toBeTrue();

    $this->getJson('/api/v1/storefront')
        ->assertOk()
        ->assertJsonPath('data.navigation.header.0.children.0.preview_image.original', $media->getUrl())
        ->assertJsonPath('data.navigation.header.0.children.0.preview_image.alt', 'Paint tins and colour cards in a workshop')
        ->assertJsonStructure([
            'data' => [
                'navigation' => [
                    'header' => [[
                        'children' => [[
                            'preview_image' => ['url', 'original', 'alt'],
                        ]],
                    ]],
                ],
            ],
        ]);
});

it('invalidates the configuration cache when storefront media is added or deleted', function (string $modelClass, string $collection) {
    $model = $modelClass::factory()->create();

    Cache::put('storefront.configuration.v1', ['cached' => true], 60);

    $model->addMedia(UploadedFile::fake()->image("{$collection}.jpg"))
        ->toMediaCollection($collection, 'public');

    expect(Cache::has('storefront.configuration.v1'))->toBeFalse();

    Cache::put('storefront.configuration.v1', ['cached' => true], 60);
    $model->clearMediaCollection($collection);

    expect(Cache::has('storefront.configuration.v1'))->toBeFalse();
})->with([
    'brand logo' => [SiteSetting::class, 'brand_logo'],
    'menu preview' => [NavigationItem::class, 'menu_preview'],
    'hero image' => [HeroSlide::class, 'image'],
]);

it('reflects a brand logo replacement without saving the site setting', function () {
    $settings = SiteSetting::factory()->create(['site_name' => 'Kokkonios']);
    $original = $settings->addMedia(UploadedFile::fake()->image('original-logo.png'))
        ->toMediaCollection('brand_logo', 'public');

    $this->getJson('/api/v1/storefront')
        ->assertOk()
        ->assertJsonPath('data.site.logo.original', $original->getUrl());

    $replacement = $settings->addMedia(UploadedFile::fake()->image('replacement-logo.png'))
        ->toMediaCollection('brand_logo', 'public');

    $this->getJson('/api/v1/storefront')
        ->assertOk()
        ->assertJsonPath('data.site.logo.original', $replacement->getUrl());
});

it('uses a safe Kokkonios fallback before settings are configured', function () {
    $this->getJson('/api/v1/storefront')
        ->assertOk()
        ->assertJsonPath('data.site.name', 'Kokkonios')
        ->assertJsonPath('data.site.logo', null)
        ->assertJsonCount(0, 'data.navigation.header')
        ->assertJsonCount(0, 'data.hero_slides');
});
