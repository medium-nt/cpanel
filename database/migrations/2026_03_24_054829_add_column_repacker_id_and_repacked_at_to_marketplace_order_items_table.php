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
            $table->unsignedBigInteger('repacker_id')
                ->nullable()
                ->default(null)
                ->after('otk_id');
            $table->foreign('repacker_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->timestamp('repacked_at')
                ->nullable()
                ->after('packed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            $table->dropForeign(['repacker_id']);
            $table->dropColumn(['repacker_id', 'repacked_at']);
        });
    }
};
