<?php

use App\Actions\Products\ScanProductBarcode;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates an unpublished barcode-only draft when the product does not exist', function () {
    $product = app(ScanProductBarcode::class)->handle('4006381333931');

    expect($product->wasRecentlyCreated)->toBeTrue()
        ->and($product->barcode)->toBe('4006381333931')
        ->and($product->name)->toBeNull()
        ->and($product->slug)->toBeNull()
        ->and($product->is_published)->toBeFalse();
});

it('returns an existing product instead of creating a duplicate', function () {
    $existing = Product::factory()->create(['barcode' => '4006381333931']);

    $product = app(ScanProductBarcode::class)->handle('4006381333931');

    expect($product->is($existing))->toBeTrue()
        ->and($product->wasRecentlyCreated)->toBeFalse()
        ->and(Product::query()->withTrashed()->count())->toBe(1);
});

it('finds a soft-deleted product instead of violating the unique barcode constraint', function () {
    $existing = Product::factory()->create(['barcode' => '4006381333931']);
    $existing->delete();

    $product = app(ScanProductBarcode::class)->handle('4006381333931');

    expect($product->is($existing))->toBeTrue()
        ->and($product->trashed())->toBeTrue()
        ->and(Product::query()->withTrashed()->count())->toBe(1);
});

it('rejects invalid EAN-13 values defensively', function () {
    app(ScanProductBarcode::class)->handle('1234567890123');
})->throws(InvalidArgumentException::class, 'valid EAN-13');
