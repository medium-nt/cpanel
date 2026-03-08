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
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            $table->timestamp('packed_at')
                ->nullable()
                ->after('cutting_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            $table->dropColumn('packed_at');
        });
    }
};
