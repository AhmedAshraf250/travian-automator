<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('village_buildings') || ! Schema::hasColumn('village_buildings', 'automation_enabled')) {
            return;
        }

        DB::table('village_buildings')
            ->whereBetween('slot_id', [19, 40])
            ->where('building_gid', '>', 0)
            ->whereNotIn('building_gid', [10, 11, 15, 23])
            ->update([
                'automation_enabled' => false,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('village_buildings') || ! Schema::hasColumn('village_buildings', 'automation_enabled')) {
            return;
        }

        DB::table('village_buildings')
            ->whereBetween('slot_id', [19, 40])
            ->where('building_gid', '>', 0)
            ->whereNotIn('building_gid', [10, 11, 15, 23])
            ->update([
                'automation_enabled' => true,
                'updated_at' => now(),
            ]);
    }
};
