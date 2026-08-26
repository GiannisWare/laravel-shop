<?php

namespace App\Http\Controllers\Api;

use App\Actions\Storefront\GetCatalogueFilters;
use App\Http\Controllers\Controller;
use App\Http\Resources\CatalogueFilterResource;

final class CatalogueFilterController extends Controller
{
    public function __invoke(GetCatalogueFilters $getCatalogueFilters): CatalogueFilterResource
    {
        return new CatalogueFilterResource($getCatalogueFilters->handle());
    }
}
