<?php

namespace Database\Factories;

use App\Enums\NavigationDestination;
use App\Enums\NavigationLocation;
use App\Models\NavigationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NavigationItem>
 */
class NavigationItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'category_id' => null,
            'location' => NavigationLocation::Header,
            'label' => fake()->unique()->words(2, true),
            'destination' => NavigationDestination::Catalogue,
            'url' => null,
            'preview_image_alt' => null,
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
            'open_in_new_tab' => false,
        ];
    }
}
