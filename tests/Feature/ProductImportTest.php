<?php

use App\Actions\Products\ImportProductsFromSpreadsheet;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function productImportSpreadsheet(array $rows): string
{
    $path = sys_get_temp_dir().'/product-import-'.Str::uuid().'.xlsx';
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray([
        [
            'source_id',
            'barcode',
            'category_slug',
            'brand_slug',
            'name',
            'slug',
            'short_description',
            'description',
            'availability',
            'is_published',
            'discontinued_at',
        ],
        ...$rows,
    ]);
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return $path;
}

it('imports valid rows and reports invalid category or brand references', function () {
    $category = Category::factory()->create(['slug' => 'acrylic-paints']);
    $brand = Brand::factory()->create(['slug' => 'golden']);
    $path = productImportSpreadsheet([
        ['SKU-001', '4006381333931', $category->slug, $brand->slug, 'Heavy Body Acrylic', 'heavy-body-acrylic', 'Acrylic paint', 'Full description', 'in_stock', 'yes', null],
        ['SKU-002', '5901234123457', $category->slug, 'missing-brand', 'Fluid Acrylic', 'fluid-acrylic', null, null, 'in_stock', 'yes', null],
    ]);

    try {
        $result = app(ImportProductsFromSpreadsheet::class)->handle($path);
    } finally {
        unlink($path);
    }

    expect($result->created)->toBe(1)
        ->and($result->updated)->toBe(0)
        ->and($result->failures)->not->toBeEmpty()
        ->and($result->failures[0]['row'])->toBe(3)
        ->and(Product::query()->where('source_id', 'SKU-001')->exists())->toBeTrue()
        ->and(Product::query()->where('source_id', 'SKU-002')->exists())->toBeFalse();
});

it('updates by source ID before falling back to barcode', function () {
    $category = Category::factory()->create(['slug' => 'brushes']);
    $brand = Brand::factory()->create(['slug' => 'da-vinci']);
    $product = Product::factory()->for($category)->for($brand)->create([
        'source_id' => 'SKU-100',
        'barcode' => '4006381333931',
        'slug' => 'old-brush',
        'name' => 'Old brush',
    ]);
    $path = productImportSpreadsheet([
        ['SKU-100', '5901234123457', $category->slug, $brand->slug, 'Updated brush', 'updated-brush', null, null, 'low_stock', 'no', null],
    ]);

    try {
        $result = app(ImportProductsFromSpreadsheet::class)->handle($path);
    } finally {
        unlink($path);
    }

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(1)
        ->and($result->failures)->toBeEmpty()
        ->and($product->fresh()->name)->toBe('Updated brush')
        ->and($product->fresh()->barcode)->toBe('5901234123457')
        ->and($product->fresh()->is_published)->toBeFalse();
});

it('updates an existing product by barcode when source ID is absent', function () {
    $product = Product::factory()->create([
        'source_id' => null,
        'barcode' => '4006381333931',
        'slug' => 'original-product',
    ]);
    $path = productImportSpreadsheet([
        [null, '4006381333931', null, null, 'Renamed product', 'renamed-product', null, null, 'in_stock', true, null],
    ]);

    try {
        $result = app(ImportProductsFromSpreadsheet::class)->handle($path);
    } finally {
        unlink($path);
    }

    expect($result->updated)->toBe(1)
        ->and(Product::query()->count())->toBe(1)
        ->and($product->fresh()->name)->toBe('Renamed product');
});

it('reports duplicate identities inside one spreadsheet without aborting valid rows', function () {
    $path = productImportSpreadsheet([
        ['SKU-200', '4006381333931', null, null, 'First product', 'first-product', null, null, 'in_stock', true, null],
        ['SKU-200', '5901234123457', null, null, 'Duplicate product', 'duplicate-product', null, null, 'in_stock', true, null],
        ['SKU-201', '5012345678900', null, null, 'Valid product', 'valid-product', null, null, 'in_stock', true, null],
    ]);

    try {
        $result = app(ImportProductsFromSpreadsheet::class)->handle($path);
    } finally {
        unlink($path);
    }

    expect($result->created)->toBe(2)
        ->and($result->failures)->toHaveCount(1)
        ->and(Product::query()->where('source_id', 'SKU-201')->exists())->toBeTrue();
});
