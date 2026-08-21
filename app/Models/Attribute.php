<?php

namespace App\Models;

use Database\Factories\AttributeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $this->hasMany(AttributeValue::class)->orderBy('value');
    }

    protected function casts(): array
    {
        return [
            'is_filterable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
