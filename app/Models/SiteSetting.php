<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SiteSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SiteSetting extends Model implements HasMedia
{
    /** @use HasFactory<SiteSettingFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'site_name', 'utility_left_text', 'utility_center_text', 'utility_right_text',
        'footer_heading', 'footer_description', 'copyright_text',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('brand_logo')
            ->useDisk((string) config('media-library.disk_name', 'public'))
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/svg+xml'])
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('logo')
            ->performOnCollections('brand_logo')
            ->queued()
            ->fit(Fit::Max, 640, 240)
            ->format('webp')
            ->quality(90);
    }

    protected static function booted(): void
    {
        static::saved(fn (): bool => Cache::forget('storefront.configuration.v1'));
        static::deleted(fn (): bool => Cache::forget('storefront.configuration.v1'));

        static::registerMediaCollectionCallbacks();
    }

    protected static function registerMediaCollectionCallbacks(): void
    {
        $handler = fn (): bool => Cache::forget('storefront.configuration.v1');

        static::mediaSaved($handler);
        static::mediaDeleted($handler);
    }
}
