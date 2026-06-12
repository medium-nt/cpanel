<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pivot-таблица: какие материалы доступны в каких цехах
        Schema::create('material_workshop', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')
                ->constrained('materials')
                ->cascadeOnDelete();
            $table->foreignId('workshop_id')
                ->constrained('workshops')
                ->cascadeOnDelete();
            $table->timestamps();

            // Один материал может быть привязан к цеху только один раз
            $table->unique(['material_id', 'workshop_id']);
        });

        // Backfill: привязываем все существующие материалы (не удаленные) к цеху №1
        $materials = DB::table('materials')
            ->whereNull('deleted_at')
            ->pluck('id');
        $now = now();

        foreach ($materials as $materialId) {
            DB::table('material_workshop')->insert([
                'material_id' => $materialId,
                'workshop_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_workshop');
    }
};
