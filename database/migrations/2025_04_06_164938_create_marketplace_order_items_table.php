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
        Schema::create('marketplace_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marketplace_order_id');
            $table->foreign('marketplace_order_id')
                ->references('id')
                ->on('marketplace_orders')
                ->onDelete('cascade');
            $table->integer('marketplace_item_id');
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->integer('status')->default(0);
            $table->integer('seamstress_id')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_order_items');
    }
};
