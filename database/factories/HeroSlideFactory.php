<?php

namespace Database\Factories;

use App\Models\HeroSlide;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HeroSlide>
 */
class HeroSlideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'key' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'eyebrow' => fake()->words(2, true),
            'title' => $title,
            'description' => fake()->sentence(),
            'cta_label' => 'Explore products',
            'cta_url' => '/products',
            'image_alt' => fake()->sentence(6),
            'focal_position' => 'center',
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }
}
