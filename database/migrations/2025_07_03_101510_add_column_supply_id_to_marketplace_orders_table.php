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
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('supply_id')
                ->nullable()
                ->after('is_printed');
            $table->foreign('supply_id')->references('id')
                ->on('marketplace_supplies')->onDelete('set null');
            $table->string('part_b')->nullable()->after('fulfillment_type');
            $table->string('barcode')->nullable()->after('fulfillment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropForeign(['supply_id']);
            $table->dropColumn('supply_id');
            $table->dropColumn('part_b');
            $table->dropColumn('barcode');
        });
    }
};
