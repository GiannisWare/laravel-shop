<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;
use InvalidArgumentException;

final class ScanProductBarcode
{
    public function handle(string $barcode): Product
    {
        $barcode = trim($barcode);

        if (! Product::isValidEan13($barcode)) {
            throw new InvalidArgumentException('The barcode must be a valid EAN-13 value.');
        }

        return Product::query()
            ->withTrashed()
            ->firstOrCreate(
                ['barcode' => $barcode],
                ['is_published' => false],
            );
    }
}
