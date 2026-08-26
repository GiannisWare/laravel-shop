<?php

use App\Http\Controllers\Api\CatalogueFilterController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/storefront', StorefrontController::class)
        ->middleware('throttle:120,1')
        ->name('api.v1.storefront');
    Route::get('/catalogue/filters', CatalogueFilterController::class)
        ->middleware('throttle:120,1')
        ->name('api.v1.catalogue.filters');
    Route::get('/products', ProductController::class)
        ->middleware('throttle:120,1')
        ->name('api.v1.products.index');
});
