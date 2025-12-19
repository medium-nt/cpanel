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
        Schema::create('rolls', function (Blueprint $table) {
            $table->id();
            $table->string('roll_code')
                ->default('');
            $table->foreignId('material_id')
                ->constrained();
            $table->string('status');
            $table->decimal('initial_quantity', 10)
                ->default(0);
            $table->decimal('shortage_quantity', 10)
                ->default(0);
            $table->boolean('is_printed')
                ->default(false);
            $table->timestamp('completed_at')
                ->nullable()
                ->default(null);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rolls');
    }
};
