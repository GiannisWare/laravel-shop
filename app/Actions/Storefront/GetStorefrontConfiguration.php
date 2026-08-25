<?php

namespace App\Actions\Storefront;

use App\Enums\NavigationLocation;
use App\Models\HeroSlide;
use App\Models\NavigationItem;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

final class GetStorefrontConfiguration
{
    /**
     * @return array{settings: SiteSetting, navigation: array<string, mixed>, slides: mixed}
     */
    public function handle(): array
    {
        return Cache::remember('storefront.configuration.v1', now()->addSeconds(60), function (): array {
            $settings = SiteSetting::query()->with('media')->first() ?? new SiteSetting([
                'site_name' => 'Kokkonios',
                'footer_heading' => 'Tools for work that lasts',
                'footer_description' => 'Professional paints and tools for careful, lasting work.',
                'copyright_text' => '© '.date('Y').' Kokkonios. All rights reserved.',
            ]);

            $navigation = [];
            foreach (NavigationLocation::cases() as $location) {
                $navigation[$location->value] = NavigationItem::query()
                    ->where('location', $location)
                    ->whereNull('parent_id')
                    ->where('is_active', true)
                    ->with([
                        'category:id,slug',
                        'media',
                        'children' => fn ($query) => $query
                            ->where('is_active', true)
                            ->with(['category:id,slug', 'media'])
                            ->orderBy('sort_order'),
                    ])
                    ->orderBy('sort_order')
                    ->get();
            }

            return [
                'settings' => $settings,
                'navigation' => $navigation,
                'slides' => HeroSlide::query()
                    ->publiclyVisible()
                    ->with('media')
                    ->limit(6)
                    ->get(),
            ];
        });
    }
}
