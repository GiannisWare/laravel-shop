<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('slug')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('products')
            ->whereNull('name')
            ->orWhereNull('slug')
            ->orderBy('id')
            ->chunkById(500, function ($products): void {
                foreach ($products as $product) {
                    $name = $product->name;

                    if ($name === null) {
                        $name = match (true) {
                            filled($product->slug) => Str::headline((string) $product->slug),
                            filled($product->barcode) => "Draft product {$product->barcode}",
                            default => "Product {$product->id}",
                        };
                    }

                    $slug = $product->slug;

                    if ($slug === null) {
                        $suffix = "-{$product->id}";
                        $slugBase = Str::slug($name) ?: 'product';
                        $slug = Str::substr($slugBase, 0, 255 - Str::length($suffix)).$suffix;
                    }

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update([
                            'name' => Str::substr($name, 0, 255),
                            'slug' => $slug,
                        ]);
                }
            });

        Schema::table('products', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('slug')->nullable(false)->change();
        });
    }
};
