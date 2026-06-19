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
            $table->boolean('supply_negative_crop_enabled')->default(true)->after('support_enabled');
            $table->json('construction_schedule')->nullable()->after('prioritize_crop_fields_when_negative');
        });

        Schema::table('village_buildings', function (Blueprint $table): void {
            $table->boolean('automation_enabled')->default(true)->after('is_under_construction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('village_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'supply_negative_crop_enabled',
                'construction_schedule',
            ]);
        });

        Schema::table('village_buildings', function (Blueprint $table): void {
            $table->dropColumn('automation_enabled');
        });
    }
};
