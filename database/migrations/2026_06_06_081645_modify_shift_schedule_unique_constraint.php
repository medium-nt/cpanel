<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_schedule', function (Blueprint $table) {
            // Убираем старое ограничение (одна смена на весь день)
            $table->dropUnique(['date']);

            // Новое ограничение: одна смена может быть назначена на конкретный день только один раз
            $table->unique(['shift_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_schedule', function (Blueprint $table) {
            $table->dropUnique(['shift_id', 'date']);
            $table->unique('date');
        });
    }
};
