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
        Schema::create('account_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('update_period_minutes')->default(15);
            $table->unsignedSmallInteger('min_trade_percent')->default(30);
            $table->unsignedSmallInteger('warehouse_reserve_hours')->default(8);
            $table->unsignedSmallInteger('max_trading_distance')->default(50);
            $table->unsignedSmallInteger('crop_factor_percent')->default(50);
            $table->boolean('avoid_overflow')->default(false);
            $table->boolean('random_refresh_enabled')->default(true);
            $table->unsignedSmallInteger('login_period_minutes')->default(60);
            $table->unsignedSmallInteger('logout_period_minutes')->default(10);
            $table->unsignedSmallInteger('time_variability_percent')->default(20);
            $table->json('resource_priorities');
            $table->unsignedSmallInteger('negative_crop_priority')->default(100);
            $table->boolean('read_reports')->default(false);
            $table->boolean('read_messages')->default(false);
            $table->boolean('refresh_after_build')->default(true);
            $table->boolean('refresh_after_attack')->default(true);
            $table->boolean('accept_quests')->default(false);
            $table->boolean('generate_user_agent')->default(false);
            $table->timestamps();

            $table->unique('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_settings');
    }
};
