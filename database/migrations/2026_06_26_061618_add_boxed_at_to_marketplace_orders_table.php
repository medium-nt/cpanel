<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавляет колонку boxed_at (время добавления заказа в короб)
     * и заполняет её для существующих заказов в коробах значением updated_at.
     */
    public function up(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->timestamp('boxed_at')->nullable()->after('box_id');
        });

        DB::table('marketplace_orders')
            ->whereNotNull('box_id')
            ->whereNull('boxed_at')
            ->update(['boxed_at' => DB::raw('updated_at')]);
    }

    /**
     * Откатывает миграцию: удаляет колонку boxed_at.
     */
    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropColumn('boxed_at');
        });
    }
};
