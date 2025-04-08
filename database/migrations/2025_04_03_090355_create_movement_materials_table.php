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
        Schema::create('movement_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_id');
            $table->foreign('material_id')->references('id')
                ->on('materials')->onDelete('restrict');
            $table->decimal('quantity', 10)->default(0);
            $table->decimal('ordered_quantity', 10)->default(0);
            $table->decimal('price', 10)->default(0);
            $table->text('comment')->nullable();

            $table->unsignedBigInteger('order_id')->nullable()->default(null);
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('restrict');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movement_materials');
    }
};
