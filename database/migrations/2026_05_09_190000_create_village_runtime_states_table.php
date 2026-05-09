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
        Schema::create('village_runtime_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('tribe_id')->nullable();
            $table->json('troop_slots')->nullable();
            $table->unsignedInteger('incoming_attack_count')->default(0);
            $table->unsignedInteger('incoming_reinforcement_count')->default(0);
            $table->unsignedInteger('outgoing_movement_count')->default(0);
            $table->json('movement_entries')->nullable();
            $table->string('hero_status')->nullable();
            $table->unsignedInteger('hero_remaining_seconds')->nullable();
            $table->timestamp('server_reported_at')->nullable();
            $table->timestamps();

            $table->unique('village_id');
            $table->index(['incoming_attack_count', 'incoming_reinforcement_count'], 'vrs_attack_support_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('village_runtime_states');
    }
};
