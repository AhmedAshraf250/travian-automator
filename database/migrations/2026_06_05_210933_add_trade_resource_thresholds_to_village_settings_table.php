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
            $table->unsignedTinyInteger('send_min_resource_percentage')->default(30)->after('send_enabled');
            $table->unsignedTinyInteger('send_reserve_resource_percentage')->default(10)->after('send_min_resource_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('village_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'send_min_resource_percentage',
                'send_reserve_resource_percentage',
            ]);
        });
    }
};
