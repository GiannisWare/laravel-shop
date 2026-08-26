<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** @mixin Product */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $images = $this->getMedia('images')
            ->map(fn (Media $media): array => $this->imagePayload($media))
            ->values();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'availability' => [
                'value' => $this->availability->value,
                'label' => $this->availability->label(),
            ],
            'category' => $this->whenLoaded('category', fn (): ?array => $this->category === null ? null : [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'brand' => $this->whenLoaded('brand', fn (): ?array => $this->brand === null ? null : [
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ]),
            'images' => $images,
            'image' => $images->first(),
        ];
    }

    /** @return array{id: int, alt: string, original: string, thumb: string, card: string, medium: string, large: string} */
    private function imagePayload(Media $media): array
    {
        return [
            'id' => (int) $media->getKey(),
            'alt' => trim((string) $media->getCustomProperty('alt')) ?: $this->name,
            'original' => $media->getUrl(),
            'thumb' => $this->conversionUrl($media, 'thumb'),
            'card' => $this->conversionUrl($media, 'card'),
            'medium' => $this->conversionUrl($media, 'medium'),
            'large' => $this->conversionUrl($media, 'large'),
        ];
    }

    private function conversionUrl(Media $media, string $conversion): string
    {
        return $media->hasGeneratedConversion($conversion)
            ? $media->getUrl($conversion)
            : $media->getUrl();
    }
}
