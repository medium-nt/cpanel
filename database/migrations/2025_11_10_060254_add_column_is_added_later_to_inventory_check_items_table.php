<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_check_items', function (Blueprint $table) {
            $table->boolean('is_added_later')
                ->after('is_found')
                ->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_check_items', function (Blueprint $table) {
            $table->dropColumn('is_added_later');
        });
    }
};
