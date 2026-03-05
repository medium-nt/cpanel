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
            $table->unsignedBigInteger('otk_id')
                ->nullable()
                ->default(null)
                ->after('cutter_id');
            $table->foreign('otk_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            $table->dropForeign(['otk_id']);
            $table->dropColumn('otk_id');
        });
    }
};
