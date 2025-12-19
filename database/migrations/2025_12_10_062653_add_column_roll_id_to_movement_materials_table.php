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
        Schema::table('movement_materials', function (Blueprint $table) {
            $table->unsignedBigInteger('roll_id')
                ->after('order_id')
                ->nullable();

            $table->foreign('roll_id')
                ->references('id')
                ->on('rolls');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movement_materials', function (Blueprint $table) {
            $table->dropForeign(['roll_id']);

            $table->dropColumn('roll_id');
        });
    }
};
