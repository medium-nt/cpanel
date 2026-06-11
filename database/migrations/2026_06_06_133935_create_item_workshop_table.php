<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot-таблица: какие товары доступны в каких цехах
        Schema::create('item_workshop', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_item_id')
                ->constrained('marketplace_items')
                ->cascadeOnDelete();
            $table->foreignId('workshop_id')
                ->constrained('workshops')
                ->cascadeOnDelete();
            $table->timestamps();

            // Один товар может быть привязан к цеху только один раз
            $table->unique(['marketplace_item_id', 'workshop_id']);
        });

        // Backfill: привязываем все существующие товары к цеху №1
        $items = DB::table('marketplace_items')->pluck('id');
        $now = now();

        foreach ($items as $itemId) {
            DB::table('item_workshop')->insert([
                'marketplace_item_id' => $itemId,
                'workshop_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('item_workshop');
    }
};
