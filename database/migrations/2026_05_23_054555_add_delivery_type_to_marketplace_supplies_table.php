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
        Schema::table('marketplace_supplies', function (Blueprint $table) {
            $table->string('delivery_type')->nullable()->after('gazelka_shipment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_supplies', function (Blueprint $table) {
            $table->dropColumn('delivery_type');
        });
    }
};
