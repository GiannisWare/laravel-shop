<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            ->update([
                'name' => DB::raw('COALESCE(name, CONCAT("Product ", id))'),
                'slug' => DB::raw('COALESCE(slug, CONCAT("product-", id))'),
            ]);

        Schema::table('products', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('slug')->nullable(false)->change();
        });
    }
};
