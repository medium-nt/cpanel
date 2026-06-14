<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Добавляет workshop_id в shift_schedule и делает shift_id nullable,
 * чтобы поддержать третье состояние дня — «выходной» (shift_id = NULL).
 *
 * Инвариант: одна запись на (workshop_id, date) — один цех в один день
 * работает не более чем одной сменой (рабочая смена ИЛИ выходной).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Добавляем workshop_id (пока nullable).
        Schema::table('shift_schedule', function (Blueprint $table) {
            $table->unsignedBigInteger('workshop_id')->nullable()->after('shift_id');
        });

        // 2. Заполняем workshop_id из смены для существующих записей (DB-агностично).
        $shiftToWorkshop = DB::table('shifts')->pluck('workshop_id', 'id');
        DB::table('shift_schedule')->orderBy('id')->chunk(500, function ($schedules) use ($shiftToWorkshop) {
            foreach ($schedules as $schedule) {
                if (isset($shiftToWorkshop[$schedule->shift_id])) {
                    DB::table('shift_schedule')
                        ->where('id', $schedule->id)
                        ->update(['workshop_id' => $shiftToWorkshop[$schedule->shift_id]]);
                }
            }
        });

        // 3. Защитная дедупликация (workshop_id, date) перед созданием UNIQUE.
        //    Старый unique (shift_id, date) разрешал несколько смен на дату.
        //    На прод-данных дублей нет (проверено 2026-06-14) — шаг no-op.
        $keepIds = DB::table('shift_schedule')
            ->selectRaw('MIN(id) as id')
            ->groupBy('workshop_id', 'date')
            ->pluck('id');
        if ($keepIds->isNotEmpty()) {
            DB::table('shift_schedule')->whereNotIn('id', $keepIds)->delete();
        }

        // 4. Делаем workshop_id NOT NULL, добавляем FK и UNIQUE, убираем старый UNIQUE.
        Schema::table('shift_schedule', function (Blueprint $table) {
            $table->unsignedBigInteger('workshop_id')->nullable(false)->change();
            $table->foreign('workshop_id')
                ->references('id')
                ->on('workshops')
                ->restrictOnDelete();
            $table->unique(['workshop_id', 'date'], 'shift_schedule_workshop_date_unique');
            $table->dropUnique('shift_schedule_shift_id_date_unique');
        });

        // 5. shift_id → nullable (выходной = запись без смены). FK на shift_id отсутствует.
        Schema::table('shift_schedule', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Удаляем записи-выходные (shift_id = NULL) — они несовместимы со старой схемой.
        DB::table('shift_schedule')->whereNull('shift_id')->delete();

        Schema::table('shift_schedule', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable(false)->change();
            $table->unique(['shift_id', 'date'], 'shift_schedule_shift_id_date_unique');
            $table->dropUnique('shift_schedule_workshop_date_unique');
            $table->dropForeign(['workshop_id']);
        });

        Schema::table('shift_schedule', function (Blueprint $table) {
            $table->dropColumn('workshop_id');
        });
    }
};
