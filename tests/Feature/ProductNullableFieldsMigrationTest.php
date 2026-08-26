<?php

use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('preserves products and backfills nullable identity fields before rollback', function () {
    $barcodeDraft = Product::factory()->unpublished()->create([
        'barcode' => '4006381333931',
        'name' => null,
        'slug' => null,
    ]);
    $namedProduct = Product::factory()->unpublished()->create([
        'name' => 'Preserved Product',
        'slug' => null,
    ]);
    $sluggedProduct = Product::factory()->unpublished()->create([
        'name' => null,
        'slug' => 'preserved-product-slug',
    ]);

    $migration = require database_path('migrations/2026_08_22_103821_make_name_and_slug_nullable_on_products_table.php');
    $migration->down();

    expect(Product::query()->findOrFail($barcodeDraft->id))
        ->name->toBe('Draft product 4006381333931')
        ->slug->toBe("draft-product-4006381333931-{$barcodeDraft->id}")
        ->and(Product::query()->findOrFail($namedProduct->id))
        ->name->toBe('Preserved Product')
        ->slug->toBe("preserved-product-{$namedProduct->id}")
        ->and(Product::query()->findOrFail($sluggedProduct->id))
        ->name->toBe('Preserved Product Slug')
        ->slug->toBe('preserved-product-slug');

    expect(fn () => DB::table('products')->insert([
        'name' => null,
        'slug' => null,
    ]))->toThrow(QueryException::class);
});
