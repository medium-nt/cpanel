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
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('marketplace_order_item_id')
                ->after('title')
                ->nullable()
                ->default(null);
            $table->foreign('marketplace_order_item_id')
                ->references('id')
                ->on('marketplace_order_items')
                ->onDelete('restrict');
            $table->timestamp('paid_at')
                ->after('status')
                ->nullable();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('transaction_type');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('transaction_type', ['out', 'in'])
                ->nullable()
                ->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['marketplace_order_item_id']);
            $table->dropColumn(['marketplace_order_item_id', 'paid_at', 'transaction_type']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('transaction_type', ['outflow', 'inflow'])
                ->nullable()
                ->after('status');
        });
    }
};
