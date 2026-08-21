<?php

namespace App\Models;

use App\Enums\ProductHoldStatus;
use Database\Factories\ProductHoldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductHold extends Model
{
    /** @use HasFactory<ProductHoldFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'status',
        'note',
        'expires_at',
        'fulfilled_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return [
            'status' => ProductHoldStatus::class,
            'expires_at' => 'datetime',
            'fulfilled_at' => 'datetime',
        ];
    }
}
