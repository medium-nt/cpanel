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
            $table->string('type')->default('FBS')->after('marketplace_id');
            $table->string('cluster')->nullable()->after('type');
            $table->date('supply_date')->nullable()->after('cluster');
            $table->date('gazelka_shipment_date')->nullable()->after('supply_date');
            $table->string('gazelka_shipment_id')->nullable()->after('gazelka_shipment_date');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_supplies', function (Blueprint $table) {
            $table->dropColumn(['type', 'cluster', 'supply_date', 'gazelka_shipment_date', 'gazelka_shipment_id']);
        });
    }
};
