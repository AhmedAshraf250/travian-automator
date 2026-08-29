<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('inherit_from_account')->default(true);
            $table->json('field_priority');
            $table->string('field_level_cap_mode')->default('inherit');
            $table->unsignedTinyInteger('field_level_cap')->nullable();
            $table->boolean('prioritize_crop_fields_when_negative')->default(true);
            $table->boolean('pause_buildings')->default(false);
            $table->boolean('pause_fields')->default(false);
            $table->json('construction_schedule')->nullable();
            $table->string('trade_mode')->default('balanced');
            $table->boolean('support_enabled')->default(true);
            $table->boolean('hero_resources_enabled')->default(true);
            $table->boolean('supply_negative_crop_enabled')->default(true);
            $table->boolean('send_enabled')->default(true);
            $table->unsignedTinyInteger('send_min_resource_percentage')->default(30);
            $table->unsignedTinyInteger('send_reserve_resource_percentage')->default(10);
            $table->unsignedInteger('trade_max_duration_seconds')->nullable();
            $table->boolean('troop_training_enabled')->default(false);
            $table->boolean('celebration_enabled')->default(false);
            $table->string('celebration_type')->default('small');
            $table->unsignedSmallInteger('celebration_min_culture_points')->default(200);
            $table->boolean('celebration_use_hero_resources')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_settings');
    }
};
