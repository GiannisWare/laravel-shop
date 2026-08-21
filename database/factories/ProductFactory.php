<?php

namespace Database\Factories;

use App\Enums\ProductAvailability;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word().' '.fake()->unique()->word().' '.fake()->unique()->word();

        return [
            'source_id' => fake()->unique()->numerify('SKU-######'),
            'barcode' => $this->ean13(),
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'availability' => ProductAvailability::InStock,
            'is_published' => true,
            'discontinued_at' => null,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }

    private function ean13(): string
    {
        $digits = fake()->unique()->numerify('############');
        $sum = 0;

        for ($index = 0; $index < 12; $index++) {
            $sum += (int) $digits[$index] * ($index % 2 === 0 ? 1 : 3);
        }

        return $digits.(10 - ($sum % 10)) % 10;
    }
}
