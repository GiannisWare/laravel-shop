<?php

namespace Database\Factories;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AttributeValue>
 */
class AttributeValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $value = fake()->unique()->word().' '.fake()->unique()->word();

        return [
            'attribute_id' => Attribute::factory(),
            'value' => Str::title($value),
            'slug' => Str::slug($value),
            'swatch_hex' => fake()->optional()->hexColor(),
        ];
    }
}
