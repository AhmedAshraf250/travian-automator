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
        Schema::table('village_settings', function (Blueprint $table): void {
            $table->unsignedInteger('trade_max_duration_seconds')->nullable()->after('send_reserve_resource_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('village_settings', function (Blueprint $table): void {
            $table->dropColumn('trade_max_duration_seconds');
        });
    }
};
