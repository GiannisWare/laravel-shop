<?php

namespace App\Models;

use Database\Factories\AttributeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Attribute extends Model
{
    /** @use HasFactory<AttributeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_filterable',
        'sort_order',
    ];

    /** @return HasMany<AttributeValue, $this> */
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order')->orderBy('id');
    }

    protected static function booted(): void
    {
        static::creating(function (Attribute $attribute): void {
            if (! $attribute->isDirty('sort_order')) {
                $attribute->setAttribute('sort_order', ((int) static::query()->max('sort_order')) + 10);
            }
        });

        static::saved(fn (): bool => Cache::forget('storefront.catalogue-filters.v1'));
        static::deleted(fn (): bool => Cache::forget('storefront.catalogue-filters.v1'));
    }

    protected function casts(): array
    {
        return [
            'is_filterable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
