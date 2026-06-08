<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Добавляем workshop_id: NULL = глобальная настройка, число = настройка цеха
        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('workshop_id')
                ->after('id')
                ->nullable()
                ->constrained('workshops')
                ->nullOnDelete();

            // Заменяем UNIQUE(name) на составной UNIQUE(name, workshop_id),
            // чтобы одноимённые настройки могли существовать для разных цехов
            $table->dropUnique(['name']);
            $table->unique(['name', 'workshop_id']);
        });
    }

    public function down(): void
    {
        // Удаляем цеховые настройки перед восстановлением UNIQUE(name),
        // иначе дубликаты name не позволят создать уникальный индекс
        DB::table('settings')->whereNotNull('workshop_id')->delete();

        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['name', 'workshop_id']);
            $table->unique(['name']);

            $table->dropForeign(['workshop_id']);
            $table->dropColumn('workshop_id');
        });
    }
};
