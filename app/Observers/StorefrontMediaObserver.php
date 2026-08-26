<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\HeroSlide;
use App\Models\NavigationItem;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class StorefrontMediaObserver
{
    public function created(Media $media): void
    {
        $this->forgetConfigurationForStorefrontMedia($media);
    }

    public function deleted(Media $media): void
    {
        $this->forgetConfigurationForStorefrontMedia($media);
    }

    private function forgetConfigurationForStorefrontMedia(Media $media): void
    {
        $isStorefrontMedia = match ($media->model_type) {
            SiteSetting::class => $media->collection_name === 'brand_logo',
            NavigationItem::class => $media->collection_name === 'menu_preview',
            HeroSlide::class => $media->collection_name === 'image',
            default => false,
        };

        if ($isStorefrontMedia) {
            Cache::forget('storefront.configuration.v1');
        }
    }
}
