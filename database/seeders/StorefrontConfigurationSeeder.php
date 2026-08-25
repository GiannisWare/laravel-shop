<?php

namespace Database\Seeders;

use App\Enums\NavigationDestination;
use App\Enums\NavigationLocation;
use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\NavigationItem;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class StorefrontConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SiteSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'site_name' => 'Kokkonios',
                'utility_left_text' => 'Professional paints & tools',
                'utility_center_text' => 'Kokkonios — Fine paints & art supplies',
                'utility_right_text' => 'View-only catalogue',
                'footer_heading' => 'Tools for work that lasts',
                'footer_description' => 'Professional paints, construction equipment and decorating tools for careful, lasting work.',
                'copyright_text' => '© '.date('Y').' Kokkonios. All rights reserved.',
            ],
        );

        $paints = Category::query()->firstOrCreate(
            ['slug' => 'paints'],
            ['name' => 'Paints', 'description' => 'Professional paints and colour systems.', 'sort_order' => 10, 'is_active' => true],
        );
        $constructionTools = Category::query()->firstOrCreate(
            ['slug' => 'construction-tools'],
            ['name' => 'Construction Tools', 'description' => 'Tools and equipment for building and preparation.', 'sort_order' => 20, 'is_active' => true],
        );

        $items = [
            ['Home', NavigationDestination::Home, null, 10],
            ['All products', NavigationDestination::Catalogue, null, 20],
            ['Paints', NavigationDestination::Category, $paints->getKey(), 30],
            ['Construction tools', NavigationDestination::Category, $constructionTools->getKey(), 40],
        ];

        foreach ([NavigationLocation::Header, NavigationLocation::Footer] as $location) {
            foreach ($items as [$label, $destination, $categoryId, $sortOrder]) {
                NavigationItem::query()->updateOrCreate(
                    ['location' => $location, 'label' => $label],
                    [
                        'parent_id' => null,
                        'category_id' => $categoryId,
                        'destination' => $destination,
                        'url' => null,
                        'sort_order' => $sortOrder,
                        'is_active' => true,
                        'open_in_new_tab' => false,
                    ],
                );
            }
        }

        $slides = [
            [
                'key' => 'painting-supplies', 'eyebrow' => 'Paint & colour', 'title' => 'Colour, built to last',
                'description' => 'Professional paint systems and tools for confident, consistent finishes.',
                'cta_label' => 'Explore paints', 'cta_url' => '/products?category=paints',
                'image_alt' => 'Blue paint tin and brushes arranged in a bright working studio.',
                'image' => 'painting-campaign.png', 'sort_order' => 10,
            ],
            [
                'key' => 'construction-tools', 'eyebrow' => 'Construction tools', 'title' => 'Measure. Build. Refine.',
                'description' => 'Dependable construction tools for preparation, installation and repair.',
                'cta_label' => 'Explore construction tools', 'cta_url' => '/products?category=construction-tools',
                'image_alt' => 'Construction hand tools arranged safely on a clean timber workbench.',
                'image' => 'construction-tools-campaign.png', 'sort_order' => 20,
            ],
            [
                'key' => 'decorating-tools', 'eyebrow' => 'Surface finishing', 'title' => 'Precision in every layer',
                'description' => 'Rollers, tapes and finishing tools chosen for clean, controlled results.',
                'cta_label' => 'Browse all products', 'cta_url' => '/products',
                'image_alt' => 'Roller, masking tape and finishing tools in a softly lit interior.',
                'image' => 'decorating-tools-campaign.png', 'sort_order' => 30,
            ],
        ];

        foreach ($slides as $slideData) {
            $image = $slideData['image'];
            unset($slideData['image']);

            $slide = HeroSlide::query()->updateOrCreate(
                ['key' => $slideData['key']],
                $slideData + ['is_active' => true, 'focal_position' => 'center'],
            );

            if (! $slide->hasMedia('image')) {
                $slide->addMedia(resource_path('images/storefront/'.$image))->toMediaCollection('image');
            }
        }
    }
}
