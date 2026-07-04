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
            $table->string('field_level_cap_mode')->default('inherit')->after('field_priority');
            $table->unsignedTinyInteger('field_level_cap')->nullable()->after('field_level_cap_mode');
        });

        Schema::table('village_resource_states', function (Blueprint $table): void {
            $table->unsignedInteger('available_merchants')->nullable()->after('granary_capacity');
            $table->unsignedInteger('merchant_capacity')->nullable()->after('available_merchants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('village_resource_states', function (Blueprint $table): void {
            $table->dropColumn(['available_merchants', 'merchant_capacity']);
        });

        Schema::table('village_settings', function (Blueprint $table): void {
            $table->dropColumn(['field_level_cap_mode', 'field_level_cap']);
        });
    }
};
