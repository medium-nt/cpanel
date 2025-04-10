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
        Schema::create('material_consumptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->foreign('item_id')
                ->references('id')
                ->on('marketplace_items')
                ->onDelete('cascade');
            $table->unsignedBigInteger('material_id');
            $table->foreign('material_id')
                ->references('id')
                ->on('materials')
                ->onDelete('cascade');
            $table->decimal('quantity', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_consumptions');
    }
};
