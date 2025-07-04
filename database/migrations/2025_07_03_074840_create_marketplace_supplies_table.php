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
        Schema::create('marketplace_supplies', function (Blueprint $table) {
            $table->id();
            $table->string('supply_id')->unique()->nullable();
            $table->integer('marketplace_id');
            $table->integer('status')->default(0);
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
        Schema::dropIfExists('marketplace_supplies');
    }
};
