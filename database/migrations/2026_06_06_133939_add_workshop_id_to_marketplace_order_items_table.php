<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Добавляем колонку workshop_id (nullable: NULL = не взят в работу)
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            $table->foreignId('workshop_id')
                ->after('id')
                ->nullable()
                ->constrained('workshops')
                ->nullOnDelete();
        });

        // Backfill: только товары со статусом != 0 (уже были в работе) → цех №1
        DB::table('marketplace_order_items')
            ->where('status', '!=', 0)
            ->update(['workshop_id' => 1]);
    }

    public function down(): void
    {
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            $table->dropForeign(['workshop_id']);
            $table->dropColumn('workshop_id');
        });
    }
};
