<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\HeroSlideFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class HeroSlide extends Model implements HasMedia
{
    /** @use HasFactory<HeroSlideFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'key', 'eyebrow', 'title', 'description', 'cta_label', 'cta_url',
        'image_alt', 'focal_position', 'sort_order', 'is_active',
    ];

    /**
     * @param  Builder<HeroSlide>  $query
     * @return Builder<HeroSlide>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->useDisk((string) config('media-library.disk_name', 'public'))
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('hero')
            ->performOnCollections('image')
            ->queued()
            ->fit(Fit::Max, 1920, 1080)
            ->format('webp')
            ->quality(88);
    }

    protected static function booted(): void
    {
        static::creating(function (HeroSlide $heroSlide): void {
            if (! $heroSlide->isDirty('sort_order')) {
                $heroSlide->setAttribute('sort_order', ((int) static::query()->max('sort_order')) + 10);
            }
        });

        static::saved(fn (): bool => Cache::forget('storefront.configuration.v1'));
        static::deleted(fn (): bool => Cache::forget('storefront.configuration.v1'));
    }

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_active' => 'boolean'];
    }
}
