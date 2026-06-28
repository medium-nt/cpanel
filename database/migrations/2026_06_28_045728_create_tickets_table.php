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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            // Без cascade: автор удаляется через SoftDeletes, тикет должен сохраниться.
            $table->foreignId('user_id')->constrained();
            $table->text('description');
            $table->string('page_url', 500)->nullable();
            $table->string('screenshot', 500)->nullable();
            $table->string('status', 20)->default('new');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
