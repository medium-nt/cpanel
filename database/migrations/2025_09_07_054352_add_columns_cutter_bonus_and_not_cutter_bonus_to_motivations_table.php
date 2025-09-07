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
        Schema::table('motivations', function (Blueprint $table) {
            $table->integer('not_cutter_bonus')
                ->after('bonus')
                ->default(0);
            $table->integer('cutter_bonus')
                ->after('not_cutter_bonus')
                ->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('motivations', function (Blueprint $table) {
            $table->dropColumn('not_cutter_bonus');
            $table->dropColumn('cutter_bonus');
        });
    }
};
