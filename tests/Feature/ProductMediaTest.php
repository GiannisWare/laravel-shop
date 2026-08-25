<?php

use App\Actions\Products\UpdateProductImageAltText;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Conversions\ConversionCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Storage::fake('public');
    Queue::fake();
});

it('registers the ordered product image collection and queued storefront conversions', function () {
    $product = Product::factory()->create();

    $first = $product->addMedia(UploadedFile::fake()->image('first.jpg', 320, 240))
        ->withCustomProperties(['alt' => 'First product angle'])
        ->toMediaCollection('images', 'public');
    $second = $product->addMedia(UploadedFile::fake()->image('second.png', 240, 240))
        ->withCustomProperties(['alt' => 'Second product angle'])
        ->toMediaCollection('images', 'public');

    Media::setNewOrder([$second->id, $first->id]);

    $conversions = ConversionCollection::createForMedia($first)->getConversions('images');

    expect($conversions->map->getName()->all())
        ->toBe(['thumb', 'card', 'medium', 'large'])
        ->and($conversions->every(fn ($conversion): bool => $conversion->shouldBeQueued()))
        ->toBeTrue();

    $response = $this->getJson('/api/v1/products');

    $response
        ->assertOk()
        ->assertJsonPath('data.0.images.0.id', $second->id)
        ->assertJsonPath('data.0.images.0.alt', 'Second product angle')
        ->assertJsonPath('data.0.images.1.id', $first->id)
        ->assertJsonPath('data.0.image.id', $second->id)
        ->assertJsonStructure([
            'data' => [[
                'images' => [[
                    'id',
                    'alt',
                    'original',
                    'thumb',
                    'card',
                    'medium',
                    'large',
                ]],
            ]],
        ]);
});

it('updates alt text only for media owned by the selected product', function () {
    $product = Product::factory()->create();
    $otherProduct = Product::factory()->create();

    $ownedMedia = $product->addMedia(UploadedFile::fake()->image('owned.jpg'))
        ->withCustomProperties(['alt' => 'Old alt'])
        ->toMediaCollection('images', 'public');
    $foreignMedia = $otherProduct->addMedia(UploadedFile::fake()->image('foreign.jpg'))
        ->withCustomProperties(['alt' => 'Foreign alt'])
        ->toMediaCollection('images', 'public');

    app(UpdateProductImageAltText::class)->handle($product, [
        ['id' => $ownedMedia->id, 'alt' => '  Updated storefront alt  '],
        ['id' => $foreignMedia->id, 'alt' => 'Must not change'],
    ]);

    expect($ownedMedia->refresh()->getCustomProperty('alt'))->toBe('Updated storefront alt')
        ->and($foreignMedia->refresh()->getCustomProperty('alt'))->toBe('Foreign alt');
});
