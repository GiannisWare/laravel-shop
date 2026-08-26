<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CatalogueFilterSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $values = $this->seedAttributes();
        $this->assignValuesToPublishedProducts($values);

        Cache::forget('storefront.catalogue-filters.v1');
    }

    private function seedCategories(): void
    {
        $categories = [
            ['Paints', 'paints', 'Professional paints and colour systems.'],
            ['Brushes', 'brushes', 'Brushes for painting, decorating and finishing.'],
            ['Paper', 'paper', 'Artist and decorating papers.'],
            ['Tools', 'tools', 'Hand tools for preparation and application.'],
            ['Construction Tools', 'construction-tools', 'Tools and equipment for building and preparation.'],
            ['Surfaces', 'surfaces', 'Prepared surfaces and supports.'],
            ['Accessories', 'accessories', 'Useful accessories for clean, accurate work.'],
            ['Sets', 'sets', 'Curated product and starter sets.'],
        ];

        foreach ($categories as $index => [$name, $slug, $description]) {
            Category::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'parent_id' => null,
                    'name' => $name,
                    'description' => $description,
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return array<string, array<string, AttributeValue>>
     */
    private function seedAttributes(): array
    {
        $definitions = [
            'medium' => [
                'name' => 'Medium',
                'values' => [
                    ['Acrylic', 'acrylic', null],
                    ['Oil', 'oil', null],
                    ['Watercolour', 'watercolour', null],
                    ['Gouache', 'gouache', null],
                    ['Ink', 'ink', null],
                    ['Mixed Media', 'mixed-media', null],
                ],
            ],
            'surface' => [
                'name' => 'Surface',
                'values' => [
                    ['Canvas', 'canvas', null],
                    ['Paper', 'paper', null],
                    ['Board', 'board', null],
                    ['Wood', 'wood', null],
                    ['Metal', 'metal', null],
                    ['Fabric', 'fabric', null],
                ],
            ],
            'size' => [
                'name' => 'Size',
                'values' => [
                    ['Small', 'small', null],
                    ['Medium', 'medium', null],
                    ['Large', 'large', null],
                ],
            ],
            'colour-family' => [
                'name' => 'Colour Family',
                'values' => [
                    ['Red', 'red', '#D71920'],
                    ['Orange', 'orange', '#F58220'],
                    ['Yellow', 'yellow', '#FFD400'],
                    ['Green', 'green', '#2FB344'],
                    ['Cyan', 'cyan', '#2EB5C7'],
                    ['Blue', 'blue', '#0A5CC0'],
                    ['Violet', 'violet', '#6F3CC3'],
                    ['Pink', 'pink', '#FF9FC5'],
                    ['Brown', 'brown', '#995218'],
                    ['Black', 'black', '#111111'],
                    ['Grey', 'grey', '#9E9E9E'],
                    ['White', 'white', '#FFFFFF'],
                ],
            ],
        ];

        $seededValues = [];

        foreach ($definitions as $attributeIndex => $definition) {
            $attribute = Attribute::query()->updateOrCreate(
                ['slug' => $attributeIndex],
                [
                    'name' => $definition['name'],
                    'is_filterable' => true,
                    'sort_order' => (array_search($attributeIndex, array_keys($definitions), true) + 1) * 10,
                ],
            );

            foreach ($definition['values'] as $valueIndex => [$value, $slug, $swatchHex]) {
                $seededValues[$attributeIndex][$slug] = AttributeValue::query()->updateOrCreate(
                    ['attribute_id' => $attribute->getKey(), 'slug' => $slug],
                    [
                        'value' => $value,
                        'swatch_hex' => $swatchHex,
                        'sort_order' => ($valueIndex + 1) * 10,
                    ],
                );
            }
        }

        return $seededValues;
    }

    /**
     * @param  array<string, array<string, AttributeValue>>  $values
     */
    private function assignValuesToPublishedProducts(array $values): void
    {
        Product::query()
            ->published()
            ->whereNotNull('name')
            ->each(function (Product $product) use ($values): void {
                $name = Str::lower((string) $product->name);
                $slugs = [];

                if (Str::contains($name, 'watercolour')) {
                    $slugs['medium'][] = 'watercolour';
                    $slugs['surface'][] = 'paper';
                } elseif (Str::contains($name, 'gouache')) {
                    $slugs['medium'][] = 'gouache';
                    $slugs['surface'] = ['paper', 'board'];
                } elseif (Str::contains($name, ['oil colour', 'oil paint'])) {
                    $slugs['medium'][] = 'oil';
                    $slugs['surface'] = ['canvas', 'wood'];
                } elseif (Str::contains($name, ['acrylic', 'vivechrom'])) {
                    $slugs['medium'][] = 'acrylic';
                    $slugs['surface'] = ['canvas', 'metal', 'fabric'];
                } elseif (Str::contains($name, 'ink')) {
                    $slugs['medium'][] = 'ink';
                    $slugs['surface'][] = 'paper';
                }

                $slugs['size'][] = match (true) {
                    Str::contains($name, ['half pan', '12ml', '15ml']) => 'small',
                    Str::contains($name, '35ml') => 'medium',
                    default => 'large',
                };

                foreach ([
                    'red' => ['red'],
                    'yellow' => ['yellow'],
                    'green' => ['green'],
                    'blue' => ['blue', 'ultramarine'],
                    'violet' => ['violet'],
                    'brown' => ['brown', 'umber', 'sienna'],
                    'black' => ['black'],
                    'grey' => ['grey', 'gray'],
                    'white' => ['white'],
                ] as $colour => $needles) {
                    if (Str::contains($name, $needles)) {
                        $slugs['colour-family'][] = $colour;
                    }
                }

                $valueIds = [];

                foreach ($slugs as $attributeSlug => $attributeSlugs) {
                    foreach ($attributeSlugs as $valueSlug) {
                        if (isset($values[$attributeSlug][$valueSlug])) {
                            $valueIds[] = $values[$attributeSlug][$valueSlug]->getKey();
                        }
                    }
                }

                $valueIds = array_values(array_unique($valueIds));

                if ($valueIds !== []) {
                    $product->attributeValues()->syncWithoutDetaching($valueIds);
                }
            });
    }
}
