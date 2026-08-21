<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('source_id')->nullable()->unique();
            $table->string('barcode', 13)->nullable()->unique();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->enum('availability', ['in_stock', 'low_stock', 'out_of_stock', 'archived'])
                ->default('in_stock');
            $table->boolean('is_published')->default(true);
            $table->timestamp('discontinued_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'is_published']);
            $table->index(['brand_id', 'is_published']);
        });

        Schema::create('attribute_value_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'attribute_value_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_value_product');
        Schema::dropIfExists('products');
    }
};
