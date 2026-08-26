<?php

namespace Database\Factories;

use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteSetting>
 */
class SiteSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_name' => 'Kokkonios',
            'utility_left_text' => 'Professional paints & tools',
            'utility_center_text' => 'Fine paints & art supplies',
            'utility_right_text' => 'View-only catalogue',
            'footer_heading' => 'Kokkonios',
            'footer_description' => 'Paint, construction and decorating supplies selected for professional work.',
            'copyright_text' => '© '.date('Y').' Kokkonios. All rights reserved.',
        ];
    }
}
