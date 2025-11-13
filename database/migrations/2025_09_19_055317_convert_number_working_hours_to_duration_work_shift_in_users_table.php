<?php

use Carbon\Carbon;
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
            $table->time('duration_work_shift')
                ->after('number_working_hours')
                ->default('00:00:00');
        });

        DB::table('users')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $time = Carbon::createFromTime($user->number_working_hours, 0, 0)
                    ->format('H:i:s');

                DB::table('users')->where('id', $user->id)->update([
                    'duration_work_shift' => $time,
                ]);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('number_working_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('number_working_hours')
                ->after('duration_work_shift')
                ->default(0);
        });

        DB::table('users')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $hours = Carbon::parse($user->duration_work_shift)->hour;

                DB::table('users')->where('id', $user->id)->update([
                    'number_working_hours' => $hours,
                ]);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('duration_work_shift');
        });
    }
};
