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
        Schema::create('account_hero_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('status')->nullable();
            $table->decimal('health_percent', 5, 2)->nullable();
            $table->unsignedTinyInteger('experience_percent')->nullable();
            $table->unsignedSmallInteger('level')->nullable();
            $table->unsignedSmallInteger('adventures_available_count')->default(0);
            $table->boolean('has_unspent_attribute_points')->default(false);
            $table->unsignedSmallInteger('unspent_attribute_points')->nullable();
            $table->unsignedInteger('hero_remaining_seconds')->nullable();
            $table->string('home_village_travian_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();

            $table->unique('account_id');
            $table->index(['status', 'hero_remaining_seconds']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_hero_states');
    }
};
