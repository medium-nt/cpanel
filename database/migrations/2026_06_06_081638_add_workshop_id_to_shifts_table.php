<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Добавляем колонку как nullable для backfill
        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('workshop_id')
                ->after('id')
                ->nullable()
                ->constrained('workshops')
                ->restrictOnDelete();
        });

        // Backfill: все существующие смены → цех №1
        DB::table('shifts')->update(['workshop_id' => 1]);

        // Делаем NOT NULL после заполнения
        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('workshop_id')
                ->nullable(false)
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropForeign(['workshop_id']);
            $table->dropColumn('workshop_id');
        });
    }
};
