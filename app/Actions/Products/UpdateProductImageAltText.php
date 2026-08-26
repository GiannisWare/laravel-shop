<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;

final class UpdateProductImageAltText
{
    /**
     * @param  array<int, array{id?: mixed, alt?: mixed}>  $images
     */
    public function handle(Product $product, array $images): void
    {
        $mediaById = $product->getMedia('images')->keyBy(fn ($media): int => (int) $media->getKey());

        foreach ($images as $image) {
            $mediaId = filter_var($image['id'] ?? null, FILTER_VALIDATE_INT);
            $alt = trim((string) ($image['alt'] ?? ''));

            if ($mediaId === false || $alt === '' || ! $mediaById->has($mediaId)) {
                continue;
            }

            $media = $mediaById->get($mediaId);
            $media->setCustomProperty('alt', mb_substr($alt, 0, 255));
            $media->save();
        }
    }
}
