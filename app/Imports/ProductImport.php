<?php

namespace App\Imports;

use App\Enums\ProductAvailability;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Rules\Ean13;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

final class ProductImport implements SkipsEmptyRows, SkipsOnFailure, ToModel, WithChunkReading, WithHeadingRow, WithValidation
{
    use Importable, RemembersRowNumber, SkipsFailures;

    private int $created = 0;

    private int $updated = 0;

    /** @var array<string, true> */
    private array $seenSourceIds = [];

    /** @var array<string, true> */
    private array $seenBarcodes = [];

    /** @var array<string, true> */
    private array $seenSlugs = [];

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): Model
    {
        $product = $this->findExistingProduct($row) ?? new Product;

        $product->exists ? $this->updated++ : $this->created++;

        $product->fill([
            'source_id' => $row['source_id'],
            'barcode' => $row['barcode'],
            'category_id' => $row['category_id'],
            'brand_id' => $row['brand_id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'short_description' => $row['short_description'],
            'description' => $row['description'],
            'availability' => $row['availability'],
            'is_published' => $row['is_published'],
            'discontinued_at' => $row['discontinued_at'],
        ]);

        return $product;
    }

    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        return [
            'source_id' => ['nullable', 'string', 'max:255', 'required_without:barcode'],
            'barcode' => ['nullable', 'string', 'size:13', new Ean13, 'required_without:source_id'],
            'category_slug' => ['nullable', 'string', Rule::exists('categories', 'slug')],
            'brand_slug' => ['nullable', 'string', Rule::exists('brands', 'slug')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'availability' => ['required', Rule::enum(ProductAvailability::class)],
            'is_published' => ['required', 'boolean'],
            'discontinued_at' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($validator->getData() as $rowNumber => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $this->validateSpreadsheetDuplicate(
                    validator: $validator,
                    rowNumber: $rowNumber,
                    attribute: 'source_id',
                    value: $row['source_id'] ?? null,
                    seen: $this->seenSourceIds,
                );
                $this->validateSpreadsheetDuplicate(
                    validator: $validator,
                    rowNumber: $rowNumber,
                    attribute: 'barcode',
                    value: $row['barcode'] ?? null,
                    seen: $this->seenBarcodes,
                );
                $this->validateSpreadsheetDuplicate(
                    validator: $validator,
                    rowNumber: $rowNumber,
                    attribute: 'slug',
                    value: $row['slug'] ?? null,
                    seen: $this->seenSlugs,
                );

                $sourceProduct = filled($row['source_id'] ?? null)
                    ? Product::withTrashed()->where('source_id', $row['source_id'])->first()
                    : null;
                $barcodeProduct = filled($row['barcode'] ?? null)
                    ? Product::withTrashed()->where('barcode', $row['barcode'])->first()
                    : null;
                $matchedProduct = $sourceProduct ?? $barcodeProduct;

                if ($sourceProduct && $barcodeProduct && ! $sourceProduct->is($barcodeProduct)) {
                    $validator->errors()->add(
                        "{$rowNumber}.barcode",
                        'The barcode belongs to a different product than the source ID.',
                    );
                }

                $slugProduct = Product::withTrashed()->where('slug', $row['slug'] ?? '')->first();

                if ($slugProduct && (! $matchedProduct || ! $slugProduct->is($matchedProduct))) {
                    $validator->errors()->add("{$rowNumber}.slug", 'The slug has already been taken.');
                }
            }
        });
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function prepareForValidation(array $row, int $index): array
    {
        $barcode = $this->nullableString($row['barcode'] ?? null);

        if ($barcode !== null && ctype_digit($barcode) && strlen($barcode) < 13) {
            $barcode = str_pad($barcode, 13, '0', STR_PAD_LEFT);
        }

        $categorySlug = $this->nullableString($row['category_slug'] ?? null);
        $brandSlug = $this->nullableString($row['brand_slug'] ?? null);

        return [
            'source_id' => $this->nullableString($row['source_id'] ?? null),
            'barcode' => $barcode,
            'category_slug' => $categorySlug,
            'category_id' => $categorySlug === null
                ? null
                : Category::query()->where('slug', $categorySlug)->value('id'),
            'brand_slug' => $brandSlug,
            'brand_id' => $brandSlug === null
                ? null
                : Brand::query()->where('slug', $brandSlug)->value('id'),
            'name' => trim((string) ($row['name'] ?? '')),
            'slug' => Str::slug(trim((string) ($row['slug'] ?? ''))),
            'short_description' => $this->nullableString($row['short_description'] ?? null),
            'description' => $this->nullableString($row['description'] ?? null),
            'availability' => $this->nullableString($row['availability'] ?? null) ?? ProductAvailability::InStock->value,
            'is_published' => $this->normalizeBoolean($row['is_published'] ?? true),
            'discontinued_at' => $row['discontinued_at'] ?? null,
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function createdCount(): int
    {
        return $this->created;
    }

    public function updatedCount(): int
    {
        return $this->updated;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function findExistingProduct(array $row): ?Product
    {
        if ($row['source_id'] !== null) {
            $product = Product::withTrashed()->where('source_id', $row['source_id'])->first();

            if ($product) {
                return $product;
            }
        }

        if ($row['barcode'] !== null) {
            return Product::withTrashed()->where('barcode', $row['barcode'])->first();
        }

        return null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return trim((string) $value);
    }

    private function normalizeBoolean(mixed $value): bool|string
    {
        if (is_bool($value)) {
            return $value;
        }

        return match (strtolower(trim((string) $value))) {
            '1', 'true', 'yes', 'y' => true,
            '0', 'false', 'no', 'n' => false,
            default => (string) $value,
        };
    }

    /**
     * @param  array<string, true>  $seen
     */
    private function validateSpreadsheetDuplicate(
        Validator $validator,
        int|string $rowNumber,
        string $attribute,
        mixed $value,
        array &$seen,
    ): void {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (isset($seen[$value])) {
            $validator->errors()->add(
                "{$rowNumber}.{$attribute}",
                "The {$attribute} is duplicated in this spreadsheet.",
            );

            return;
        }

        $seen[$value] = true;
    }
}
