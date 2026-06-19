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
            $table->boolean('hero_resources_enabled')->default(true)->after('support_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('village_settings', function (Blueprint $table): void {
            $table->dropColumn('hero_resources_enabled');
        });
    }
};
