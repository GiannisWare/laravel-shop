<?php

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Brand extends Model implements HasMedia
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
    ];

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])
            ->singleFile();
    }

    protected static function booted(): void
    {
        static::creating(function (Brand $brand): void {
            if (! $brand->isDirty('sort_order')) {
                $brand->setAttribute('sort_order', ((int) static::query()->max('sort_order')) + 10);
            }
        });

        static::saved(fn (): bool => Cache::forget('storefront.catalogue-filters.v1'));
        static::deleted(fn (): bool => Cache::forget('storefront.catalogue-filters.v1'));
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
