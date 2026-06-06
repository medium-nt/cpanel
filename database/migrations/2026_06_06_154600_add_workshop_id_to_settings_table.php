<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

            // Индекс для быстрого поиска настройки по ключу и цеху
            $table->index(['name', 'workshop_id']);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropIndex(['name', 'workshop_id']);
            $table->dropForeign(['workshop_id']);
            $table->dropColumn('workshop_id');
        });
    }
};
