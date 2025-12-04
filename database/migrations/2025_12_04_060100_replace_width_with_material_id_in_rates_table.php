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
            // удаляем колонку width
            $table->dropColumn('width');

            // добавляем колонку material_id
            $table->unsignedBigInteger('material_id')->after('user_id');
            $table->foreign('material_id')
                ->references('id')
                ->on('materials')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('rates', function (Blueprint $table) {
            // откат: вернуть width
            $table->integer('width')->default(0);

            // убрать material_id
            $table->dropForeign(['material_id']);
            $table->dropColumn('material_id');
        });
    }
};
