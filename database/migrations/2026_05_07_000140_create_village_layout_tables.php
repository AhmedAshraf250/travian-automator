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
        Schema::create('village_building_targets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('slot_id');
            $table->string('building_type')->nullable();
            $table->unsignedTinyInteger('target_level')->default(0);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['village_id', 'slot_id']);
            $table->index(['village_id', 'priority']);
        });

        Schema::create('village_buildings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('slot_id');
            $table->string('building_type')->nullable();
            $table->unsignedTinyInteger('current_level')->default(0);
            $table->boolean('is_under_construction')->default(false);
            $table->timestamp('finish_at')->nullable();
            $table->timestamps();

            $table->unique(['village_id', 'slot_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('village_buildings');
        Schema::dropIfExists('village_building_targets');
    }
};
