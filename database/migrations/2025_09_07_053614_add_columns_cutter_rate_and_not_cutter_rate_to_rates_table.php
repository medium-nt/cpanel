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
        Schema::table('rates', function (Blueprint $table) {
            $table->integer('not_cutter_rate')
                ->after('rate')
                ->default(0);
            $table->integer('cutter_rate')
                ->after('not_cutter_rate')
                ->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rates', function (Blueprint $table) {
            $table->dropColumn('not_cutter_rate');
            $table->dropColumn('cutter_rate');
        });
    }
};
