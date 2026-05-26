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
        Schema::table('marketplace_warehouses', function (Blueprint $table) {
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('cluster');
            $table->unsignedBigInteger('macrolocal_cluster_id')->nullable()->after('warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_warehouses', function (Blueprint $table) {
            $table->dropColumn(['warehouse_id', 'macrolocal_cluster_id']);
        });
    }
};
