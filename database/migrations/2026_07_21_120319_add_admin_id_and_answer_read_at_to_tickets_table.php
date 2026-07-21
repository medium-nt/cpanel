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
        Schema::table('tickets', function (Blueprint $table) {
            // Кто из админов закрыл тикет с ответом. restrict (как user_id): User под SoftDeletes,
            // физического удаления нет; консистентно с существующим FK tickets.user_id_foreign.
            $table->foreignId('admin_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users');

            // Когда автор тикета прочитал ответ администратора. null = ответ не прочитан.
            $table->timestamp('answer_read_at')->nullable()->after('closed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropColumn(['admin_id', 'answer_read_at']);
        });
    }
};
