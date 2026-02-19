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
        Schema::create('product_stickers', function (Blueprint $table) {
            $table->id();
            $table->string('title');           // Название товара
            $table->string('color')->nullable();       // Цвет
            $table->string('print_type')->nullable();  // Вид принта
            $table->string('material')->nullable();    // Материал
            $table->string('country')->nullable();     // Страна производства
            $table->string('fastening_type')->nullable(); // Тип крепления
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stickers');
    }
};
