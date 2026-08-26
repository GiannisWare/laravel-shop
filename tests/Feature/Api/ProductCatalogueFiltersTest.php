<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

beforeEach(fn () => Cache::flush());

it('filters products by parent category brand search and attributes', function () {
    $paints = Category::factory()->create(['name' => 'Paints', 'slug' => 'paints']);
    $acrylics = Category::factory()->for($paints, 'parent')->create(['name' => 'Acrylics', 'slug' => 'acrylics']);
    $tools = Category::factory()->create(['name' => 'Tools', 'slug' => 'tools']);
    $brand = Brand::factory()->create(['name' => 'Vivechrom', 'slug' => 'vivechrom']);

    $colour = Attribute::factory()->create(['name' => 'Colour', 'slug' => 'colour']);
    $finish = Attribute::factory()->create(['name' => 'Finish', 'slug' => 'finish']);
    $blue = AttributeValue::factory()->for($colour)->create(['value' => 'Blue', 'slug' => 'blue']);
    $matt = AttributeValue::factory()->for($finish)->create(['value' => 'Matt', 'slug' => 'matt']);

    $matching = Product::factory()->for($acrylics, 'category')->for($brand)->create([
        'name' => 'Blue Interior Paint',
        'slug' => 'blue-interior-paint',
    ]);
    $matching->attributeValues()->attach([$blue->id, $matt->id]);

    Product::factory()->for($tools, 'category')->for($brand)->create(['name' => 'Blue Drill']);
    Product::factory()->for($acrylics, 'category')->unpublished()->create(['name' => 'Blue Draft Paint']);

    $query = http_build_query([
        'q' => 'Blue',
        'category' => 'paints',
        'brand' => 'vivechrom',
        'attributes' => ['colour:blue', 'finish:matt'],
    ]);

    $this->getJson('/api/v1/products?'.$query)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matching->id);
});

it('returns no products when a requested attribute group has no filterable value', function () {
    $category = Category::factory()->create();
    $colour = Attribute::factory()->create(['name' => 'Colour', 'slug' => 'colour']);
    $blue = AttributeValue::factory()->for($colour)->create(['value' => 'Blue', 'slug' => 'blue']);
    $product = Product::factory()->for($category)->create();
    $product->attributeValues()->attach($blue);

    $query = http_build_query([
        'attributes' => ['colour:blue', 'finish:missing'],
    ]);

    $this->getJson('/api/v1/products?'.$query)
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns cached filter metadata only for public products', function () {
    $category = Category::factory()->create(['name' => 'Paints', 'slug' => 'paints']);
    $brand = Brand::factory()->create(['name' => 'Vivechrom', 'slug' => 'vivechrom']);
    $attribute = Attribute::factory()->create(['name' => 'Colour', 'slug' => 'colour']);
    $blue = AttributeValue::factory()->for($attribute)->create(['value' => 'Blue', 'slug' => 'blue', 'swatch_hex' => '#1646a0']);

    $product = Product::factory()->for($category)->for($brand)->create();
    $product->attributeValues()->attach($blue);
    Product::factory()->for($category)->unpublished()->create();

    $this->getJson('/api/v1/catalogue/filters')
        ->assertOk()
        ->assertJsonPath('data.categories.0.slug', 'paints')
        ->assertJsonPath('data.categories.0.products_count', 1)
        ->assertJsonPath('data.brands.0.slug', 'vivechrom')
        ->assertJsonPath('data.attributes.0.values.0.slug', 'blue')
        ->assertJsonPath('data.attributes.0.values.0.swatch_hex', '#1646a0')
        ->assertJsonPath('data.sorts.0.value', 'recommended');
});

it('rejects malformed attribute filter tokens', function () {
    $this->getJson('/api/v1/products?attributes[]=not-valid')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('attributes.0');
});

it('returns brands attributes and values in the configured admin order', function () {
    $category = Category::factory()->create();
    $laterBrand = Brand::factory()->create(['name' => 'Later', 'slug' => 'later', 'sort_order' => 20]);
    $firstBrand = Brand::factory()->create(['name' => 'First', 'slug' => 'first', 'sort_order' => 10]);
    $laterAttribute = Attribute::factory()->create(['name' => 'Later facet', 'slug' => 'later-facet', 'sort_order' => 20]);
    $firstAttribute = Attribute::factory()->create(['name' => 'First facet', 'slug' => 'first-facet', 'sort_order' => 10]);
    $laterValue = AttributeValue::factory()->for($firstAttribute)->create(['value' => 'Later value', 'slug' => 'later-value', 'sort_order' => 20]);
    $firstValue = AttributeValue::factory()->for($firstAttribute)->create(['value' => 'First value', 'slug' => 'first-value', 'sort_order' => 10]);
    $otherValue = AttributeValue::factory()->for($laterAttribute)->create(['sort_order' => 10]);

    $firstProduct = Product::factory()->for($category)->for($firstBrand)->create();
    $firstProduct->attributeValues()->attach([$laterValue->id, $firstValue->id, $otherValue->id]);
    Product::factory()->for($category)->for($laterBrand)->create()->attributeValues()->attach($otherValue);

    $this->getJson('/api/v1/catalogue/filters')
        ->assertOk()
        ->assertJsonPath('data.brands.0.slug', 'first')
        ->assertJsonPath('data.brands.1.slug', 'later')
        ->assertJsonPath('data.attributes.0.slug', 'first-facet')
        ->assertJsonPath('data.attributes.0.values.0.slug', 'first-value')
        ->assertJsonPath('data.attributes.0.values.1.slug', 'later-value')
        ->assertJsonPath('data.attributes.1.slug', 'later-facet');
});
