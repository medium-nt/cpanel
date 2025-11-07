<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_check_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_check_id')
                ->constrained('inventory_checks')
                ->cascadeOnDelete();
            $table->foreignId('marketplace_order_item_id')
                ->constrained('marketplace_order_items')
                ->cascadeOnDelete();
            $table->foreignId('expected_shelf_id')
                ->nullable()
                ->constrained('shelves');
            $table->foreignId('founded_shelf_id')
                ->nullable()
                ->constrained('shelves');

            $table->boolean('is_found')->default(false);
            $table->timestamps();

            $table->unique(
                ['inventory_check_id', 'marketplace_order_item_id'],
                'inv_chk_mkt_ord_item_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_check_items');
    }
};
