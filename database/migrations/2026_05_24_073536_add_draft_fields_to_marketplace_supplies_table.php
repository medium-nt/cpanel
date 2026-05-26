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
        Schema::table('marketplace_supplies', function (Blueprint $table) {
            $table->unsignedBigInteger('draft_id')->nullable()->after('delivery_type');
            $table->json('draft_params')->nullable()->after('draft_id');
            $table->dateTime('draft_created_at')->nullable()->after('draft_params');
            $table->string('supply_type')->nullable()->after('draft_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_supplies', function (Blueprint $table) {
            $table->dropColumn(['draft_id', 'draft_params', 'draft_created_at', 'supply_type']);
        });
    }
};
