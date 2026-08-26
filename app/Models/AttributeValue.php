<?php

namespace App\Models;

use Database\Factories\AttributeValueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class AttributeValue extends Model
{
    /** @use HasFactory<AttributeValueFactory> */
    use HasFactory;

    protected $fillable = [
        'attribute_id',
        'value',
        'slug',
        'swatch_hex',
        'sort_order',
    ];

    /** @return BelongsTo<Attribute, $this> */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }

    protected static function booted(): void
    {
        static::creating(function (AttributeValue $attributeValue): void {
            if (! $attributeValue->isDirty('sort_order')) {
                $attributeValue->setAttribute('sort_order', ((int) static::query()
                    ->where('attribute_id', $attributeValue->attribute_id)
                    ->max('sort_order')) + 10);
            }
        });

        static::saved(fn (): bool => Cache::forget('storefront.catalogue-filters.v1'));
        static::deleted(fn (): bool => Cache::forget('storefront.catalogue-filters.v1'));
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
