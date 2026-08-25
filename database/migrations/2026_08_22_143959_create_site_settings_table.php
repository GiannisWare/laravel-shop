<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name')->default('Kokkonios');
            $table->string('utility_left_text')->nullable();
            $table->string('utility_center_text')->nullable();
            $table->string('utility_right_text')->nullable();
            $table->string('footer_heading')->nullable();
            $table->text('footer_description')->nullable();
            $table->string('copyright_text')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
