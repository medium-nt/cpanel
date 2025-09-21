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
        Schema::table('schedules', function (Blueprint $table) {
            $table->time('shift_opened_time')
                ->after('date')
                ->default('00:00:00');
            $table->time('shift_closed_time')
                ->after('shift_opened_time')
                ->default('00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('shift_opened_time');
            $table->dropColumn('shift_closed_time');
        });
    }
};
