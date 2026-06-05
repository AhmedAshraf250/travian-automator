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
        Schema::table('account_settings', function (Blueprint $table): void {
            $table->boolean('hero_use_global_settings')->default(true)->after('generate_user_agent');
            $table->boolean('hero_adventures_enabled')->default(false)->after('hero_use_global_settings');
            $table->unsignedTinyInteger('hero_min_health')->default(40)->after('hero_adventures_enabled');
            $table->boolean('hero_revive_enabled')->default(false)->after('hero_min_health');
            $table->boolean('hero_attribute_upgrade_enabled')->default(false)->after('hero_revive_enabled');
            $table->json('hero_attribute_weights')->nullable()->after('hero_attribute_upgrade_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'hero_use_global_settings',
                'hero_adventures_enabled',
                'hero_min_health',
                'hero_revive_enabled',
                'hero_attribute_upgrade_enabled',
                'hero_attribute_weights',
            ]);
        });
    }
};
