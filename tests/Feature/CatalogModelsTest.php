<?php

use App\Enums\ProductAvailability;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;

it('validates EAN-13 barcodes with their checksum', function () {
    expect(Product::isValidEan13('4006381333931'))->toBeTrue()
        ->and(Product::isValidEan13('4006381333932'))->toBeFalse()
        ->and(Product::isValidEan13('123'))->toBeFalse()
        ->and(Product::isValidEan13(null))->toBeFalse();
});

it('supports nested categories and EAV product attributes', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);
    $attribute = Attribute::factory()->create();
    $value = AttributeValue::factory()->for($attribute)->create();
    $product = Product::factory()->for($child)->create();

    $product->attributeValues()->attach($value);

    expect($child->parent->is($parent))->toBeTrue()
        ->and($parent->children)->toHaveCount(1)
        ->and($product->attributeValues->first()->is($value))->toBeTrue();
});

it('only exposes published non-archived products through the published scope', function () {
    $published = Product::factory()->create();
    Product::factory()->unpublished()->create();
    Product::factory()->create(['availability' => ProductAvailability::Archived]);

    expect(Product::query()->published()->pluck('id')->all())->toBe([$published->id]);
});

it('soft deletes products without removing their archival record', function () {
    $product = Product::factory()->create();

    $product->delete();

    expect(Product::query()->find($product->id))->toBeNull()
        ->and(Product::withTrashed()->find($product->id))->not->toBeNull();
});
