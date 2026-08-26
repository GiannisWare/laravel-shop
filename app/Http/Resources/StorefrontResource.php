<?php

namespace App\Http\Resources;

use App\Models\HeroSlide;
use App\Models\NavigationItem;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class StorefrontResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SiteSetting $settings */
        $settings = $this->resource['settings'];
        /** @var array<string, Collection<int, NavigationItem>> $navigation */
        $navigation = $this->resource['navigation'];

        return [
            'site' => [
                'name' => $settings->site_name ?: 'Kokkonios',
                'logo' => $this->logoPayload($settings),
                'utility_bar' => [
                    'left' => $settings->utility_left_text,
                    'center' => $settings->utility_center_text,
                    'right' => $settings->utility_right_text,
                ],
            ],
            'navigation' => collect($navigation)->map(
                fn (Collection $items): array => $items->map(fn (NavigationItem $item): array => $this->navigationPayload($item))->all(),
            )->all(),
            'hero_slides' => $this->resource['slides']
                ->map(fn (HeroSlide $slide): array => $this->slidePayload($slide))
                ->all(),
            'footer' => [
                'heading' => $settings->footer_heading,
                'description' => $settings->footer_description,
                'copyright' => $settings->copyright_text,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function navigationPayload(NavigationItem $item): array
    {
        return [
            'id' => $item->getKey(),
            'label' => $item->label,
            'url' => $item->resolvedUrl(),
            'open_in_new_tab' => $item->open_in_new_tab,
            'preview_image' => $this->navigationImagePayload($item),
            'children' => $item->relationLoaded('children')
                ? $item->children->map(fn (NavigationItem $child): array => $this->navigationPayload($child))->all()
                : [],
        ];
    }

    /** @return array{url: string, original: string, alt: string}|null */
    private function navigationImagePayload(NavigationItem $item): ?array
    {
        $media = $item->getFirstMedia('menu_preview');

        if ($media === null) {
            return null;
        }

        return [
            'url' => $this->conversionUrl($media, 'menu-preview'),
            'original' => $media->getUrl(),
            'alt' => trim((string) $item->preview_image_alt) ?: $item->label,
        ];
    }

    /** @return array<string, mixed> */
    private function slidePayload(HeroSlide $slide): array
    {
        $media = $slide->getFirstMedia('image');

        return [
            'id' => $slide->getKey(),
            'key' => $slide->key,
            'eyebrow' => $slide->eyebrow,
            'title' => $slide->title,
            'description' => $slide->description,
            'cta' => [
                'label' => $slide->cta_label,
                'url' => $slide->cta_url,
            ],
            'image' => $media === null ? null : [
                'url' => $this->conversionUrl($media, 'hero'),
                'original' => $media->getUrl(),
                'alt' => $slide->image_alt,
                'focal_position' => $slide->focal_position,
            ],
        ];
    }

    /** @return array{url: string, original: string, alt: string}|null */
    private function logoPayload(SiteSetting $settings): ?array
    {
        $media = $settings->getFirstMedia('brand_logo');

        if ($media === null) {
            return null;
        }

        return [
            'url' => $this->conversionUrl($media, 'logo'),
            'original' => $media->getUrl(),
            'alt' => $settings->site_name ?: 'Kokkonios',
        ];
    }

    private function conversionUrl(Media $media, string $conversion): string
    {
        return $media->hasGeneratedConversion($conversion)
            ? $media->getUrl($conversion)
            : $media->getUrl();
    }
}
