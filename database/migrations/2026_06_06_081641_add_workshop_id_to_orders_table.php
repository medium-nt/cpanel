<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('workshop_id')
                ->after('id')
                ->nullable()
                ->constrained('workshops')
                ->nullOnDelete();
        });

        // Backfill: заказы type_movement != 1 → цех №1
        // Тип 1 (поступление от поставщика) остаётся NULL — это заказ на склад
        DB::table('orders')
            ->where('type_movement', '!=', 1)
            ->update(['workshop_id' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['workshop_id']);
            $table->dropColumn('workshop_id');
        });
    }
};
