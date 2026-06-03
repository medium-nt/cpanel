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
            $table->unsignedInteger('boxes_count')->nullable()->after('gazelka_pickup');
            $table->string('gazelka_invoice')->nullable()->after('boxes_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_supplies', function (Blueprint $table) {
            $table->dropColumn('boxes_count');
            $table->dropColumn('gazelka_invoice');
        });
    }
};
