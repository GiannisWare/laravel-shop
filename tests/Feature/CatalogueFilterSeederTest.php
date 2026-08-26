<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Database\Seeders\CatalogueFilterSeeder;
use Illuminate\Support\Facades\DB;

it('seeds idempotent catalogue facets and assigns matching values to published products', function () {
    $paints = Category::factory()->create(['name' => 'Paints', 'slug' => 'paints']);
    $brand = Brand::factory()->create();
    $product = Product::factory()->for($paints)->for($brand)->create([
        'name' => 'Watercolour Permanent Red 15ml',
        'slug' => 'watercolour-permanent-red-15ml',
    ]);

    $this->seed(CatalogueFilterSeeder::class);
    $this->seed(CatalogueFilterSeeder::class);

    expect(DB::table('attributes')->whereIn('slug', ['medium', 'surface', 'size', 'colour-family'])->count())->toBe(4)
        ->and(DB::table('attribute_values')->count())->toBe(27)
        ->and($product->attributeValues()->count())->toBe(4);

    $this->assertDatabaseHas('attribute_value_product', [
        'product_id' => $product->getKey(),
        'attribute_value_id' => DB::table('attribute_values')->where('slug', 'watercolour')->value('id'),
    ]);

    $this->getJson('/api/v1/catalogue/filters')
        ->assertOk()
        ->assertJsonPath('data.attributes.0.name', 'Medium')
        ->assertJsonPath('data.attributes.0.values.0.value', 'Watercolour')
        ->assertJsonPath('data.attributes.1.name', 'Surface')
        ->assertJsonPath('data.attributes.2.name', 'Size')
        ->assertJsonPath('data.attributes.3.name', 'Colour Family')
        ->assertJsonPath('data.attributes.3.values.0.value', 'Red')
        ->assertJsonPath('data.attributes.3.values.0.swatch_hex', '#D71920');
});
