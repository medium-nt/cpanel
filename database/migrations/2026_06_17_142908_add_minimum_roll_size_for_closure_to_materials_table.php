<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавляет колонку минимального остатка для закрытия рулона в materials
     * и удаляет глобальную настройку roll_close_min_remaining.
     */
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->decimal('minimum_roll_size_for_closure', 8, 2)
                ->default(10.00)
                ->after('unit');
        });

        DB::table('settings')
            ->where('name', 'roll_close_min_remaining')
            ->delete();
    }

    /**
     * Откатывает изменения: удаляет колонку и возвращает настройку.
     */
    public function down(): void
    {
        DB::table('settings')
            ->insertOrIgnore([
                'name' => 'roll_close_min_remaining',
                'value' => '10',
                'workshop_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('minimum_roll_size_for_closure');
        });
    }
};
