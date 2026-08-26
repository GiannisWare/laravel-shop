<?php

namespace App\Http\Controllers\Api;

use App\Actions\Storefront\GetStorefrontConfiguration;
use App\Http\Controllers\Controller;
use App\Http\Resources\StorefrontResource;

final class StorefrontController extends Controller
{
    public function __invoke(GetStorefrontConfiguration $getStorefrontConfiguration): StorefrontResource
    {
        return new StorefrontResource($getStorefrontConfiguration->handle());
    }
}
