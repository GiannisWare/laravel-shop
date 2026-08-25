<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NavigationDestination;
use App\Enums\NavigationLocation;
use Database\Factories\NavigationItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** @property NavigationDestination $destination */
class NavigationItem extends Model implements HasMedia
{
    /** @use HasFactory<NavigationItemFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'parent_id', 'category_id', 'location', 'label', 'destination', 'url', 'preview_image_alt',
        'sort_order', 'is_active', 'open_in_new_tab',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('menu_preview')
            ->useDisk((string) config('media-library.disk_name', 'public'))
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('menu-preview')
            ->performOnCollections('menu_preview')
            ->queued()
            ->fit(Fit::Max, 1280, 800)
            ->format('webp')
            ->quality(86);
    }

    /** @return BelongsTo<NavigationItem, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<NavigationItem, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('label');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function resolvedUrl(): string
    {
        return match ($this->destination) {
            NavigationDestination::Home => '/',
            NavigationDestination::Catalogue => '/products',
            NavigationDestination::Category => $this->category === null
                ? '/products'
                : '/products?category='.rawurlencode($this->category->slug),
            NavigationDestination::InternalPath, NavigationDestination::ExternalUrl => (string) $this->url,
        };
    }

    protected static function booted(): void
    {
        static::creating(function (NavigationItem $navigationItem): void {
            if (! $navigationItem->isDirty('sort_order')) {
                $navigationItem->setAttribute('sort_order', ((int) static::query()
                    ->where('location', $navigationItem->location)
                    ->where('parent_id', $navigationItem->parent_id)
                    ->max('sort_order')) + 10);
            }
        });

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

    protected function casts(): array
    {
        return [
            'location' => NavigationLocation::class,
            'destination' => NavigationDestination::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'open_in_new_tab' => 'boolean',
        ];
    }
}
