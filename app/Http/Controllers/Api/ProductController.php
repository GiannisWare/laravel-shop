<?php

namespace App\Http\Controllers\Api;

use App\Actions\Products\BuildProductCatalogueQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexProductsRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProductController extends Controller
{
    public function __invoke(
        IndexProductsRequest $request,
        BuildProductCatalogueQuery $buildProductCatalogueQuery,
    ): AnonymousResourceCollection {
        $products = $buildProductCatalogueQuery
            ->handle($request->validated())
            ->paginate($request->integer('per_page', 12))
            ->withQueryString();

        return ProductResource::collection($products);
    }
}
