<?php

use App\Enums\ProductAvailability;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

it('returns only public catalogue products through the versioned resource contract', function () {
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();
    $published = Product::factory()->for($brand)->for($category)->create([
        'name' => 'Visible Paint',
        'slug' => 'visible-paint',
    ]);

    Product::factory()->unpublished()->create(['name' => 'Draft Product']);
    Product::factory()->create([
        'name' => 'Archived Product',
        'availability' => ProductAvailability::Archived,
    ]);
    Product::factory()->create(['name' => 'Deleted Product'])->delete();

    $response = $this->getJson('/api/v1/products');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $published->id)
        ->assertJsonPath('data.0.name', 'Visible Paint')
        ->assertJsonPath('data.0.slug', 'visible-paint')
        ->assertJsonPath('data.0.brand.name', $brand->name)
        ->assertJsonPath('data.0.category.name', $category->name)
        ->assertJsonPath('data.0.availability.value', 'in_stock')
        ->assertJsonPath('data.0.image', null)
        ->assertJsonMissingPath('data.0.barcode')
        ->assertJsonMissingPath('data.0.deleted_at')
        ->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total'],
        ]);
});

it('rejects an unbounded page size', function () {
    $this->getJson('/api/v1/products?per_page=1000')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('per_page');
});
