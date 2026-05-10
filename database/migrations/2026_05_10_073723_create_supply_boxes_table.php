<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_supply_id')->constrained('marketplace_supplies')->cascadeOnDelete();
            $table->string('number')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_boxes');
    }
};
