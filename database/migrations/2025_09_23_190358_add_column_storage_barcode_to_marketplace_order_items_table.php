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
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            $table->string('storage_barcode')
                ->nullable()
                ->after('marketplace_item_id');
            $table->unsignedBigInteger('shelf_id')
                ->nullable()
                ->after('storage_barcode');
            $table->foreign('shelf_id')->references('id')
                ->on('shelves')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            $table->dropColumn('storage_barcode');
            $table->dropForeign(['shelf_id']);
            $table->dropColumn('shelf_id');
        });
    }
};
