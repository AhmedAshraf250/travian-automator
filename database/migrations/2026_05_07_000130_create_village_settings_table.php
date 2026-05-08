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
        Schema::create('village_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->constrained()->cascadeOnDelete();
            $table->boolean('inherit_from_account')->default(true);
            $table->json('field_priority');
            $table->boolean('pause_buildings')->default(false);
            $table->boolean('pause_fields')->default(false);
            $table->string('trade_mode')->default('balanced');
            $table->boolean('support_enabled')->default(true);
            $table->boolean('send_enabled')->default(true);
            $table->boolean('troop_training_enabled')->default(false);
            $table->boolean('celebration_enabled')->default(false);
            $table->timestamps();

            $table->unique('village_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('village_settings');
    }
};
