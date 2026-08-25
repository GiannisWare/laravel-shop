<?php

namespace App\Models;

use App\Enums\ProductAvailability;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** @property ProductAvailability $availability */
class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'source_id',
        'barcode',
        'category_id',
        'brand_id',
        'name',
        'slug',
        'short_description',
        'description',
        'availability',
        'is_published',
        'discontinued_at',
    ];

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return BelongsToMany<AttributeValue, $this> */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class)->withTimestamps();
    }

    /** @return HasMany<Favorite, $this> */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /** @return HasMany<ProductHold, $this> */
    public function holds(): HasMany
    {
        return $this->hasMany(ProductHold::class);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where('availability', '!=', ProductAvailability::Archived->value);
    }

    public static function isValidEan13(?string $barcode): bool
    {
        if ($barcode === null || preg_match('/^\d{13}$/', $barcode) !== 1) {
            return false;
        }

        $sum = 0;

        for ($index = 0; $index < 12; $index++) {
            $sum += (int) $barcode[$index] * ($index % 2 === 0 ? 1 : 3);
        }

        return (10 - ($sum % 10)) % 10 === (int) $barcode[12];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->useDisk((string) config('media-library.disk_name', 'public'))
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/avif',
            ]);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        foreach ([
            'thumb' => 160,
            'card' => 480,
            'medium' => 900,
            'large' => 1600,
        ] as $name => $size) {
            $this->addMediaConversion($name)
                ->performOnCollections('images')
                ->queued()
                ->fit(Fit::Max, $size, $size)
                ->format('webp')
                ->quality(90);
        }
    }

    protected static function booted(): void
    {
        static::saved(fn (): bool => Cache::forget('storefront.catalogue-filters.v1'));
        static::deleted(fn (): bool => Cache::forget('storefront.catalogue-filters.v1'));
        static::restored(fn (): bool => Cache::forget('storefront.catalogue-filters.v1'));
    }

    protected function casts(): array
    {
        return [
            'availability' => ProductAvailability::class,
            'is_published' => 'boolean',
            'discontinued_at' => 'datetime',
        ];
    }
}
