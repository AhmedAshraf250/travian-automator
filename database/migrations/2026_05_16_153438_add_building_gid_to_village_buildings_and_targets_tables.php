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
        Schema::table('village_buildings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('building_gid')->default(0)->after('slot_id');
        });

        Schema::table('village_building_targets', function (Blueprint $table): void {
            $table->unsignedTinyInteger('building_gid')->default(0)->after('slot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('village_building_targets', function (Blueprint $table): void {
            $table->dropColumn('building_gid');
        });

        Schema::table('village_buildings', function (Blueprint $table): void {
            $table->dropColumn('building_gid');
        });
    }
};
