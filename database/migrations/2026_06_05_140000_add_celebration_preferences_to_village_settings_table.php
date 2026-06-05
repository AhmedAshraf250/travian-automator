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
            $table->string('celebration_type')->default('auto')->after('celebration_enabled');
            $table->unsignedSmallInteger('celebration_min_culture_points')->default(200)->after('celebration_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('village_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'celebration_type',
                'celebration_min_culture_points',
            ]);
        });
    }
};
