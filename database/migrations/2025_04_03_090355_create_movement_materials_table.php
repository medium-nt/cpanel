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
        Schema::create('movement_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_id');
            $table->foreign('material_id')->references('id')
                ->on('materials')->onDelete('restrict');
            $table->integer('quantity')->default(0);
            $table->integer('ordered_quantity')->default(0);
            $table->decimal('price', 10)->default(0);
            $table->text('comment')->nullable();

            $table->integer('type_movement')->default(0);
            $table->integer('status_movement')->default(0);

            $table->unsignedBigInteger('supplier_id')->nullable()->default(null);
            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers')
                ->onDelete('restrict');

            $table->unsignedBigInteger('storekeeper_id')->nullable()->default(null);
            $table->foreign('storekeeper_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->unsignedBigInteger('seamstress_id')->nullable()->default(null);
            $table->foreign('seamstress_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->integer('is_approved')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movement_materials');
    }
};
