<?php

use App\Actions\Products\ImportProductsFromSpreadsheet;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function productImportSpreadsheet(array $rows, bool $withImportNotes = false): string
{
    $path = sys_get_temp_dir().'/product-import-'.Str::uuid().'.xlsx';
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()
        ->setTitle('Products')
        ->fromArray([
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

    if ($withImportNotes) {
        $spreadsheet->createSheet()
            ->setTitle('Import Notes')
            ->fromArray([
                ['Column', 'Required?', 'Notes'],
                ['source_id', 'Required (or barcode)', 'Internal reference'],
                ['discontinued_at', 'Optional', 'Leave blank unless archived'],
            ]);
    }

    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return $path;
}

function productImportCsv(array $rows): string
{
    $path = sys_get_temp_dir().'/product-import-'.Str::uuid().'.csv';
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

    (new Csv($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return $path;
}

it('imports only the first worksheet and accepts a blank discontinued date', function () {
    $category = Category::factory()->create(['slug' => 'watercolors']);
    $brand = Brand::factory()->create(['slug' => 'schmincke']);
    $path = productImportSpreadsheet([
        ['SCH-1001', '5000000020003', $category->slug, $brand->slug, 'Horadam Watercolour', 'horadam-watercolour', null, null, 'in_stock', true, null],
    ], withImportNotes: true);

    try {
        $result = app(ImportProductsFromSpreadsheet::class)->handle($path);
    } finally {
        unlink($path);
    }

    expect($result->created)->toBe(1)
        ->and($result->failures)->toBeEmpty()
        ->and(Product::query()->where('source_id', 'SCH-1001')->value('discontinued_at'))->toBeNull();
});

it('imports a Livewire temporary upload from its configured filesystem disk', function () {
    Storage::fake('tmp-for-tests');
    $path = productImportSpreadsheet([
        ['REMOTE-001', '4006381333931', null, null, 'Remote product', 'remote-product', null, null, 'in_stock', true, null],
    ]);
    $filename = 'remote-products.xlsx';
    Storage::disk('tmp-for-tests')->put("livewire-tmp/{$filename}", file_get_contents($path));
    unlink($path);

    $file = new class($filename, 'tmp-for-tests') extends TemporaryUploadedFile
    {
        public function getRealPath(): string
        {
            return $this->path;
        }
    };

    $result = app(ImportProductsFromSpreadsheet::class)->handle($file);

    expect($result->created)->toBe(1)
        ->and($result->failures)->toBeEmpty()
        ->and(Product::query()->where('source_id', 'REMOTE-001')->exists())->toBeTrue();
});

it('continues to import csv files', function () {
    $path = productImportCsv([
        ['CSV-001', '4006381333931', null, null, 'CSV product', 'csv-product', null, null, 'in_stock', true, null],
    ]);

    try {
        $result = app(ImportProductsFromSpreadsheet::class)->handle($path);
    } finally {
        unlink($path);
    }

    expect($result->created)->toBe(1)
        ->and($result->failures)->toBeEmpty()
        ->and(Product::query()->where('source_id', 'CSV-001')->exists())->toBeTrue();
});

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
