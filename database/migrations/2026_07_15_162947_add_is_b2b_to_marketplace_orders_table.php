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
            // Признак заказа от юридического лица (B2B): Ozon — по legal_info.inn/company_name, WB — по options.isB2B.
            $table->boolean('is_b2b')->default(false);
            $table->index('is_b2b');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropIndex(['is_b2b']);
            $table->dropColumn('is_b2b');
        });
    }
};
