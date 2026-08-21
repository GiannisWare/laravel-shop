<?php

namespace Database\Factories;

use App\Enums\ProductHoldStatus;
use App\Models\Product;
use App\Models\ProductHold;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductHold>
 */
class ProductHoldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'status' => ProductHoldStatus::Requested,
            'note' => fake()->optional()->sentence(),
            'expires_at' => now()->addDays(2),
            'fulfilled_at' => null,
        ];
    }
}
