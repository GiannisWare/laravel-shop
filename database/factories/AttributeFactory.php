<?php

namespace Database\Factories;

use App\Models\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attribute>
 */
class AttributeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Color', 'Size', 'Material', 'Format', 'Viscosity']).' '.fake()->unique()->word();

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'is_filterable' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
