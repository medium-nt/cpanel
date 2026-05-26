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
        Schema::create('ozon_fbo_draft_supply_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_id')->constrained('marketplace_supplies')->cascadeOnDelete();
            $table->string('sku');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ozon_fbo_draft_supply_items');
    }
};
