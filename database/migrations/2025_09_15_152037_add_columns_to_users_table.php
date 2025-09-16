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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('shift_is_open')
                ->after('orders_priority')
                ->default(false);
            $table->time('start_work_shift')
                ->after('shift_is_open')
                ->default('00:00:00');
            $table->integer('number_working_hours')
                ->after('start_work_shift')
                ->default(0);
            $table->integer('max_late_minutes')
                ->after('number_working_hours')
                ->default(0);
            $table->time('actual_start_work_shift')
                ->after('max_late_minutes')
                ->default('00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('shift_is_open');
            $table->dropColumn('start_work_shift');
            $table->dropColumn('number_working_hours');
            $table->dropColumn('actual_start_work_shift');
            $table->dropColumn('max_late_minutes');
        });
    }
};
