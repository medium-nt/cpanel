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
        Schema::table('supply_boxes', function (Blueprint $table) {
            $table->unsignedBigInteger('cargo_id')->nullable()->after('closed_at');
            $table->text('sticker_url')->nullable()->after('cargo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supply_boxes', function (Blueprint $table) {
            $table->dropColumn(['cargo_id', 'sticker_url']);
        });
    }
};
